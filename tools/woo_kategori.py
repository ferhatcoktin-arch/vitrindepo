#!/usr/bin/env python3
"""Reorganise the WooCommerce product categories of iqosvitrin.com.tr.

The target structure and the product -> category rules live in
kategori-yapisi.json. Every command that writes is a dry run unless --yes
is given, and `apply` always takes a backup first.

  backup                      save products, categories and attributes to yedek/<timestamp>/
  plan [--out plan.csv]       propose a category for every product (writes nothing to the site)
  setup [--yes]               create the categories and attributes from kategori-yapisi.json
  apply plan.csv [--yes]      back up, then move every product into its category from plan.csv
  restore yedek/<ts> [--yes]  put every product's categories back as they were in that backup

Environment: WC_URL (default https://iqosvitrin.com.tr), WC_CONSUMER_KEY,
WC_CONSUMER_SECRET (WooCommerce > Settings > Advanced > REST API, read/write).
"""

import argparse
import base64
import csv
import html
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
STRUCTURE = ROOT / "kategori-yapisi.json"
BACKUP_DIR = ROOT / "yedek"
PLAN_FIELDS = ["id", "name", "status", "current", "new", "rule"]
UNSURE = "?"
BATCH_SIZE = 50

FOLD = str.maketrans("çğıİöşüÇĞÖŞÜ", "cgiiosucgosu")


def fold(text):
    """Lowercase and strip Turkish diacritics so 'Kılıf', 'KILIF' and 'kilif' compare equal."""
    return html.unescape(text).translate(FOLD).lower()


class Api:
    def __init__(self):
        self.base = os.environ.get("WC_URL", "https://iqosvitrin.com.tr").rstrip("/") + "/wp-json/wc/v3/"
        self.key = os.environ.get("WC_CONSUMER_KEY")
        self.secret = os.environ.get("WC_CONSUMER_SECRET")
        if not self.key or not self.secret:
            sys.exit("WC_CONSUMER_KEY and WC_CONSUMER_SECRET must be set")
        self.query_auth = os.environ.get("WC_QUERY_AUTH") == "1"

    def request(self, method, path, params=None, body=None):
        params = dict(params or {})
        headers = {"User-Agent": "vitrindepo-kategori/1.0", "Accept": "application/json"}
        if self.query_auth:
            params.update(consumer_key=self.key, consumer_secret=self.secret)
        else:
            token = base64.b64encode(f"{self.key}:{self.secret}".encode()).decode()
            headers["Authorization"] = "Basic " + token
        url = self.base + path + ("?" + urllib.parse.urlencode(params) if params else "")
        data = None
        if body is not None:
            data = json.dumps(body).encode()
            headers["Content-Type"] = "application/json"
        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=90) as resp:
                return json.load(resp), resp.headers
        except urllib.error.HTTPError as e:
            if e.code == 401 and not self.query_auth:
                # Some hosts drop the Authorization header; WooCommerce also accepts the keys as query params over HTTPS.
                self.query_auth = True
                return self.request(method, path, params, body)
            detail = e.read()[:500].decode(errors="replace")
            sys.exit(f"{method} {path} -> HTTP {e.code}: {detail}")

    def get_all(self, path, params=None):
        items, page = [], 1
        while True:
            batch, headers = self.request("GET", path, {**(params or {}), "per_page": 100, "page": page})
            items.extend(batch)
            if page >= int(headers.get("X-WP-TotalPages") or 1):
                return items
            page += 1

    def batch_update(self, path, updates):
        failed = []
        for i in range(0, len(updates), BATCH_SIZE):
            resp, _ = self.request("POST", path + "/batch", body={"update": updates[i:i + BATCH_SIZE]})
            failed += [u for u in resp.get("update", []) if "error" in u]
        return failed


def load_structure():
    return json.loads(STRUCTURE.read_text(encoding="utf-8"))


def rel(path):
    try:
        return Path(path).resolve().relative_to(ROOT)
    except ValueError:
        return path


def match_rule(rule, text):
    """Return the keywords that made `rule` match `text`, or None."""
    def has(word):
        return re.search(r"(?<![a-z0-9])" + re.escape(fold(word)), text) is not None

    hits = []
    if "all" in rule:
        if not all(has(w) for w in rule["all"]):
            return None
        hits += rule["all"]
    if "any" in rule:
        found = [w for w in rule["any"] if has(w)]
        if not found:
            return None
        hits += found
    if "regex" in rule:
        if not re.search(rule["regex"], text):
            return None
        hits.append("/" + rule["regex"] + "/")
    return hits


def classify(name, rules):
    text = fold(name)
    for rule in rules:
        hits = match_rule(rule, text)
        if hits is not None:
            return rule["category"], "+".join(hits)
    return UNSURE, ""


def take_backup(api):
    target = BACKUP_DIR / time.strftime("%Y%m%d-%H%M%S")
    target.mkdir(parents=True)
    products = api.get_all("products", {"status": "any"})
    categories = api.get_all("products/categories")
    attributes, _ = api.request("GET", "products/attributes")
    for attr in attributes:
        attr["terms"] = api.get_all(f"products/attributes/{attr['id']}/terms")
    for name, data in [("products", products), ("categories", categories), ("attributes", attributes)]:
        (target / f"{name}.json").write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"backup: {len(products)} products, {len(categories)} categories, "
          f"{len(attributes)} attributes -> {rel(target)}")
    return target


def cmd_backup(api, args):
    take_backup(api)


