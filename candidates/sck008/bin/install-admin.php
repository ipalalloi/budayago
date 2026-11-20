<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require dirname(__DIR__) . '/app/bootstrap.php';
$email = strtolower(trim((string)getenv('ADMIN_EMAIL')));
$pass = (string)getenv('ADMIN_PASSWORD');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass)<12 || strlen($pass)>72) {
    fwrite(STDERR,"Periksa ADMIN_EMAIL dan ADMIN_PASSWORD (12–72 byte).\n"); exit(2);
}
$db = database();
if ((int)query("SELECT GET_LOCK('budayago_first_admin',5)")->fetchColumn() !== 1) { exit(3); }
try {
    if (query("SELECT 1 FROM users WHERE role='admin' LIMIT 1")->fetchColumn()) {
        fwrite(STDERR,"Administrator sudah tersedia.\n"); exit(2);
    }
    $db->beginTransaction();
    query("INSERT INTO users(name,email,password_hash,role) VALUES('Administrator',?,?,'admin')", [$email,password_hash($pass,PASSWORD_DEFAULT)]);
    $id = (int)$db->lastInsertId();
    audit_event($id,'admin_provision','users',$id);
    $db->commit();
    echo "Administrator pertama dibuat.\n";
} finally {
    query("SELECT RELEASE_LOCK('budayago_first_admin')");
}
