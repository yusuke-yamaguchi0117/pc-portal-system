ALTER TABLE lesson_slots ADD COLUMN day_of_week TINYINT NOT NULL AFTER date, ADD INDEX idx_day_of_week (day_of_week);
