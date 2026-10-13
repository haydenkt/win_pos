CREATE TABLE IF NOT EXISTS survey_mobile_sync (
 draft_id VARCHAR(64) PRIMARY KEY,
 user_id INT NOT NULL,
 payload_hash CHAR(64) NOT NULL,
 survey_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_mobile_survey (survey_id)
);
CREATE TABLE IF NOT EXISTS survey_mobile_photos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 survey_id INT NOT NULL,
 mime_type VARCHAR(40) NOT NULL,
 image_data MEDIUMBLOB NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_mobile_photo_survey (survey_id)
);
