# vitrindepo

Tools for restructuring the product categories of iqosvitrin.com.tr, a self-hosted WordPress + WooCommerce shop. The owner writes in casual Turkish; answer in Turkish.

- Site access is only through the WooCommerce REST API with `WC_CONSUMER_KEY` / `WC_CONSUMER_SECRET` from the environment, and the host must be in the environment's network allowlist. The WordPress.com connector sees the site but its site tools need a paid Jetpack plan, so it cannot edit.
- `tools/woo_kategori.py` (stdlib only): `backup`, `plan`, `setup`, `apply`, `restore`. Target structure and classification rules: `kategori-yapisi.json`.
- Workflow: `backup` → `plan` → show the owner the plan (especially `?` rows) and get approval → `setup --yes` → `apply plan.csv --yes`. Never delete categories or products. Commit the `yedek/` backups so an undo survives the container.
