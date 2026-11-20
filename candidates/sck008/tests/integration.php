<?php
declare(strict_types=1);
// Run ONLY against a disposable test database; never point to a production database.
require __DIR__ . '/sck_ci_guard.php';
sck_ci_preflight();
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/Policy.php';
require dirname(__DIR__) . '/app/CultureService.php';
$pdo=database();$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
sck_ci_assert_database($pdo);
$tests=0;
function ok(bool $b,string $n):void {global $tests;if(!$b){throw new RuntimeException('FAILED: '.$n);} $tests++;echo 'PASS: '.$n.PHP_EOL;}
function denied(callable $fn,string $n):void {try{$fn();}catch(DomainException){ok(true,$n);return;}ok(false,$n);}
$roles=['admin','curator','holder','teacher','student'];$accounts=[];
foreach($roles as $r){$s=$pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');$s->execute(['Test '.$r,$r.'@example.test',password_hash('Test-only-Password-2026',PASSWORD_DEFAULT),$r]);$accounts[$r]=['id'=>(int)$pdo->lastInsertId(),'role'=>$r];}
$svc=new CultureService($pdo);
$input=['title'=>'TEST ONLY: Materi uji generik','summary'=>'Konten simulasi aman, bukan informasi budaya nyata.','body'=>'Isi generik hanya dipakai untuk menguji alur persetujuan perangkat lunak, tanpa pengetahuan adat.','source_name'=>'Simulated test source','source_ref'=>'TEST_ONLY_DO_NOT_PUBLISH','scope'=>'PUBLIC','holder_id'=>$accounts['holder']['id']];
$id=$svc->saveKnowledge($accounts['curator'],null,$input);
$k=$pdo->query('SELECT * FROM knowledge WHERE id='.$id)->fetch();
ok($k['status']==='draft','knowledge created as draft');ok(!Policy::canReadKnowledge(null,$k),'unapproved cultural knowledge not public');
denied(fn()=>$svc->submitKnowledge($accounts['student'],$id),'student cannot submit editorial draft');
$svc->submitKnowledge($accounts['curator'],$id);
denied(fn()=>$svc->reviewKnowledge($accounts['admin'],$id,true,['allow_learning'=>1,'allow_online'=>1],''),'admin cannot approve on behalf of holder');
denied(fn()=>$svc->reviewKnowledge($accounts['holder'],$id,true,['allow_learning'=>1],''),'online consent required for public');
$svc->reviewKnowledge($accounts['holder'],$id,true,['allow_learning'=>1,'allow_online'=>1], 'Approved for test');
$k=$pdo->query('SELECT * FROM knowledge WHERE id='.$id)->fetch();
ok($k['status']==='approved'&&(int)$k['allow_online']===1,'holder consent saved with scope');
denied(fn()=>$svc->publishKnowledge($accounts['curator'],$id),'curator cannot publish');
$svc->publishKnowledge($accounts['admin'],$id);
$k=$pdo->query('SELECT * FROM knowledge WHERE id='.$id)->fetch();ok(Policy::canReadKnowledge(null,$k),'published public knowledge visible');
$lessonInput=['knowledge_id'=>$id,'title'=>'TEST ONLY: Lesson','objective'=>'Understand an abstract placeholder workflow safely','body'=>'This generic test content verifies that only approved learning material becomes available.','question'=>'Which word is first in TEST ONLY?','answer'=>'TEST'];
$lid=$svc->saveLesson($accounts['curator'],null,$lessonInput);
$l=$pdo->query('SELECT l.*,k.status knowledge_status,k.scope,k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.id='.$lid)->fetch();
ok(!Policy::canViewLesson($accounts['student'],$l),'draft lesson inaccessible');
denied(fn()=>$svc->publishLesson($accounts['curator'],$lid),'curator cannot publish lesson');
$svc->publishLesson($accounts['admin'],$lid);
ok($svc->submitAnswer($accounts['student'],$lid,'  test  '),'student answer accepted and correct');
ok(!$svc->submitAnswer($accounts['student'],$lid,'wrong'),'incorrect answer recorded');
$cnt=(int)$pdo->query('SELECT COUNT(*) FROM attempts')->fetchColumn();ok($cnt===2,'student quiz attempts persisted');
denied(fn()=>$svc->submitAnswer($accounts['curator'],$lid,'TEST'),'curator cannot submit a student answer');
$svc->withdrawKnowledge($accounts['holder'],$id,'Consent revoked for test');
$l=$pdo->query('SELECT l.*,k.status knowledge_status,k.scope,k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.id='.$lid)->fetch();
ok(!Policy::canViewLesson($accounts['student'],$l),'holder revocation immediately hides lesson');
denied(fn()=>$svc->submitAnswer($accounts['student'],$lid,'TEST'),'student cannot use revoked lesson');
$input['scope']='RESTRICTED';$id2=$svc->saveKnowledge($accounts['curator'],null,$input);$svc->submitKnowledge($accounts['curator'],$id2);$svc->reviewKnowledge($accounts['holder'],$id2,true,['allow_learning'=>1],'Approved restricted archive');
denied(fn()=>$svc->publishKnowledge($accounts['admin'],$id2),'restricted archival knowledge cannot publish');
$events=(int)$pdo->query('SELECT COUNT(*) FROM audit_events')->fetchColumn();ok($events>=7,'audit events persisted');
echo "INTEGRATION TESTS: $tests passed\n";
