#!/usr/bin/env python3
"""End-to-end test of woo_kategori.py against a small in-memory WooCommerce REST API.

    python3 tools/test_woo_kategori.py

The tool runs in a temporary copy of the repository, so the test never touches yedek/ or plan.csv here.
"""

import base64
import csv
import json
import shutil
import socket
import subprocess
import sys
import tempfile
import threading
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

ROOT = Path(__file__).resolve().parent.parent
KEY, SECRET = "ck_test", "cs_test"

PRODUCTS = {
    101: ("IQOS ILUMA i PRIME Jade Green", [20], "iluma-i-serisi"),
    102: ("IQOS ILUMA ONE Moss Green", [20], "iluma-serisi"),
    103: ("TEREA Amber", [21], "terea-tutun-aromali"),
    104: ("TEREA Turquoise", [21], "terea-mentollu"),
    105: ("TEREA Sun Pearl", [21], "terea-kapsullu-meyveli"),
    106: ("TEREA Green Zing", [21], "terea-kapsullu-meyveli"),
    107: ("IQOS ILUMA i ONE + 10 Paket TEREA Kampanya Seti", [20, 21], "setler-kampanyalar"),
    108: ("IQOS ILUMA i Deri Kılıf", [22], "kilif-kapak"),
    109: ("IQOS USB-C Şarj Kablosu", [22], "sarj-kablo"),
    110: ("HEETS Amber Selection", [15], "?"),
    111: ("TEREA Yellow &#8211; Karton", [21], "terea-tutun-aromali"),
}
FILLER = 105  # more than one page of 100 products


class Shop:
    def __init__(self):
        self.categories = [self.category(i, n, s, 0) for i, n, s in [
            (15, "Uncategorized", "uncategorized"),
            (20, "IQOS", "iqos"),
            (21, "TEREA", "terea-cesitleri"),  # same name as the target "TEREA", other slug
            (22, "Aksesuar", "aksesuar"),
        ]]
        self.products = [self.product(pid, name, cats) for pid, (name, cats, _) in PRODUCTS.items()]
        self.products += [self.product(1000 + i, f"Numune ürün {i}", [15]) for i in range(FILLER)]
        self.attributes, self.terms, self.recounts = [], {}, 0

    @staticmethod
    def category(cid, name, slug, parent, menu_order=0):
        return {"id": cid, "name": name.replace("&", "&amp;"), "slug": slug, "parent": parent,
                "menu_order": menu_order, "count": 0}

    def product(self, pid, name, cats):
        return {"id": pid, "name": name, "status": "publish", "type": "simple", "stock_status": "instock",
                "categories": [self.cat_ref(c) for c in cats]}

    def cat_ref(self, cid):
        c = next(c for c in self.categories if c["id"] == cid)
        return {"id": c["id"], "name": c["name"], "slug": c["slug"]}

    def categories_of(self, pid):
        return [c["slug"] for c in next(p for p in self.products if p["id"] == pid)["categories"]]


