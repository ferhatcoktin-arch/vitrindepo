# vitrindepo

Tools for restructuring the product categories of iqosvitrin.com.tr, a self-hosted WordPress + WooCommerce shop, and a lightweight theme for it. The owner writes in casual Turkish; answer in Turkish.

- Site access is only through the WooCommerce REST API with `WC_CONSUMER_KEY` / `WC_CONSUMER_SECRET` from the environment, and the host must be in the environment's network allowlist. The WordPress.com connector sees the site but its site tools need a paid Jetpack plan, so it cannot edit. A cloud session cannot open a browser for the owner to log in to wp-admin.
- `tools/woo_kategori.py` (stdlib only): `inspect`, `backup`, `plan`, `setup`, `apply`, `restore`. Target structure and classification rules: `kategori-yapisi.json`. Test: `python3 tools/test_woo_kategori.py`.
- Workflow: `inspect` → `backup` → `plan` → show the owner the plan (especially `?` rows) and get approval → `setup --yes` → `apply plan.csv --yes`. Never delete categories or products. Commit the `yedek/` backups so an undo survives the container.
- Theme `tema/vitrin-hizli/` (classic PHP theme, no build step). The owner uploads `tema/vitrin-hizli.zip`: rebuild it after every theme change (command in README). `vitrin_main_category_names()` / `vitrin_aroma_category_names()` in `functions.php` mirror `kategori-yapisi.json`; change them together.
- Theme test: `tema/test/kur.sh` sets up WordPress + MariaDB with `tema/test/sahte-woocommerce.php`, a stand-in that renders WooCommerce's markup (the real plugin cannot be downloaded from here), then `tema/test/ekran.mjs` takes mobile/desktop screenshots.
