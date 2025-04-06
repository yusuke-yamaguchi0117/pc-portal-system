ALTER TABLE lesson_slots ADD COLUMN type ENUM('regular', 'transfer') NOT NULL DEFAULT 'regular' AFTER status;
