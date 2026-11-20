<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Policy.php';
$knowledge = ['author_id'=>11,'holder_id'=>22];
$cases = [
  ['admin can inspect', ['id'=>99,'role'=>'admin'], true],
  ['author curator can inspect', ['id'=>11,'role'=>'curator'], true],
  ['other curator denied', ['id'=>12,'role'=>'curator'], false],
  ['assigned holder can inspect', ['id'=>22,'role'=>'holder'], true],
  ['other holder denied', ['id'=>23,'role'=>'holder'], false],
  ['teacher denied', ['id'=>33,'role'=>'teacher'], false],
  ['student denied', ['id'=>44,'role'=>'student'], false],
  ['guest denied', null, false],
];
foreach ($cases as [$name,$actor,$expected]) {
    if (Policy::canReadSourceReference($actor,$knowledge) !== $expected) {
        fwrite(STDERR,'FAIL: '.$name.PHP_EOL);
        exit(1);
    }
    echo 'PASS: '.$name.PHP_EOL;
}
echo 'SCK006_PRIVATE_REF_TESTS=8/8 PASS'.PHP_EOL;