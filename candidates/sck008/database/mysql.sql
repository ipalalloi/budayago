-- Sulbar Culture Knowledge MVP 0.1. MySQL 8.0+ / MariaDB 10.5+; UTF8MB4.
-- Production migration: import to a fresh, empty database after taking backups.
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','curator','holder','teacher','student') NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS knowledge (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 summary TEXT NOT NULL,
 body MEDIUMTEXT NOT NULL,
 source_name VARCHAR(190) NOT NULL,
 source_ref VARCHAR(500) NOT NULL,
 scope ENUM('PUBLIC','MEMBERS','RESTRICTED') NOT NULL DEFAULT 'RESTRICTED',
 holder_id BIGINT UNSIGNED NOT NULL,
 author_id BIGINT UNSIGNED NOT NULL,
 status ENUM('draft','submitted','rejected','approved','published','withdrawn') NOT NULL DEFAULT 'draft',
 allow_learning TINYINT(1) NOT NULL DEFAULT 0,
 allow_online TINYINT(1) NOT NULL DEFAULT 0,
 allow_ai TINYINT(1) NOT NULL DEFAULT 0,
 allow_promo TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_knowledge_visibility(status,scope),
 INDEX idx_knowledge_holder(holder_id,status),
 FOREIGN KEY (holder_id) REFERENCES users(id),
 FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS lessons (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 knowledge_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 objective VARCHAR(500) NOT NULL,
 body MEDIUMTEXT NOT NULL,
 question TEXT NOT NULL,
 answer VARCHAR(250) NOT NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 author_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_lessons_status(status,knowledge_id),
 FOREIGN KEY (knowledge_id) REFERENCES knowledge(id),
 FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS attempts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 lesson_id BIGINT UNSIGNED NOT NULL,
 learner_id BIGINT UNSIGNED NOT NULL,
 correct TINYINT(1) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_attempts_user(learner_id,created_at),
 FOREIGN KEY (lesson_id) REFERENCES lessons(id),
 FOREIGN KEY (learner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 actor_id BIGINT UNSIGNED NULL,
 action VARCHAR(70) NOT NULL,
 entity_type VARCHAR(30) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 detail VARCHAR(250) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_created(created_at),
 FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
