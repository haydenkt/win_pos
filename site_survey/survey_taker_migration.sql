-- Run once before deploying survey-taker tracking. Existing records stay intact.
ALTER TABLE site_surveys ADD COLUMN created_by INT NULL;
