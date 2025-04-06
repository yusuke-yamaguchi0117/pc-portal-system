-- lesson_calendarテーブルのlesson_typeカラムを修正
ALTER TABLE lesson_calendar MODIFY COLUMN lesson_type VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci;