UPDATE lesson_slots ls JOIN students s ON ls.student_id = s.id SET ls.type = 'transfer' WHERE (ls.lesson_day != s.lesson_day OR ls.start_time != s.lesson_time) AND ls.type = 'regular';
