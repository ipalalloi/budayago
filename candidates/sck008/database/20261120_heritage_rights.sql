-- Apply after 20261119. Existing Alpha records remain private until reviewed.
CREATE TABLE IF NOT EXISTS heritage_rights (
 collection_id BIGINT UNSIGNED PRIMARY KEY,
 holder_id BIGINT UNSIGNED NOT NULL,
 authority_ref VARCHAR(180) NOT NULL,
 evidence_ref VARCHAR(250) NOT NULL,
 granted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewer_id BIGINT UNSIGNED NULL,
 reviewed_at TIMESTAMP NULL,
 FOREIGN KEY (collection_id) REFERENCES heritage_collections(id),
 FOREIGN KEY (holder_id) REFERENCES users(id),
 FOREIGN KEY (reviewer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS heritage_asset_grants (
 asset_id BIGINT UNSIGNED PRIMARY KEY,
 FOREIGN KEY (asset_id) REFERENCES heritage_assets(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS heritage_draft_receipts (
 author_id BIGINT UNSIGNED NOT NULL,
 request_key CHAR(32) NOT NULL,
 collection_id BIGINT UNSIGNED NULL UNIQUE,
 PRIMARY KEY (author_id,request_key),
 FOREIGN KEY (author_id) REFERENCES users(id),
 FOREIGN KEY (collection_id) REFERENCES heritage_collections(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS heritage_upload_receipts (
 collection_id BIGINT UNSIGNED NOT NULL,
 content_sha256 CHAR(64) NOT NULL,
 asset_id BIGINT UNSIGNED NOT NULL UNIQUE,
 capture_ref VARCHAR(180) NOT NULL,
 PRIMARY KEY (collection_id,content_sha256),
 FOREIGN KEY (asset_id) REFERENCES heritage_assets(id),
 FOREIGN KEY (collection_id) REFERENCES heritage_collections(id)
) ENGINE=InnoDB;
