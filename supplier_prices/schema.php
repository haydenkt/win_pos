<?php
function ensureSupplierPriceSchema(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS supplier_price_documents (
        id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(100) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL,
        effective_date DATE NOT NULL, sha256 CHAR(64) NOT NULL,
        page_count INT NOT NULL, item_count INT NOT NULL DEFAULT 0,
        imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS supplier_price_pages (
        id INT AUTO_INCREMENT PRIMARY KEY, document_id INT NOT NULL,
        page_number INT NOT NULL, page_text MEDIUMTEXT NOT NULL, tables_json MEDIUMTEXT NOT NULL,
        UNIQUE KEY document_page (document_id, page_number)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS supplier_price_items (
        id INT AUTO_INCREMENT PRIMARY KEY, document_id INT NOT NULL,
        page_number INT NOT NULL, table_number INT NOT NULL, source_row_number INT NOT NULL,
        category VARCHAR(1000) NOT NULL, product_code VARCHAR(255) NOT NULL,
        description TEXT NOT NULL, specs_json MEDIUMTEXT NOT NULL,
        prices_json MEDIUMTEXT NOT NULL, raw_json MEDIUMTEXT NOT NULL,
        flags_json TEXT NOT NULL, search_text MEDIUMTEXT NOT NULL,
        UNIQUE KEY source_row (document_id, page_number, table_number, source_row_number),
        INDEX document_idx (document_id), INDEX product_code_idx (product_code)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
