--
-- PostgreSQL database dump for EarlyBird Backend
-- Version: 16
-- Generated: 2025-10-10
--

-- =========================================================
-- 🔹 CREATE DATABASE (if not exists)
-- =========================================================
DO
$$
BEGIN
   IF NOT EXISTS (SELECT FROM pg_database WHERE datname = 'database') THEN
      PERFORM dblink_exec('dbname=postgres', 'CREATE DATABASE database');
   END IF;
END
$$;

-- =========================================================
-- 🔹 TABLE: user
-- =========================================================
CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(255),
    lastname VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255),
    code_pin INTEGER
);

-- ✅ Données d’exemple pour la table user

INSERT INTO "user" (firstname, lastname, email, phone_number, password, role, code_pin) VALUES
('Alice', 'Durand', 'alice.durand@example.com', '0601020304', '$2y$10$abcd1234hash', 'user', 1111),
('Bob', 'Martin', 'bob.martin@example.com', '0605060708', '$2y$10$efgh5678hash', 'user', 2222),
('Clara', 'Admin', 'clara.admin@example.com', '0610101010', '$2y$10$ijkl9012hash', 'admin', 9999),
('David', 'Lopez', 'david.lopez@example.com', '0602030405', '$2y$10$mnop3456hash', 'user', 3333),
('Emma', 'Dubois', 'emma.dubois@example.com', '0603040506', '$2y$10$qrst7890hash', 'user', 4444),
('Fanny', 'Petit', 'fanny.petit@example.com', '0604050607', '$2y$10$uvwx1234hash', 'user', 5555),
('Gabriel', 'Roux', 'gabriel.roux@example.com', '0605060708', '$2y$10$yzab5678hash', 'user', 6666),
('Hugo', 'Lefevre', 'hugo.lefevre@example.com', '0606070809', '$2y$10$cdef9012hash', 'user', 7777),
('Isabelle', 'Moreau', 'isabelle.moreau@example.com', '0607080910', '$2y$10$ghij3456hash', 'user', 8888);

-- =========================================================
-- 🔹 TABLE: team
-- =========================================================
CREATE TABLE IF NOT EXISTS "team" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    manager_id INTEGER REFERENCES "user"(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS "team_member" (
    team_id INTEGER REFERENCES "team"(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES "user"(id) ON DELETE CASCADE,
    PRIMARY KEY (team_id, user_id)
);

-- ✅ Données d’exemple pour la table team
INSERT INTO "team" (id, name, description, manager_id) VALUES
(1, 'Backend Team', 'Responsible for APIs and data layer', 3),
(2, 'DevOps Team', 'Handles deployment and CI/CD pipelines', 1),
(3, 'AI Team', 'Focuses on machine learning models', 2),
(4, 'All Users', 'All users in the system', 3);

-- ✅ Données d’exemple pour la table team_member
-- Backend Team: Alice (1), Bob (2)
INSERT INTO team_member (team_id, user_id) VALUES (1, 1), (1, 2);
-- DevOps Team: Clara (3), Bob (2)
INSERT INTO team_member (team_id, user_id) VALUES (2, 3), (2, 2);
-- AI Team: Alice (1), Clara (3)
INSERT INTO team_member (team_id, user_id) VALUES (3, 1), (3, 3);
-- All Users: Alice (1), Bob (2), Clara (3)
-- All Users: Alice (1), Bob (2), Clara (3), David (4), Emma (5), Fanny (6), Gabriel (7), Hugo (8), Isabelle (9)
INSERT INTO team_member (team_id, user_id) VALUES (4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9);


-- =========================================================
-- 🔹 TABLE: clock
-- =========================================================
CREATE TABLE IF NOT EXISTS clock (
    id SERIAL PRIMARY KEY,
    timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
    type VARCHAR(50) NOT NULL,
    user_id INTEGER REFERENCES "user"(id) ON DELETE CASCADE
);
-- ✅ Données d’exemple pour la table clock