<?php
declare(strict_types=1);
// Only a fresh local disposable schema. Provision the first synthetic admin beforehand.
if (PHP_SAPI !== 'cli' || getenv('DB_DSN') !== 'mysql:host=127.0.0.1;port=33781;dbname=sck008_fixture;charset=utf8mb4' || is_file(dirname(__DIR__).'/config.php')) {exit(2);}
require dirname(__DIR__).'/app/bootstrap.php';
if(query('SELECT DATABASE()')->fetchColumn()!=='sck008_fixture' || (int)query('SELECT COUNT(*) FROM users')->fetchColumn()!==1 || !getenv('ADMIN_PASSWORD')){exit(2);}
database()->beginTransaction();
foreach(['teacher','holder','curator','student'] as $role){
    query('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',['Fixture '.$role,$role.'@example.test',password_hash(getenv('ADMIN_PASSWORD'),PASSWORD_DEFAULT),$role]);
}
query("INSERT INTO knowledge(title,summary,body,source_name,source_ref,scope,holder_id,author_id,status,allow_learning,allow_online) SELECT 'Fixture knowledge','fixture','synthetic content','fixture','private fixture reference','PUBLIC',h.id,c.id,'published',1,1 FROM users h JOIN users c ON c.role='curator' WHERE h.role='holder'");
query("INSERT INTO lessons(knowledge_id,title,objective,body,question,answer,status,author_id) SELECT id,'Synthetic lesson','Test objective','Synthetic content','Fixture question?','fixture-exact-327','published',author_id FROM knowledge");
database()->commit();

try {
    query('INSERT INTO heritage_asset_grants(asset_id) VALUES (999999)');
    throw new RuntimeException('Invalid foreign key was accepted.');
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1]??0)!==1452) throw $e;
    echo "PASS nonexistent asset rejected by InnoDB foreign key.\n";
}