def cmd_plan(api, args):
    rules = load_structure()["rules"]
    rows = []
    for p in api.get_all("products", {"status": "any"}):
        category, why = classify(p["name"], rules)
        rows.append({
            "id": p["id"],
            "name": html.unescape(p["name"]),
            "status": p["status"],
            "current": " | ".join(html.unescape(c["name"]) for c in p["categories"]),
            "new": category,
            "rule": why,
        })
    rows.sort(key=lambda r: (r["new"], r["name"]))
    out = Path(args.out)
    with out.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=PLAN_FIELDS)
        writer.writeheader()
        writer.writerows(rows)

    counts = {}
    for r in rows:
        counts[r["new"]] = counts.get(r["new"], 0) + 1
    for category, n in sorted(counts.items()):
        print(f"{n:4}  {category}")
    unsure = [r for r in rows if r["new"] == UNSURE]
    for r in unsure:
        print(f"  ? #{r['id']} {r['name']}")
    print(f"plan: {len(rows)} products, {len(unsure)} need a manual category -> {rel(out)}")


def ensure_categories(api, defs, existing, parent, apply):
    """Create the missing categories of `defs` under `parent` (a category dict, or None for top level)."""
    for d in defs:
        cat = existing.get(d["slug"])
        parent_id = parent["id"] if parent else 0
        where = parent["slug"] if parent else "top level"
        if cat:
            note = "" if cat["parent"] == parent_id else f"  (warning: expected under {where})"
            print(f"exists        {d['slug']}{note}")
        elif apply:
            cat, _ = api.request("POST", "products/categories",
                                 body={"name": d["name"], "slug": d["slug"], "parent": parent_id})
            existing[cat["slug"]] = cat
            print(f"created       {d['slug']} under {where}")
        else:
            cat = {"id": 0, "slug": d["slug"], "parent": parent_id}
            print(f"would create  {d['slug']} under {where}")
        ensure_categories(api, d.get("children", []), existing, cat, apply)


def ensure_attributes(api, defs, apply):
    attributes, _ = api.request("GET", "products/attributes")
    existing = {a["slug"].removeprefix("pa_"): a for a in attributes}
    for d in defs:
        attr = existing.get(d["slug"])
        if attr:
            print(f"exists        attribute {d['slug']}")
            terms = {fold(t["name"]) for t in api.get_all(f"products/attributes/{attr['id']}/terms")}
        elif apply:
            attr, _ = api.request("POST", "products/attributes",
                                  body={"name": d["name"], "slug": d["slug"], "type": "select", "has_archives": False})
            print(f"created       attribute {d['slug']}")
            terms = set()
        else:
            print(f"would create  attribute {d['slug']}")
            terms = set()
        for term in d["terms"]:
            if fold(term) in terms:
                continue
            if apply:
                api.request("POST", f"products/attributes/{attr['id']}/terms", body={"name": term})
                print(f"created         term {term}")
            else:
                print(f"would create    term {term}")


def cmd_setup(api, args):
    structure = load_structure()
    existing = {c["slug"]: c for c in api.get_all("products/categories")}
    ensure_categories(api, structure["categories"], existing, None, args.yes)
    ensure_attributes(api, structure["attributes"], args.yes)
    if not args.yes:
        print("dry run - nothing changed; add --yes to create")


def cmd_apply(api, args):
    with open(args.plan, newline="", encoding="utf-8") as f:
        rows = list(csv.DictReader(f))
    categories = {c["slug"]: c for c in api.get_all("products/categories")}
    updates, skipped = [], []
    for r in rows:
        slug = r["new"].strip()
        if not slug or slug == UNSURE:
            skipped.append(r)
            continue
        if slug not in categories:
            sys.exit(f"category {slug!r} (product #{r['id']}) does not exist on the site - run setup --yes first")
        updates.append({"id": int(r["id"]), "categories": [{"id": categories[slug]["id"]}]})

    print(f"{len(updates)} products to move, {len(skipped)} left as they are (no category in plan)")
    for r in skipped:
        print(f"  skip #{r['id']} {r['name']}")
    if not args.yes:
        print("dry run - nothing changed; add --yes to apply")
        return

    backup = take_backup(api)
    failed = api.batch_update("products", updates)
    for u in failed:
        print(f"  failed #{u.get('id')}: {u['error'].get('message')}")
    print(f"moved {len(updates) - len(failed)} products, {len(failed)} failed")
    print(f"undo with: python3 tools/woo_kategori.py restore {rel(backup)} --yes")


def cmd_restore(api, args):
    products = json.loads((Path(args.backup) / "products.json").read_text(encoding="utf-8"))
    updates = [{"id": p["id"], "categories": [{"id": c["id"]} for c in p["categories"]]} for p in products]
    print(f"{len(updates)} products to restore from {rel(args.backup)}")
    if not args.yes:
        print("dry run - nothing changed; add --yes to restore")
        return
    failed = api.batch_update("products", updates)
    for u in failed:
        print(f"  failed #{u.get('id')}: {u['error'].get('message')}")
    print(f"restored {len(updates) - len(failed)} products, {len(failed)} failed")


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("backup")
    p = sub.add_parser("plan")
    p.add_argument("--out", default=str(ROOT / "plan.csv"))
    p = sub.add_parser("setup")
    p.add_argument("--yes", action="store_true")
    p = sub.add_parser("apply")
    p.add_argument("plan")
    p.add_argument("--yes", action="store_true")
    p = sub.add_parser("restore")
    p.add_argument("backup")
    p.add_argument("--yes", action="store_true")
    args = parser.parse_args()
    commands = {"backup": cmd_backup, "plan": cmd_plan, "setup": cmd_setup, "apply": cmd_apply, "restore": cmd_restore}
    commands[args.command](Api(), args)


if __name__ == "__main__":
    main()
