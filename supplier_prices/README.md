# Supplier price lists

The October 2026 import preserves two dated catalogues: 1,003 pipe/sheet rows and 365 aluminium rows, with all 96 page texts, original table cells and all available price variants in TiDB. Original PDFs and page previews live in `storage/supplier_prices`. The source document dates are 29 September and 4 September 2026 respectively.

Access uses existing `factory_view` or `products_view` permissions. Users with `factory_manage` or `products_manage` can correct product names/descriptions and rename price lists through CSRF-protected forms with audit logging and stale-edit detection. Corrected descriptions are included in search. Original raw source specifications and prices are preserved. Importing does not change inventory, sales prices or invoices. Search accepts space-separated terms and document/category filters. Different series with the same product code remain separate records.

## Deployment

Deploy this folder, the updated sidebar, and the entire `storage/supplier_prices` folder together. Keep source files in backups alongside the database. Run `php supplier_prices/import.php` once for a new database; it creates the three tables and imports the packaged reviewed catalogue. Rerunning the same bundle skips existing documents. It refuses a different bundle under the same version slug.

The generated source files end in `.php` and begin with an exit guard, preventing direct access even under PHP's development server. `source.php` checks login and permissions, removes the guard while streaming, and only serves known database document slugs. Do not rename these files to unguarded `.pdf`, `.jpg`, or `.json` files in the public directory.

## Extraction notes

Blank prices and dashes are not zero. Currency and price units are not inferred; labels and values are preserved as supplied. Merged cells inherit their source value. Myanmar PDF text and overlapping dimensional text are marked for source review; product drawings and image-only dimensions remain available in original pages rather than being guessed as numeric fields.

One extraction overlap was visually verified and corrected: aluminium page 16, code 868B04B, PC White = 125,400. The original raw cell is retained for traceability. All P-/S- item codes found in the pipe/sheet PDF text are represented in the import. Prices have been spot-checked against rendered source pages, not independently certified by the supplier.