def handler_for(shop):
    class Handler(BaseHTTPRequestHandler):
        def log_message(self, *args):
            pass

        def reply(self, code, data, pages=1):
            body = json.dumps(data).encode()
            self.send_response(code)
            self.send_header("Content-Type", "application/json")
            self.send_header("X-WP-TotalPages", str(pages))
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)

        def page(self, items, query):
            per_page, page = int(query.get("per_page", ["10"])[0]), int(query.get("page", ["1"])[0])
            self.reply(200, items[(page - 1) * per_page:page * per_page], -(-len(items) // per_page) or 1)

        def handle_any(self, method):
            url = urlsplit(self.path)
            path, query = url.path.removeprefix("/wp-json/wc/v3/"), parse_qs(url.query)
            token = base64.b64encode(f"{KEY}:{SECRET}".encode()).decode()
            if self.headers.get("Authorization") != "Basic " + token:
                return self.reply(401, {"code": "woocommerce_rest_cannot_view"})
            length = int(self.headers.get("Content-Length") or 0)
            body = json.loads(self.rfile.read(length)) if length else {}

            if (method, path) == ("GET", "system_status"):
                return self.reply(200, {
                    "environment": {"wp_version": "6.8.2", "version": "10.1.0", "php_version": "8.2.29"},
                    "theme": {"name": "Flatsome Child", "version": "3.0", "is_child_theme": True,
                              "parent_name": "Flatsome", "parent_version": "3.19.4", "overrides": [{}, {}]},
                    "active_plugins": [{"name": "WooCommerce", "version": "10.1.0"},
                                       {"name": "Elementor", "version": "3.31.0"}],
                })
            if (method, path) == ("PUT", "system_status/tools/recount_terms"):
                shop.recounts += 1
                return self.reply(200, {"id": "recount_terms", "success": True, "message": "Terms successfully recounted"})
            if (method, path) == ("GET", "products"):
                return self.page(shop.products, query)
            if (method, path) == ("POST", "products/batch"):
                done = []
                for u in body.get("update", []):
                    p = next((p for p in shop.products if p["id"] == u["id"]), None)
                    if p is None:
                        done.append({"id": u["id"], "error": {"code": "woocommerce_rest_product_invalid_id",
                                                              "message": "Invalid ID."}})
                        continue
                    p["categories"] = [shop.cat_ref(c["id"]) for c in u["categories"]]
                    done.append(p)
                return self.reply(200, {"update": done})
            if (method, path) == ("GET", "products/categories"):
                return self.page(shop.categories, query)
            if (method, path) == ("POST", "products/categories"):
                # Like WordPress: a taken slug is refused, a same-named sibling with a new slug is allowed.
                if any(c["slug"] == body["slug"] for c in shop.categories):
                    return self.reply(400, {"code": "term_exists", "message": "A term with the name provided already exists."})
                cat = shop.category(max(c["id"] for c in shop.categories) + 1, body["name"], body["slug"],
                                    body.get("parent", 0), body.get("menu_order", 0))
                shop.categories.append(cat)
                return self.reply(201, cat)
            if (method, path) == ("GET", "products/attributes"):
                return self.reply(200, shop.attributes)
            if (method, path) == ("POST", "products/attributes"):
                attr = {"id": len(shop.attributes) + 1, "name": body["name"], "slug": "pa_" + body["slug"]}
                shop.attributes.append(attr)
                shop.terms[attr["id"]] = []
                return self.reply(201, attr)
            parts = path.split("/")
            if parts[:2] == ["products", "attributes"] and parts[3:] == ["terms"]:
                terms = shop.terms[int(parts[2])]
                if method == "GET":
                    return self.page(terms, query)
                terms.append({"id": len(terms) + 1, "name": body["name"]})
                return self.reply(201, terms[-1])
            return self.reply(404, {"code": "rest_no_route", "message": f"{method} {path}"})

        def do_GET(self):
            self.handle_any("GET")

        def do_POST(self):
            self.handle_any("POST")

        def do_PUT(self):
            self.handle_any("PUT")

    return Handler


class WooKategoriTest(unittest.TestCase):
    def setUp(self):
        self.shop = Shop()
        self.server = ThreadingHTTPServer(("127.0.0.1", 0), handler_for(self.shop))
        threading.Thread(target=self.server.serve_forever, daemon=True).start()
        self.work = Path(tempfile.mkdtemp())
        (self.work / "tools").mkdir()
        shutil.copy(ROOT / "tools" / "woo_kategori.py", self.work / "tools")
        shutil.copy(ROOT / "kategori-yapisi.json", self.work)

    def tearDown(self):
        self.server.shutdown()
        self.server.server_close()
        shutil.rmtree(self.work)

    def run_tool(self, *args, url=None, expect_ok=True):
        env = {"PATH": "/usr/bin:/bin", "WC_CONSUMER_KEY": KEY, "WC_CONSUMER_SECRET": SECRET,
               "WC_URL": url or f"http://127.0.0.1:{self.server.server_port}", "NO_PROXY": "127.0.0.1"}
        result = subprocess.run([sys.executable, "tools/woo_kategori.py", *args], cwd=self.work, env=env,
                                capture_output=True, text=True, timeout=60)
        if expect_ok:
            self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        return result

    def slugs(self):
        return {c["slug"]: c for c in self.shop.categories}

    def test_full_workflow(self):
        out = self.run_tool("inspect").stdout
        self.assertIn("WordPress 6.8.2, WooCommerce 10.1.0, PHP 8.2.29", out)
        self.assertIn("theme: Flatsome Child 3.0 (child of Flatsome 3.19.4), overrides 2 WooCommerce templates", out)
        self.assertIn("  Elementor 3.31.0", out)
        self.assertIn(f"products: {len(PRODUCTS) + FILLER} ({len(PRODUCTS) + FILLER} publish;", out)
        self.assertIn("  TEREA [terea-cesitleri] 0", out)

        self.run_tool("plan")
        with open(self.work / "plan.csv", newline="", encoding="utf-8") as f:
            plan = {int(r["id"]): r for r in csv.DictReader(f)}
        self.assertEqual(len(plan), len(PRODUCTS) + FILLER)
        for pid, (name, _, expected) in PRODUCTS.items():
            self.assertEqual(plan[pid]["new"], expected, name)
        self.assertEqual(plan[111]["name"], "TEREA Yellow – Karton")
        self.assertEqual(plan[107]["current"], "IQOS | TEREA")

        before = len(self.shop.categories)
        out = self.run_tool("setup").stdout
        self.assertEqual(len(self.shop.categories), before, "dry run must not create anything")
        self.assertIn("exists        terea  (same name, slug terea-cesitleri)", out)
        self.assertIn("would create  terea-mentollu under terea-cesitleri", out)

        self.run_tool("setup", "--yes")
        cats = self.slugs()
        self.assertNotIn("terea", cats, "the existing TEREA must be reused, not duplicated")
        self.assertEqual(sum(c["name"] == "TEREA" and c["parent"] == 0 for c in cats.values()), 1)
        self.assertEqual(cats["terea-mentollu"]["parent"], cats["terea-cesitleri"]["id"])
        self.assertEqual(cats["iluma-i-serisi"]["parent"], cats["iqos-cihazlar"]["id"])
        self.assertEqual([cats[s]["menu_order"] for s in ["terea-tutun-aromali", "terea-mentollu",
                                                          "terea-kapsullu-meyveli"]], [0, 1, 2])
        self.assertEqual({a["slug"] for a in self.shop.attributes}, {"pa_aroma", "pa_yogunluk", "pa_paket", "pa_renk"})
        self.run_tool("setup", "--yes")
        self.assertEqual(len(self.shop.categories), len(cats), "setup must be repeatable")

        self.run_tool("apply", "plan.csv")
        self.assertEqual(self.shop.categories_of(103), ["terea-cesitleri"], "dry run must not move products")

        out = self.run_tool("apply", "plan.csv", "--yes").stdout
        self.assertIn(f"moved {len(PRODUCTS) - 1} products, 0 failed", out)
        self.assertEqual(self.shop.categories_of(103), ["terea-tutun-aromali"])
        self.assertEqual(self.shop.categories_of(107), ["setler-kampanyalar"])
        self.assertEqual(self.shop.categories_of(110), ["uncategorized"], "? rows stay where they are")
        self.assertEqual(self.shop.recounts, 1)
        backups = list((self.work / "yedek").iterdir())
        self.assertEqual(len(backups), 1)

        self.run_tool("restore", str(backups[0]), "--yes")
        self.assertEqual(self.shop.categories_of(103), ["terea-cesitleri"])
        self.assertEqual(self.shop.categories_of(107), ["iqos", "terea-cesitleri"])

    def test_unreachable_host(self):
        with socket.socket() as s:
            s.bind(("127.0.0.1", 0))
            port = s.getsockname()[1]
        result = self.run_tool("inspect", url=f"http://127.0.0.1:{port}", expect_ok=False)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("cannot reach 127.0.0.1", result.stderr)
        self.assertNotIn("Traceback", result.stderr)


if __name__ == "__main__":
    unittest.main()
