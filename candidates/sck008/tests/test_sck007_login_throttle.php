<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/LoginThrottle.php';
$dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'sck007_throttle_'.bin2hex(random_bytes(8));
if(!mkdir($dir,0700)){throw new RuntimeException('Could not create test directory');}
$n=0;
function check_security(bool $ok,string $name):void {global $n; if(!$ok){throw new RuntimeException('FAIL '.$name);} $n++;echo "PASS: $name\n";}
try {
    $limiter=new LoginThrottle($dir);
    for($i=0;$i<5;$i++){check_security($limiter->attempt('demo@example.invalid','127.0.0.1',fn()=>null)===null,'failed login '.($i+1));}
    try{$limiter->attempt('another@example.invalid','127.0.0.1',fn()=>1);check_security(false,'shared IP limit');}catch(DomainException){check_security(true,'shared IP limit across email/session');}
    try{$limiter->attempt('demo@example.invalid','127.0.0.2',fn()=>1);check_security(false,'shared email limit');}catch(DomainException){check_security(true,'shared email limit across IP/session');}
    check_security($limiter->attempt('new@example.invalid','127.0.0.2',fn()=>17)===17,'unrelated successful login');
    try{new LoginThrottle(dirname(__DIR__).'/public');check_security(false,'public storage rejected');}catch(RuntimeException){check_security(true,'public storage rejected');}
    try{new LoginThrottle('/sck007/this/path/is/not/configured');check_security(false,'missing directory fails closed');}catch(RuntimeException){check_security(true,'missing directory fails closed');}
    echo "SCK007_LOGIN_THROTTLE_TESTS=$n PASS\n";
} finally {
    @unlink($dir.DIRECTORY_SEPARATOR.'sck_login_throttle.json');
    @rmdir($dir);
}
