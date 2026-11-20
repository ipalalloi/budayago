#!/usr/bin/env bash
# From the verified release directory: bash prepare-empty-staging.sh STAGING_DB DB_USER HTTPS_ORIGIN
set -euo pipefail
set +x
umask 077
if [[ $# != 3 || ! -f public/heritage.php || ! -f SHA256SUMS.txt ]]; then
  echo "Run from the SCK-008 release and supply staging schema, DB user and HTTPS origin." >&2
  exit 2
fi
if [[ ! $1 =~ ^[a-zA-Z0-9_]+stg$ || ! $3 =~ ^https://stg\.[A-Za-z0-9.-]+$ ]]; then
  echo "Expected a staging schema ending in stg and an https://stg. origin." >&2
  exit 2
fi
phpbin=$(command -v php8.5 || command -v php)
"$phpbin" -r 'foreach (["pdo_mysql","fileinfo","mbstring","session"] as $ext) if (!extension_loaded($ext)) {fwrite(STDERR,"Missing CLI extension: $ext\n"); exit(2);} echo "PHP CLI ",PHP_VERSION,PHP_EOL;'
sha256sum --quiet -c SHA256SUMS.txt
if [[ -e config.php || -L config.php ]]; then echo "Existing config.php: inspect current staging state before retry." >&2; exit 2; fi

worker=$(mktemp)
trap 'rm -f -- "$worker"' EXIT
cat > "$worker" <<'PHP'
<?php
declare(strict_types=1);
[$script,$schema,$user,$origin,$siteHome]=$argv;
try {
    $cfg=require 'config.example.php';
    $cfg['DB_DSN']="mysql:host=127.0.0.1;port=3306;dbname=$schema;charset=utf8mb4";
    $cfg['DB_USER']=$user;
    $cfg['DB_PASS']=stream_get_contents(STDIN);
    $cfg['APP_ORIGIN']=$origin;
    $cfg['FORCE_HTTPS']='true';
    $cfg['HERITAGE_PUBLICATION_ENABLED']='false';
    $db=new PDO($cfg['DB_DSN'],$user,$cfg['DB_PASS'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $scalar=static fn(string $sql)=>$db->query($sql)->fetchColumn();
    if ($scalar('SELECT DATABASE()')!==$schema) throw new RuntimeException('Wrong schema.');
    $lock=$db->prepare('SELECT GET_LOCK(?,0)');
    $lock->execute(['sck008_init_'.substr(hash('sha256',$schema),0,36)]);
    if ((int)$lock->fetchColumn()!==1) throw new RuntimeException('Another setup is running.');
    $tables='SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()';
    if ((int)$scalar($tables)!==0) throw new RuntimeException('Schema already contains tables or views. Inspect before retry.');
    $files=['database/mysql.sql','database/20261119_heritage_additive.sql','database/20261120_heritage_rights.sql'];
    $plan=[];$names=[];
    foreach ($files as $file) {
        $text=preg_replace('/^\s*--[^\n]*$/m','',file_get_contents($file));
        foreach (explode(';',$text) as $stmt) {
            $stmt=trim($stmt);
            if ($stmt==='') continue;
            if (!preg_match('/^CREATE TABLE IF NOT EXISTS ([a-z_]+)\s*\(/i',$stmt,$m)) throw new RuntimeException("Unexpected statement in $file.");
            $plan[$file][]=$stmt;
            $names[]=$m[1];
        }
    }
    if(count(array_unique($names))!==12) throw new RuntimeException('Unexpected migration plan.');
    $private=$siteHome.'/budayago-staging-private/'.$schema;
    foreach ([$private,$private.'/media',$private.'/throttle'] as $dir) {
        if (!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Cannot create private directory.');
        if (!is_writable($dir)) throw new RuntimeException('Private directory is not writable.');
    }
    $cfg['HERITAGE_MEDIA_DIR']=$private.'/media';
    $cfg['LOGIN_RATE_LIMIT_DIR']=$private.'/throttle';
    $before=$db->query("SHOW CREATE DATABASE `$schema`")->fetch(PDO::FETCH_NUM);
    $snapshot=$private.'/preinstall-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
    if (file_put_contents($snapshot,"-- Initial staging schema; zero tables/views\n".$before[1].";\n")===false) throw new RuntimeException('Could not record initial empty schema.');
    $tmp=tempnam(getcwd(),'.sck008-');
    if ($tmp===false || dirname($tmp)!==getcwd()) throw new RuntimeException('Release directory not writable.');
    try {
        if (file_put_contents($tmp,"<?php\nreturn ".var_export($cfg,true).";\n")===false) throw new RuntimeException('Cannot stage private config.');
        foreach ($plan as $file=>$queries) {
            foreach ($queries as $q) $db->exec($q);
            echo basename($file)," OK\n";
        }
        sort($names);
        $actual=$db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
        sort($actual);
        $fk=(int)$scalar('SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()');
        if ($actual!==$names || $fk!==20) throw new RuntimeException('Unexpected final table list or FK count.');
        if (!link($tmp,'config.php')) throw new RuntimeException('Could not install config.php exclusively.');
        echo "SCK008_CONFIG_OK schema=$schema tables=12 foreign_keys=20 publication=false\n";
    } finally {unlink($tmp);}
} catch(Throwable $e) {
    $msg=$e instanceof PDOException ? 'SQLSTATE '.$e->getCode().' / MySQL '.($e->errorInfo[1]??'N/A') : $e->getMessage();
    fwrite(STDERR,"STOP: ".$msg."\n");
    exit(1);
}
PHP
IFS= read -rs -p 'Password database staging: ' db_pass </dev/tty
printf '\n' >/dev/tty
printf '%s' "$db_pass" | "$phpbin" "$worker" "$1" "$2" "$3" "$HOME"
