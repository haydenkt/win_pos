-- Apply once before deploying original-unit display. Do not guess legacy units.
ALTER TABLE order_items ADD COLUMN measurement_unit VARCHAR(2) NULL;
