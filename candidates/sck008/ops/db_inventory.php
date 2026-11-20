<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || $argc!==2) {fwrite(STDERR,"php ops/db_inventory.php EXPECTED_STAGING_SCHEMA\n");exit(2);}
require dirname(__DIR__).'/app/bootstrap.php';
$expected=$argv[1];
if (!preg_match('/^[A-Za-z0-9_]+$/D',$expected) || in_array(strtolower($expected),['sck_uat','sck_ci','mysql','sys','performance_schema','information_schema'],true)) exit(2);
$actual=query('SELECT DATABASE()')->fetchColumn();
if ($actual!==$expected) {fwrite(STDERR,"Nama basis data berbeda.\n");exit(3);}
echo "Schema ",$actual," | Engine ",query('SELECT VERSION()')->fetchColumn(),PHP_EOL;
foreach(query("SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME") as $r) echo implode("\t",$r),PHP_EOL;
foreach(query("SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,COLUMN_NAME") as $r) echo implode("\t",$r),PHP_EOL;
foreach(query("SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME,SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX") as $r) echo implode("\t",$r),PHP_EOL;
