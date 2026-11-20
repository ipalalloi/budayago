-- Run only after verified backup, against new staging fixture first; additive, not a reset.
CREATE TABLE IF NOT EXISTS heritage_collections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL, category ENUM('Tutur','Manuskrip','Objek','Tradisi') NOT NULL,
 region VARCHAR(160) NOT NULL DEFAULT '', description TEXT NOT NULL,
 author_id BIGINT UNSIGNED NOT NULL, holder_id BIGINT UNSIGNED NOT NULL,
 status ENUM('DRAFT','CONSENT_PENDING','CURATION','NEEDS_REVISION','REJECTED','PUBLISHED','WITHDRAWN') NOT NULL DEFAULT 'DRAFT',
 record_consent TINYINT(1) NOT NULL DEFAULT 0, archive_consent TINYINT(1) NOT NULL DEFAULT 0, public_consent TINYINT(1) NOT NULL DEFAULT 0,
 consent_evidence_ref VARCHAR(250) NOT NULL DEFAULT '',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_heritage_public(status,category), INDEX idx_heritage_author(author_id),
 FOREIGN KEY(author_id) REFERENCES users(id), FOREIGN KEY(holder_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS heritage_assets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, collection_id BIGINT UNSIGNED NOT NULL,
 storage_key CHAR(32) NOT NULL UNIQUE, original_name VARCHAR(190) NOT NULL,
 mime VARCHAR(90) NOT NULL, size_bytes BIGINT UNSIGNED NOT NULL,
 visibility ENUM('PRIVATE','PUBLIC') NOT NULL DEFAULT 'PRIVATE', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_heritage_asset(collection_id,visibility), FOREIGN KEY(collection_id) REFERENCES heritage_collections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS heritage_contributions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, collection_id BIGINT UNSIGNED NOT NULL,
 author_id BIGINT UNSIGNED NOT NULL, text_body TEXT NOT NULL,
 status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(collection_id) REFERENCES heritage_collections(id), FOREIGN KEY(author_id) REFERENCES users(id),
 INDEX idx_heritage_contribution(collection_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
