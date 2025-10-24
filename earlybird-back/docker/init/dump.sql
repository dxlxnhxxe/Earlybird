--
-- PostgreSQL database dump for EarlyBird Backend
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
    id SERIAL PRIMARY KEY,
    team_id INTEGER REFERENCES "team"(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES "user"(id) ON DELETE CASCADE,
    start_time TIME,
    end_time TIME,
    UNIQUE (team_id, user_id)
);

-- ✅ Données d’exemple pour la table team
INSERT INTO "team" (name, description, manager_id) VALUES
('Backend Team', 'Responsible for APIs and data layer', 3),
('DevOps Team', 'Handles deployment and CI/CD pipelines', 1),
('AI Team', 'Focuses on machine learning models', 2),
('All Users', 'All users in the system', 3);

INSERT INTO team_member (team_id, user_id, start_time, end_time) VALUES
    (1, 1, '09:00', '17:00'),
    (1, 2, '09:30', '18:00');
INSERT INTO team_member (team_id, user_id, start_time, end_time) VALUES
    (2, 3, '10:00', '19:00'),
    (2, 2, '09:00', '17:00');
INSERT INTO team_member (team_id, user_id, start_time, end_time) VALUES
    (3, 1, '08:00', '16:00'),
    (3, 3, '09:00', '17:00');
INSERT INTO team_member (team_id, user_id, start_time, end_time) VALUES
    (4, 1, '09:00', '17:00'),
    (4, 2, '09:00', '17:00'),
    (4, 3, '09:00', '17:00'),
    (4, 4, '09:00', '17:00'),
    (4, 5, '09:00', '17:00'),
    (4, 6, '09:00', '17:00'),
    (4, 7, '09:00', '17:00'),
    (4, 8, '09:00', '17:00'),
    (4, 9, '09:00', '17:00');



-- =========================================================
--  TABLE: clock
-- =========================================================
CREATE TABLE IF NOT EXISTS clock (
    id SERIAL PRIMARY KEY,
    timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
    type VARCHAR(50) NOT NULL,
    team_member_id INTEGER NOT NULL REFERENCES team_member(id) ON DELETE CASCADE,
    start_time TIME,
    end_time TIME
);
--  Données d'exemple pour la table clock

-- =========================================================
--  TABLE: refresh_tokens (JWT Refresh Token Bundle)
-- =========================================================
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id SERIAL PRIMARY KEY,
    refresh_token VARCHAR(128) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL,
    valid TIMESTAMP NOT NULL
);

-- Index pour optimiser les recherches par token
CREATE INDEX IF NOT EXISTS idx_refresh_token ON refresh_tokens(refresh_token);
CREATE INDEX IF NOT EXISTS idx_refresh_token_username ON refresh_tokens(username);

-- ✅ Table refresh_tokens créée pour la gestion des tokens JWT