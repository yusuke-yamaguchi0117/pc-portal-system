CREATE TABLE `lesson_posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `lesson_slot_id` int(11),
    `theme` VARCHAR(255) NOT NULL,
    `comment` TEXT NOT NULL,
    `photo_path` VARCHAR(255),
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_lesson_slot_id` (`lesson_slot_id`),
    CONSTRAINT `fk_lesson_posts_student_id`
        FOREIGN KEY (`student_id`)
        REFERENCES `students` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_lesson_posts_lesson_slot_id`
        FOREIGN KEY (`lesson_slot_id`)
        REFERENCES `lesson_slots` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;