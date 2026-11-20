<?php
declare(strict_types=1);
require_once __DIR__ . '/Policy.php';

final class LessonUnavailable extends DomainException {}
final class CultureService {
    public function __construct(private PDO $db) {}
    private function findKnowledge(int $id): array {
        $s=$this->db->prepare('SELECT * FROM knowledge WHERE id = ? FOR UPDATE'); $s->execute([$id]);
        $item=$s->fetch(PDO::FETCH_ASSOC); if (!$item) { throw new DomainException('Pengetahuan tidak ditemukan.'); }
        return $item;
    }
    private function run(string $sql,array $data=[]): PDOStatement { $s=$this->db->prepare($sql);$s->execute($data);return $s; }
    private function log(int $uid,string $event,string $entity,int $id,string $detail=''): void {
        $this->run('INSERT INTO audit_events (actor_id,action,entity_type,entity_id,detail) VALUES (?,?,?,?,?)',[$uid,$event,$entity,$id,substr($detail,0,250)]);
    }
    public function saveKnowledge(array $u, ?int $id, array $input): int {
        if (!in_array($u['role'],['admin','curator'],true)) {throw new DomainException('Tidak berwenang menulis.');}
        $title=trim((string)($input['title']??'')); $summary=trim((string)($input['summary']??''));
        $body=trim((string)($input['body']??''));$source=trim((string)($input['source_name']??''));
        $ref=trim((string)($input['source_ref']??''));$scope=(string)($input['scope']??'RESTRICTED');
        Policy::validateKnowledge($title,$summary,$body,$source,$ref,$scope);
        $holder=(int)($input['holder_id']??0);
        $s=$this->run("SELECT id FROM users WHERE id=? AND role='holder' AND active=1",[$holder]);
        if (!$s->fetch()) {throw new DomainException('Pilih akun pemangku budaya yang aktif.');}
        $this->db->beginTransaction();
        try {
            if ($id) {
                $k=$this->findKnowledge($id);
                if (!Policy::canEdit($u,$k)) { throw new DomainException('Hanya draf milik Anda yang dapat diubah.'); }
                $this->run("UPDATE knowledge SET title=?,summary=?,body=?,source_name=?,source_ref=?,scope=?,holder_id=?,status='draft',allow_learning=0,allow_online=0,allow_ai=0,allow_promo=0,updated_at=CURRENT_TIMESTAMP WHERE id=?",[$title,$summary,$body,$source,$ref,$scope,$holder,$id]);
            } else {
                $this->run("INSERT INTO knowledge (title,summary,body,source_name,source_ref,scope,holder_id,author_id,status) VALUES (?,?,?,?,?,?,?,?,'draft')",[$title,$summary,$body,$source,$ref,$scope,$holder,$u['id']]);
                $id=(int)$this->db->lastInsertId();
            }
            $this->log((int)$u['id'],'knowledge_save','knowledge',$id);
            $this->db->commit();return $id;
        } catch(Throwable $e) {$this->db->rollBack();throw $e;}
    }
    public function submitKnowledge(array $u,int $id): void {
        $this->db->beginTransaction();try {
            $k=$this->findKnowledge($id);
            if (!Policy::canEdit($u,$k) || !in_array($k['status'],['draft','rejected'],true)) {throw new DomainException('Tidak dapat mengirim materi ini.');}
            $this->run("UPDATE knowledge SET status='submitted',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
            $this->log((int)$u['id'],'knowledge_submit','knowledge',$id);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function reviewKnowledge(array $u,int $id,bool $approve,array $grants,string $reason): void {
        $this->db->beginTransaction();try {
            $k=$this->findKnowledge($id);
            if (!Policy::canReview($u,$k)) {throw new DomainException('Hanya pemangku budaya yang ditugaskan dapat memutuskan.');}
            $learning=$approve && !empty($grants['allow_learning']) ? 1:0;
            $online=$approve && !empty($grants['allow_online']) ? 1:0;
            $ai=$approve && !empty($grants['allow_ai']) ? 1:0;
            $promo=$approve && !empty($grants['allow_promo']) ? 1:0;
            if ($approve && !$learning) {throw new DomainException('Izin penggunaan pembelajaran wajib diberikan secara eksplisit untuk menyetujui.');}
            if ($approve && $k['scope']==='PUBLIC' && !$online) {throw new DomainException('Izin tayang daring wajib diberikan untuk akses publik.');}
            if (!$approve && strlen(trim($reason))<5) {throw new DomainException('Sertakan alasan koreksi minimal lima karakter.');}
            $newStatus=$approve?'approved':'rejected';
            $this->run('UPDATE knowledge SET status=?,allow_learning=?,allow_online=?,allow_ai=?,allow_promo=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$newStatus,$learning,$online,$ai,$promo,$id]);
            $this->log((int)$u['id'],$approve?'knowledge_approve':'knowledge_reject','knowledge',$id,$reason);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function publishKnowledge(array $u,int $id):void {
        $this->db->beginTransaction();try {
            $k=$this->findKnowledge($id);
            if(!Policy::canPublish($u,$k)){throw new DomainException('Materi belum disetujui atau aksesnya dibatasi.');}
            $this->run("UPDATE knowledge SET status='published',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
            $this->log((int)$u['id'],'knowledge_publish','knowledge',$id);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function withdrawKnowledge(array $u,int $id,string $reason):void {
        $this->db->beginTransaction();try {
            $k=$this->findKnowledge($id);
            if(!Policy::canWithdraw($u,$k)){throw new DomainException('Pencabutan hanya oleh pemangku terkait.');}
            if(strlen(trim($reason))<5){throw new DomainException('Berikan alasan pencabutan.');}
            $this->run("UPDATE knowledge SET status='withdrawn',allow_learning=0,allow_online=0,allow_ai=0,allow_promo=0,updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
            $this->log((int)$u['id'],'knowledge_withdraw','knowledge',$id,$reason);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function saveLesson(array $u, ?int $id,array $input):int {
        if(!in_array($u['role'],['admin','curator'],true)){throw new DomainException('Tidak dapat membuat pelajaran.');}
        $knowledgeId=(int)($input['knowledge_id']??0);
        $title=trim((string)($input['title']??''));$objective=trim((string)($input['objective']??''));
        $body=trim((string)($input['body']??''));$question=trim((string)($input['question']??''));$answer=trim((string)($input['answer']??''));
        if(strlen($title)<4||strlen($title)>180||strlen($objective)<8||strlen($objective)>500||strlen($body)<20||strlen($body)>20000||strlen($question)<6||strlen($question)>1000||strlen($answer)<1||strlen($answer)>250){throw new DomainException('Materi pelajaran, tujuan, dan pertanyaan tidak lengkap.');}
        $this->db->beginTransaction();try {
            $k=$this->findKnowledge($knowledgeId);
            if($k['status']!=='published'||(int)$k['allow_learning']!==1){throw new DomainException('Pelajaran harus merujuk pengetahuan terbit berizin.');}
            if($id){
                $s=$this->run('SELECT * FROM lessons WHERE id=?',[$id]);$lesson=$s->fetch(PDO::FETCH_ASSOC);
                if(!$lesson||$lesson['status']!=='draft'||($u['role']!=='admin'&&(int)$lesson['author_id']!==(int)$u['id'])){throw new DomainException('Pelajaran tidak dapat diubah.');}
                $this->run('UPDATE lessons SET knowledge_id=?,title=?,objective=?,body=?,question=?,answer=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$knowledgeId,$title,$objective,$body,$question,$answer,$id]);
            }else{
                $this->run("INSERT INTO lessons (knowledge_id,title,objective,body,question,answer,status,author_id) VALUES (?,?,?,?,?,?,'draft',?)",[$knowledgeId,$title,$objective,$body,$question,$answer,$u['id']]);$id=(int)$this->db->lastInsertId();
            }
            $this->log((int)$u['id'],'lesson_save','lesson',$id);$this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function publishLesson(array $u,int $id):void {
        if($u['role']!=='admin'){throw new DomainException('Hanya admin dapat menerbitkan pelajaran.');}
        $this->db->beginTransaction();try{
            $s=$this->run('SELECT l.status, k.status knowledge_status, k.allow_learning, k.scope FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.id=?',[$id]);$l=$s->fetch(PDO::FETCH_ASSOC);
            if(!$l||$l['status']!=='draft'||$l['knowledge_status']!=='published'||$l['scope']==='RESTRICTED'||(int)$l['allow_learning']!==1){throw new DomainException('Pelajaran belum memenuhi persyaratan publikasi.');}
            $this->run("UPDATE lessons SET status='published',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
            $this->log((int)$u['id'],'lesson_publish','lesson',$id);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function submitAnswer(array $u,int $lessonId,string $answer):bool {
        if(!in_array($u['role'],['student','teacher'],true)){throw new DomainException('Hanya akun pembelajaran dapat mengirim jawaban.');}
        if(strlen(trim($answer))<1||strlen($answer)>250){throw new DomainException('Jawaban tidak valid.');}
        $this->db->beginTransaction();
        try {
            $s=$this->run('SELECT l.*, k.status knowledge_status, k.scope, k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.id=? FOR UPDATE',[$lessonId]);$l=$s->fetch(PDO::FETCH_ASSOC);
            if(!$l||!Policy::canViewLesson($u,$l)){throw new LessonUnavailable('Pelajaran tidak tersedia.');}
            $correct=hash_equals(Policy::normalizeAnswer($l['answer']),Policy::normalizeAnswer($answer));
            $this->run('INSERT INTO attempts (lesson_id,learner_id,correct) VALUES (?,?,?)',[$lessonId,$u['id'],$correct?1:0]);
            $this->db->commit();return $correct;
        } catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
}
