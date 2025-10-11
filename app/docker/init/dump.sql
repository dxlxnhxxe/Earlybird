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
('Clara', 'Admin', 'clara.admin@example.com', '0610101010', '$2y$10$ijkl9012hash', 'admin', 9999);

-- =========================================================
-- 🔹 TABLE: team
-- =========================================================
CREATE TABLE IF NOT EXISTS "team" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    members JSON,
    manager VARCHAR(255)
);

-- ✅ Données d’exemple pour la table team
INSERT INTO "team" (name, description, members, manager) VALUES
('Backend Team', 'Responsible for APIs and data layer', '["Alice", "Bob"]', 'Clara Admin'),
('DevOps Team', 'Handles deployment and CI/CD pipelines', '["Clara", "Bob"]', 'Alice Durand'),
('AI Team', 'Focuses on machine learning models', '["Alice", "Clara"]', 'Bob Martin');


-- =========================================================
-- 🔹 TABLE: cloc     k
-- =========================================================
CREATE TABLE IF NOT EXISTS clock (
    id SERIAL PRIMARY KEY,
    timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
    type VARCHAR(50) NOT NULL,
    user_id INTEGER REFERENCES "user"(id) ON DELETE CASCADE
);
-- ✅ Données d’exemple pour la table clock