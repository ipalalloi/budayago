<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/Policy.php';
$passed = 0;
function check(bool $condition,string $name):void {global $passed;if(!$condition){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";$passed++;}
function fails(callable $callback,string $name):void {try{$callback();}catch(DomainException){check(true,$name);return;}check(false,$name);}
$admin=['id'=>1,'role'=>'admin'];$author=['id'=>2,'role'=>'curator'];$other=['id'=>4,'role'=>'curator'];$holder=['id'=>3,'role'=>'holder'];$student=['id'=>5,'role'=>'student'];
$k=['id'=>9,'author_id'=>2,'holder_id'=>3,'status'=>'draft','scope'=>'RESTRICTED','allow_learning'=>0,'allow_online'=>0];
check(Policy::canEdit($author,$k),'author can edit draft');check(!Policy::canEdit($other,$k),'other curator cannot edit');check(!Policy::canReadKnowledge($student,$k),'draft not visible to student');
$k['status']='submitted';check(Policy::canReview($holder,$k),'assigned holder can review');check(!Policy::canReview($admin,$k),'admin cannot impersonate holder');
$k['status']='approved';$k['scope']='PUBLIC';$k['allow_learning']=1;$k['allow_online']=0;
check(!Policy::canPublish($admin,$k),'cannot publish public content without online consent');
$k['allow_online']=1;check(Policy::canPublish($admin,$k),'approved rights allow admin publication');
$k['status']='published';check(Policy::canReadKnowledge(null,$k),'public published content accessible anonymously');
$k['scope']='MEMBERS';check(!Policy::canReadKnowledge(null,$k),'members-only not anonymously accessible');check(Policy::canReadKnowledge($student,$k),'members-only accessible to authenticated student');
$k['scope']='RESTRICTED';check(!Policy::canReadKnowledge($student,$k),'restricted content not accessible to student');
$k['status']='withdrawn';check(!Policy::canReadKnowledge($student,$k),'withdrawn content inaccessible');check(!Policy::canEdit($author,$k),'withdrawn source cannot be republished through editing');
$l=['status'=>'published','knowledge_status'=>'published','scope'=>'PUBLIC','allow_learning'=>1];check(Policy::canViewLesson($student,$l),'student can read published lesson');$l['knowledge_status']='withdrawn';check(!Policy::canViewLesson($student,$l),'revocation hides published lesson');
check(Policy::normalizeAnswer('  Pengetahuan   Budaya  ')==='pengetahuan budaya','answer whitespace/case normalization');
check(Policy::validEmail('test@example.org')&&!Policy::validEmail('bad.example'),'email validation');
fails(fn()=>Policy::validateKnowledge('x','short','short','xx','x','PUBLIC'),'invalid knowledge rejected');
check(escape('<script>')==='&lt;script&gt;','html escaping');
echo "UNIT TESTS: $passed passed\n";
