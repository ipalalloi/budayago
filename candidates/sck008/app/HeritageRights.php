<?php
declare(strict_types=1);

function media_directory(): string {
    $dir = realpath(cfg('HERITAGE_MEDIA_DIR', SCK_ROOT . '/private_media'));
    $public = realpath(SCK_ROOT . '/public');
    if (!$dir || !$public || $dir === $public || str_starts_with($dir . DIRECTORY_SEPARATOR, $public . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Configure private media storage outside public');
    }
    return $dir;
}

function rights_reviewed(int $id): bool {
    return (bool)query('SELECT 1 FROM heritage_rights WHERE collection_id=? AND reviewer_id IS NOT NULL', [$id])->fetchColumn();
}

function register_rights(array $actor, int $id, string $evidence): void {
    $authority = trim((string)($_POST['authority_ref'] ?? ''));
    if (mb_strlen($authority) < 5 || mb_strlen($authority) > 180) {
        throw new DomainException('Isi referensi verifikasi kewenangan pemangku hak.');
    }
    query('INSERT INTO heritage_rights (collection_id, holder_id, authority_ref, evidence_ref) VALUES (?, ?, ?, ?)',
        [$id, $actor['id'], $authority, $evidence]);
    $selected = $_POST['public_assets'] ?? [];
    if (!is_array($selected)) {
        throw new DomainException('Pilihan media tidak valid.');
    }
    foreach (array_unique($selected) as $asset) {
        $grant = query('INSERT INTO heritage_asset_grants (asset_id) SELECT id FROM heritage_assets WHERE id=? AND collection_id=?',
            [(int)$asset, $id]);
        if ($grant->rowCount() !== 1) {
            throw new DomainException('Media tidak termasuk dalam koleksi.');
        }
    }
}

function claim_draft(int $author, string $key): ?int {
    if (!preg_match('/\A[a-f0-9]{32}\z/', $key)) {
        throw new DomainException('Muat ulang formulir untuk memulai draf.');
    }
    query('INSERT INTO heritage_draft_receipts (author_id,request_key) VALUES (?,?) ON DUPLICATE KEY UPDATE request_key=VALUES(request_key)', [$author,$key]);
    $existing = query('SELECT collection_id FROM heritage_draft_receipts WHERE author_id=? AND request_key=? FOR UPDATE', [$author,$key])->fetchColumn();
    return $existing ? (int)$existing : null;
}
