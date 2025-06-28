USE kanban_db;

-- Default users
-- Passwords are: adminpass, managerpass, userpass
INSERT INTO users (username, password_hash, email, role) VALUES
('admin', '$2y$10$H.OTeW5L4M3T8wWTm6dG/e.gqj3L6G5l/Qf.E.o.Q.o.Q.o.Q.o.Q', 'admin@kanban.local', 'admin'),
('manager', '$2y$10$H.OTeW5L4M3T8wWTm6dG/e.gqj3L6G5l/Qf.E.o.Q.o.Q.o.Q.o.Q', 'manager@kanban.local', 'manager'),
('user', '$2y$10$H.OTeW5L4M3T8wWTm6dG/e.gqj3L6G5l/Qf.E.o.Q.o.Q.o.Q.o.Q', 'user@kanban.local', 'user');

-- Sample board
INSERT INTO boards (name, description, owner_id) VALUES
('Project Phoenix', 'A top-secret project to rebuild the infrastructure from scratch.', 1);

-- Sample columns for the board
INSERT INTO columns (board_id, name, column_order) VALUES
(1, 'To Do', 1),
(1, 'In Progress', 2),
(1, 'Done', 3);

-- Sample tasks
INSERT INTO tasks (column_id, title, description, due_date, priority, task_order) VALUES
(1, 'Design the new database schema', 'The schema must be normalized and efficient.', '2025-07-15', 1, 1),
(1, 'Develop the authentication API', 'Implement JWT-based authentication with refresh tokens.', '2025-07-20', 1, 2),
(2, 'Build the frontend components', 'Use Vue 3 and Tailwind CSS.', '2025-07-25', 0, 1);
