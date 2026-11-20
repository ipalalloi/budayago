<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/Policy.php';
require dirname(__DIR__) . '/app/CultureService.php';
require dirname(__DIR__) . '/app/LoginThrottle.php';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!in_array($path, [$base.'/', $base.'/index.php'], true)) { http_response_code(404); exit; }
boot_http();
if ($_SERVER['REQUEST_METHOD']==='GET' && !isset($_GET['p'])) {header('Location: heritage.php',true,302);exit;}
if (($_GET['p']??'')==='login' && ($_GET['heritage']??'')==='1') {$_SESSION['after_login_heritage']=true;}
$service = new CultureService(database());
$action = (string)($_POST['action'] ?? '');

function number_param(string $name): int { return max(0,(int)($_GET[$name] ?? $_POST[$name] ?? 0)); }
function form_begin(string $action, string $destination, array $data = []): void {
    echo '<form method="post" action="' . escape(page_url($destination,$data)) . '">' . csrf_input();
    echo '<input type="hidden" name="action" value="' . escape($action) . '">';
}
function field(string $label, string $name, string $value='', string $type='text', bool $required=true): void {
    echo '<label>' . escape($label) . '<input name="' . escape($name) . '" type="' . escape($type) . '" value="' . escape($value) . '" ' . ($required?'required':'') . '></label>';
}
function multiline(string $label, string $name, string $value=''): void {
    echo '<label>' . escape($label) . '<textarea name="' . escape($name) . '" rows="6" required>' . escape($value) . '</textarea></label>';
}
function menu_link(string $page, string $label): void { echo '<a href="' . escape(page_url($page)) . '">' . escape($label) . '</a>'; }
function top(string $title):void {
    $u=current_user();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="description" content="Sulbar Culture Knowledge - pembelajaran budaya dengan kurasi dan persetujuan pemangku budaya">';
    echo '<title>' . escape($title) . ' · Sulbar Culture Knowledge</title><link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<a class="skip-link" href="#konten-utama">Lewati navigasi</a>';
    echo '<header class="site-header"><div class="bar"><a class="brand" href="' . escape(page_url('home')) . '" aria-label="BUDAYAGO - Beranda"><span class="brand-mark" aria-hidden="true">BG</span><span class="brand-copy"><strong>BUDAYAGO <span class="brand-subname">· Sulbar Culture Knowledge</span></strong><span class="small">Ruang pengetahuan budaya terverifikasi</span></span></a><nav class="site-nav" aria-label="Navigasi utama">';
    menu_link('home','Beranda'); echo '<a href="heritage.php">Living Heritage</a>'; menu_link('knowledge','Perpustakaan Budaya');
    if($u){menu_link('lessons','Pembelajaran');if(in_array($u['role'],['admin','curator'],true)){menu_link('knowledge_form','Tambah materi');menu_link('lesson_form','Buat pelajaran');}if($u['role']==='holder'){menu_link('review','Persetujuan');}if($u['role']==='admin'){menu_link('users','Pengguna');menu_link('audit','Audit');}}
    echo '</nav><div class="account-nav">';
    if($u){echo '<span class="user" title="Akun aktif">' . escape($u['name']) . ' <span class="role-chip">' . escape($u['role']) . '</span></span>';menu_link('password','Ubah sandi');form_begin('logout','home');echo '<button class="subtle" type="submit">Keluar</button></form>';}
    else {menu_link('login','Masuk');}
    echo '</div></div></header><main class="wrap" id="konten-utama"><h1>' . escape($title) . '</h1>';
    if(isset($_SESSION['sck_message'])) {echo '<div class="notice" role="status">' . escape((string)$_SESSION['sck_message']) . '</div>';unset($_SESSION['sck_message']);}
}
function bottom():void {echo '</main><footer><div class="wrap footer-inner">Materi hanya diterbitkan setelah persetujuan pemangku budaya. Tidak ada konten budaya contoh yang dianggap sebagai sumber autentik.</div></footer></body></html>';}
function get_knowledge(int $id): array {
    $k=query('SELECT * FROM knowledge WHERE id=?',[$id])->fetch();
    if(!$k){http_response_code(404);exit('Materi tidak ditemukan.');}
    return $k;
}
function get_lesson(int $id):array {
    $l=query('SELECT l.*, k.status AS knowledge_status,k.scope,k.allow_learning,k.title AS knowledge_title FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.id=?',[$id])->fetch();
    if(!$l){http_response_code(404);exit('Pelajaran tidak ditemukan.');}
    return $l;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $destination='home';$data=[];
    try {
        switch($action){
        case 'login':
            $destination='login';
            $email=strtolower(trim((string)($_POST['email']??'')));
            $pw=(string)($_POST['password']??'');
            // Never use a per-session-only limiter; it is bypassed by obtaining another session.
            // Do not trust client-controlled X-Forwarded-For: use the direct REMOTE_ADDR.
            $throttle=new LoginThrottle(cfg('LOGIN_RATE_LIMIT_DIR'));
            $id=$throttle->attempt($email,(string)($_SERVER['REMOTE_ADDR']??'unknown'),static function() use ($email,$pw):?int {
                $s=query('SELECT id,password_hash,active FROM users WHERE email=?',[$email]);$acc=$s->fetch();
                if(!$acc||!(bool)$acc['active']||!password_verify($pw,$acc['password_hash'])) {return null;}
                return (int)$acc['id'];
            });
            if($id===null){throw new DomainException('Email atau kata sandi tidak sesuai.');}
            log_in($id);
            if(!empty($_SESSION['after_login_heritage'])){unset($_SESSION['after_login_heritage']);header('Location: heritage.php',true,303);exit;}
            $destination='home';message('Masuk berhasil.');break;
        case 'logout':
            log_out();redirect_to('home');
        case 'password_change':
            $u=require_login();$destination='password';
            $old=(string)($_POST['old_password']??'');$new=(string)($_POST['new_password']??'');
            if(strlen($new)<12||strlen($new)>200){throw new DomainException('Kata sandi baru harus 12–200 karakter.');}
            $account=query('SELECT password_hash FROM users WHERE id=?',[$u['id']])->fetch();
            if(!$account||!password_verify($old,$account['password_hash'])){throw new DomainException('Kata sandi lama tidak sesuai.');}
            query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$u['id']]);
            audit_event((int)$u['id'],'password_change','user',(int)$u['id']);session_regenerate_id(true);message('Kata sandi telah diperbarui.');break;
        case 'user_update':
            $u=require_roles(['admin']);$destination='users';$id=number_param('id');
            $target=query('SELECT id,role,active FROM users WHERE id=?',[$id])->fetch();
            if(!$target){throw new DomainException('Akun tidak ditemukan.');}
            $op=(string)($_POST['operation']??'');
            if($op==='reset_password'){
                $pw=(string)($_POST['new_password']??'');
                if(strlen($pw)<12||strlen($pw)>200){throw new DomainException('Kata sandi baru harus 12–200 karakter.');}
                query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($pw,PASSWORD_DEFAULT),$id]);
                audit_event((int)$u['id'],'password_reset','user',$id);message('Kata sandi akun telah diatur ulang.');
            }elseif($op==='deactivate'){
                if($id===(int)$u['id']){throw new DomainException('Tidak dapat menonaktifkan akun sendiri.');}
                if($target['role']==='admin'&&(int)query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn()<=1){throw new DomainException('Admin terakhir harus tetap aktif.');}
                if($target['role']==='holder'&&(int)query("SELECT COUNT(*) FROM knowledge WHERE holder_id=? AND status IN ('submitted','approved','published')",[$id])->fetchColumn()>0){throw new DomainException('Pindahkan/tutup tanggung jawab pemangku budaya terlebih dahulu.');}
                query('UPDATE users SET active=0 WHERE id=?',[$id]);audit_event((int)$u['id'],'user_deactivate','user',$id);message('Akun dinonaktifkan.');
            }elseif($op==='activate'){
                query('UPDATE users SET active=1 WHERE id=?',[$id]);audit_event((int)$u['id'],'user_activate','user',$id);message('Akun diaktifkan.');
            }else{throw new DomainException('Operasi tidak dikenali.');}
            break;
        case 'user_create':
            $u=require_roles(['admin']);$destination='users';
            $name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));
            $role=(string)($_POST['role']??'');$password=(string)($_POST['password']??'');
            if(strlen($name)<3||strlen($name)>150||!Policy::validEmail($email)||!in_array($role,Policy::ROLES,true)||strlen($password)<12){throw new DomainException('Nama, email, peran, atau kata sandi (minimal 12 karakter) tidak valid.');}
            query('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            audit_event((int)$u['id'],'user_create','user',(int)database()->lastInsertId(),$role);message('Akun berhasil dibuat.');break;
        case 'knowledge_save':
            $u=require_roles(['admin','curator']);$destination='knowledge';
            $id=number_param('id');$saved=$service->saveKnowledge($u,$id?:null,$_POST);
            $destination='knowledge_show';$data=['id'=>$saved];message('Draf disimpan.');break;
        case 'knowledge_submit':
            $u=require_roles(['admin','curator']);$id=number_param('id');$destination='knowledge_show';$data=['id'=>$id];
            $service->submitKnowledge($u,$id);message('Dikirim untuk persetujuan pemangku budaya.');break;
        case 'knowledge_review':
            $u=require_roles(['holder']);$id=number_param('id');$destination='knowledge_show';$data=['id'=>$id];
            $service->reviewKnowledge($u,$id,($_POST['decision']??'')==='approve',$_POST,(string)($_POST['reason']??''));
            message('Keputusan pemangku budaya tersimpan.');break;
        case 'knowledge_publish':
            $u=require_roles(['admin']);$id=number_param('id');$destination='knowledge_show';$data=['id'=>$id];
            $service->publishKnowledge($u,$id);message('Pengetahuan diterbitkan sesuai hak akses.');break;
        case 'knowledge_withdraw':
            $u=require_roles(['holder']);$id=number_param('id');$destination='review';
            $service->withdrawKnowledge($u,$id,(string)($_POST['reason']??''));message('Persetujuan dicabut; pengetahuan dan pelajaran terkait kini tersembunyi.');break;
        case 'lesson_save':
            $u=require_roles(['admin','curator']);$destination='lessons';$id=number_param('id');
            $saved=$service->saveLesson($u,$id?:null,$_POST);
            $destination='lesson_show';$data=['id'=>$saved];message('Draf pelajaran disimpan.');break;
        case 'lesson_publish':
            $u=require_roles(['admin']);$id=number_param('id');$destination='lesson_show';$data=['id'=>$id];
            $service->publishLesson($u,$id);message('Pelajaran terbit.');break;
        case 'answer_submit':
            $u=require_roles(['student','teacher']);$id=number_param('id');$destination='lesson_show';$data=['id'=>$id];
            $ok=$service->submitAnswer($u,$id,(string)($_POST['answer']??''));message($ok?'Jawaban benar.':'Jawaban belum tepat. Silakan pelajari kembali materinya.');break;
        default: http_response_code(400);exit('Aksi tidak dikenal.');
        }
    }catch(LessonUnavailable $e){http_response_code(403);exit('Pelajaran tidak tersedia.');}
    catch(DomainException $e){message($e->getMessage());}
     catch(Throwable $e){error_log('SCK error ' . get_class($e) . ': ' . $e->getMessage());message('Operasi gagal. Periksa data atau hubungi administrator.');}
    redirect_to($destination,$data);
}
$page=(string)($_GET['p']??'home');$u=current_user();
switch($page) {
case 'home':
    top('Menjaga tutur, menghubungkan generasi');
    echo '<div class="hero-intro"><span class="eyebrow">PERPUSTAKAAN PENGETAHUAN BUDAYA SULAWESI BARAT</span><p class="lead">Jelajahi pengetahuan budaya dengan jejak sumber, kurasi, persetujuan pemangku budaya, dan pembelajaran yang bertanggung jawab.</p><p class="small">Rilis fungsional awal: publikasi mengikuti izin pemangku budaya. Koleksi multimedia dan tanggapan lintas budayawan masih dalam roadmap.</p></div>';
    echo '<section class="grid"><article class="card"><h2>Pengetahuan</h2><p>Jejak sumber dan tingkat akses jelas. Materi yang belum disetujui tidak tampil kepada publik.</p><a class="button" href="'.escape(page_url('knowledge')).'">Jelajahi</a></article><article class="card"><h2>Pelajaran</h2><p>Pembelajaran dengan tujuan, materi, dan pertanyaan pemahaman.</p><a class="button" href="'.escape(page_url('lessons')).'">Mulai belajar</a></article><article class="card"><h2>Peran pemangku budaya</h2><p>Pengetahuan hanya terbit dengan persetujuan; izin dapat dicabut dan konten otomatis disembunyikan.</p></article></section>';
    if($u&&$u['role']==='admin'){
        $users=(int)query('SELECT COUNT(*) FROM users')->fetchColumn();$k=(int)query("SELECT COUNT(*) FROM knowledge WHERE status='published'")->fetchColumn();$l=(int)query("SELECT COUNT(*) FROM lessons WHERE status='published'")->fetchColumn();$a=(int)query('SELECT COUNT(*) FROM attempts')->fetchColumn();
        echo '<section class="stats"><div><strong>'. $users .'</strong><span>Pengguna</span></div><div><strong>'.$k.'</strong><span>Pengetahuan terbit</span></div><div><strong>'.$l.'</strong><span>Pelajaran terbit</span></div><div><strong>'.$a.'</strong><span>Jawaban tersimpan</span></div></section>';
    }
    bottom();break;
case 'login':
    if($u){redirect_to('home');}top('Masuk');echo '<div class="narrow card">';form_begin('login','login');field('Email','email','','email');field('Kata sandi','password','','password');echo '<button type="submit">Masuk</button></form><p class="small">Akun dibuat oleh administrator. Pendaftaran terbuka tidak tersedia untuk melindungi peserta didik.</p></div>';bottom();break;
case 'password':
    require_login();top('Ubah kata sandi');echo '<section class="card narrow">';form_begin('password_change','password');
    field('Kata sandi lama','old_password','','password');field('Kata sandi baru (12+ karakter)','new_password','','password');
    echo '<button type="submit">Simpan sandi baru</button></form></section>';bottom();break;
case 'users':
    require_roles(['admin']);top('Kelola pengguna');echo '<div class="admin-layout"><section class="card admin-create"><div class="section-heading"><span class="eyebrow">AKSES & PERAN</span><h2>Tambah akun</h2></div>';form_begin('user_create','users');field('Nama','name');field('Email','email','','email');field('Kata sandi minimal 12 karakter','password','','password');echo '<label>Peran<select name="role">';foreach(Policy::ROLES as $role){echo '<option value="'.escape($role).'">'.escape($role).'</option>';}echo '</select></label><button type="submit">Buat akun</button></form></section><section class="card admin-list"><div class="section-heading"><span class="eyebrow">ADMINISTRASI</span><h2>Daftar pengguna</h2><p class="small">Aksi sensitif hanya tersedia bagi administrator. Geser tabel pada layar kecil.</p></div><div class="scroll table-scroll" tabindex="0" role="region" aria-label="Daftar pengguna, dapat digeser horizontal"><table class="users-table"><thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Peran</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>';foreach(query('SELECT id,name,email,role,active FROM users ORDER BY id DESC LIMIT 100')->fetchAll() as $r){echo '<tr><td>'.escape($r['name']).'</td><td>'.escape($r['email']).'</td><td><span class="role-chip">'.escape($r['role']).'</span></td><td><span class="status-chip '.((int)$r['active']===1?'is-active':'is-inactive').'">'.((int)$r['active']===1?'Aktif':'Nonaktif').'</span></td><td>';form_begin('user_update','users');echo '<input type="hidden" name="id" value="'.(int)$r['id'].'">';echo '<div class="table-action"><label class="visually-hidden" for="operation-'.(int)$r['id'].'">Aksi untuk '.escape($r['name']).'</label><select id="operation-'.(int)$r['id'].'" name="operation"><option value="reset_password">Reset sandi</option><option value="deactivate">Nonaktifkan</option><option value="activate">Aktifkan</option></select><label class="visually-hidden" for="new-password-'.(int)$r['id'].'">Sandi baru untuk '.escape($r['name']).'</label><input id="new-password-'.(int)$r['id'].'" type="password" name="new_password" placeholder="Sandi baru (jika reset)" autocomplete="new-password"><button type="submit">Proses</button></div></form></td></tr>';}echo '</tbody></table></div></section></div>';bottom();break;
case 'knowledge':
    top('Pengetahuan budaya');
    if(!$u){$rows=query("SELECT * FROM knowledge WHERE status='published' AND scope='PUBLIC' ORDER BY updated_at DESC LIMIT 100")->fetchAll();}
    elseif($u['role']==='admin'){$rows=query('SELECT * FROM knowledge ORDER BY updated_at DESC LIMIT 100')->fetchAll();}
    elseif($u['role']==='holder'){$rows=query("SELECT * FROM knowledge WHERE holder_id=? OR (status='published' AND scope IN ('PUBLIC','MEMBERS')) ORDER BY updated_at DESC LIMIT 100",[$u['id']])->fetchAll();}
    elseif($u['role']==='curator'){$rows=query("SELECT * FROM knowledge WHERE author_id=? OR (status='published' AND scope IN ('PUBLIC','MEMBERS')) ORDER BY updated_at DESC LIMIT 100",[$u['id']])->fetchAll();}
    else {$rows=query("SELECT * FROM knowledge WHERE status='published' AND scope IN ('PUBLIC','MEMBERS') ORDER BY updated_at DESC LIMIT 100")->fetchAll();}
    if(!$rows){echo '<p class="notice">Belum ada pengetahuan yang dapat ditampilkan sesuai hak akses Anda. Tidak ada konten budaya contoh yang diterbitkan.</p>';}
    echo '<div class="grid">';foreach($rows as $k){echo '<article class="card"><div class="tag">'.escape($k['scope']).' · '.escape($k['status']).'</div><h2>'.escape($k['title']).'</h2><p>'.escape($k['summary']).'</p><a class="button" href="'.escape(page_url('knowledge_show',['id'=>$k['id']])).'">Lihat detail</a></article>';}echo '</div>';bottom();break;
case 'knowledge_form':
    $author=require_roles(['admin','curator']);$id=number_param('id');$k=$id?get_knowledge($id):null;
    if($k&&!Policy::canEdit($author,$k)){http_response_code(403);exit('Akses ditolak.');}
    $holders=query("SELECT id,name FROM users WHERE role='holder' AND active=1 ORDER BY name")->fetchAll();
    top($k?'Edit pengetahuan':'Tambah pengetahuan');if(!$holders){echo '<div class="notice">Buat akun pemangku budaya terlebih dahulu sebelum mengisi pengetahuan.</div>';bottom();break;}
    echo '<div class="card narrow">';form_begin('knowledge_save','knowledge_form');if($k)echo '<input type="hidden" name="id" value="'.$id.'">';
    field('Judul terverifikasi','title',$k['title']??'');multiline('Ringkasan kontekstual','summary',$k['summary']??'');multiline('Isi berdasarkan sumber berizin','body',$k['body']??'');
    field('Nama sumber / pemangku','source_name',$k['source_name']??'');field('Rujukan bukti privat (jangan masukkan rekaman sakral di sini)','source_ref',$k['source_ref']??'');
    echo '<label>Pemangku budaya yang berwenang<select name="holder_id">';foreach($holders as $h){echo '<option value="'.$h['id'].'" '.(((int)($k['holder_id']??0)===(int)$h['id'])?'selected':'').'>'.escape($h['name']).'</option>';}echo '</select></label>';
    echo '<label>Tingkat akses<select name="scope">';foreach(Policy::SCOPES as $scope){echo '<option value="'.$scope.'" '.((($k['scope']??'RESTRICTED')===$scope)?'selected':'').'>'.$scope.'</option>';}echo '</select></label>';
    echo '<p class="small">RESTRICTED tidak dapat dipublikasikan. MEMBERS hanya bagi akun terdaftar; PUBLIC dapat dilihat siapa saja setelah izin publik.</p><button type="submit">Simpan draf</button></form></div>';bottom();break;
case 'knowledge_show':
    $id=number_param('id');$k=get_knowledge($id);
    if(!Policy::canReadKnowledge($u,$k)){http_response_code(403);exit('Materi ini tidak tersedia untuk Anda.');}
    top($k['title']);echo '<article class="card"><div class="tag">'.escape($k['scope']).' · '.escape($k['status']).'</div><p class="lead">'.escape($k['summary']).'</p><div class="prose">'.nl2br(escape($k['body'])).'</div><hr><p><strong>Sumber:</strong> '.escape($k['source_name']).'</p></article>';
    if(Policy::canReadSourceReference($u,$k)){echo '<p class="small">Referensi internal: '.escape($k['source_ref']).'</p>';}
    if($u&&Policy::canEdit($u,$k)){
        echo '<div class="actions"><a class="button" href="'.escape(page_url('knowledge_form',['id'=>$id])).'">Edit draf</a>';
        if(in_array($k['status'],['draft','rejected'],true)){form_begin('knowledge_submit','knowledge_show',['id'=>$id]);echo '<input type="hidden" name="id" value="'.$id.'"><button type="submit">Kirim persetujuan</button></form>';}
        echo '</div>';
    }
    if($u&&Policy::canReview($u,$k)){
        echo '<section class="card"><h2>Persetujuan pemangku budaya</h2><p>Centang tiap ruang lingkup secara terpisah; izin AI dan promosi tidak diwajibkan.</p>';form_begin('knowledge_review','knowledge_show',['id'=>$id]);echo '<input type="hidden" name="id" value="'.$id.'">';
        foreach(['allow_learning'=>'Digunakan untuk materi pembelajaran','allow_online'=>'Ditampilkan di internet untuk umum','allow_ai'=>'Dapat dipakai sebagai sumber jawaban AI pada versi mendatang','allow_promo'=>'Boleh digunakan sebagai materi promosi'] as $key=>$label){echo '<label class="check"><input type="checkbox" name="'.$key.'" value="1">'.escape($label).'</label>';}
        echo '<label>Alasan revisi / catatan<textarea name="reason" rows="3"></textarea></label><div class="actions"><button name="decision" value="approve" type="submit">Setujui sesuai izin</button><button class="danger" name="decision" value="reject" type="submit">Tolak / kembalikan</button></div></form></section>';
    }
    if($u&&Policy::canPublish($u,$k)){form_begin('knowledge_publish','knowledge_show',['id'=>$id]);echo '<input type="hidden" name="id" value="'.$id.'"><button type="submit">Terbitkan pengetahuan</button></form>';}
    if($u&&Policy::canWithdraw($u,$k)){echo '<section class="card"><h2>Cabut persetujuan</h2><p>Pencabutan akan langsung menyembunyikan materi serta semua pelajaran terkait.</p>';form_begin('knowledge_withdraw','review');echo '<input type="hidden" name="id" value="'.$id.'"><label>Alasan pencabutan<input name="reason" required minlength="5"></label><button class="danger" type="submit">Cabut izin dan sembunyikan</button></form></section>';}
    bottom();break;
case 'review':
    $actor=require_roles(['holder']);top('Persetujuan dan pencabutan');
    $rows=query("SELECT * FROM knowledge WHERE holder_id=? AND status IN ('submitted','approved','published') ORDER BY updated_at DESC LIMIT 100",[$actor['id']])->fetchAll();
    if(!$rows)echo '<p class="notice">Tidak ada materi yang menunggu keputusan.</p>';
    foreach($rows as $k){echo '<article class="card"><div class="tag">'.escape($k['status']).'</div><h2>'.escape($k['title']).'</h2><p>'.escape($k['summary']).'</p><a href="'.escape(page_url('knowledge_show',['id'=>$k['id']])).'" class="button">Tinjau</a></article>';}
    bottom();break;
case 'lessons':
    $actor=require_login();top('Modul pembelajaran');
    if($actor['role']==='admin'){$rows=query('SELECT l.*,k.status knowledge_status,k.scope,k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id ORDER BY l.updated_at DESC LIMIT 100')->fetchAll();}
    elseif($actor['role']==='curator'){$rows=query("SELECT l.*,k.status knowledge_status,k.scope,k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.author_id=? OR (l.status='published' AND k.status='published' AND k.scope <> 'RESTRICTED') ORDER BY l.updated_at DESC LIMIT 100",[$actor['id']])->fetchAll();}
    else{$rows=query("SELECT l.*,k.status knowledge_status,k.scope,k.allow_learning FROM lessons l JOIN knowledge k ON k.id=l.knowledge_id WHERE l.status='published' AND k.status='published' AND k.scope <> 'RESTRICTED' AND k.allow_learning=1 ORDER BY l.updated_at DESC LIMIT 100")->fetchAll();}
    if(!$rows)echo '<p class="notice">Belum ada pelajaran yang dapat diakses.</p>';
    echo '<div class="grid">';foreach($rows as $l){if($actor['role']!=='admin'&&$actor['role']!=='curator'&&!Policy::canViewLesson($actor,$l))continue;echo '<article class="card"><div class="tag">'.escape($l['status']).'</div><h2>'.escape($l['title']).'</h2><p>'.escape($l['objective']).'</p><a href="'.escape(page_url('lesson_show',['id'=>$l['id']])).'" class="button">Buka pelajaran</a></article>';}echo '</div>';
    if(in_array($actor['role'],['teacher','student'],true)){
        $s=query('SELECT COUNT(*) total,COALESCE(SUM(correct),0) correct FROM attempts WHERE learner_id=?',[$actor['id']])->fetch();
        echo '<p class="small">Riwayat Anda: '.(int)$s['total'].' jawaban; '.(int)$s['correct'].' benar.</p>';
    }
    bottom();break;
case 'lesson_form':
    $author=require_roles(['admin','curator']);$id=number_param('id');$l=$id?get_lesson($id):null;
    if($l&&($l['status']!=='draft'||($author['role']!=='admin'&&(int)$l['author_id']!==(int)$author['id']))){http_response_code(403);exit('Pelajaran tidak dapat diubah.');}
    $sources=query("SELECT id,title FROM knowledge WHERE status='published' AND allow_learning=1 AND scope <> 'RESTRICTED' ORDER BY title LIMIT 100")->fetchAll();
    top($l?'Edit pelajaran':'Buat pelajaran');if(!$sources){echo '<div class="notice">Belum ada pengetahuan yang diterbitkan dan disetujui untuk pembelajaran.</div>';bottom();break;}
    echo '<section class="card narrow">';form_begin('lesson_save','lesson_form');if($l)echo '<input name="id" type="hidden" value="'.$id.'">';
    echo '<label>Pengetahuan terverifikasi<select name="knowledge_id">';foreach($sources as $s){echo '<option value="'.$s['id'].'" '.(((int)($l['knowledge_id']??0)===(int)$s['id'])?'selected':'').'>'.escape($s['title']).'</option>';}echo '</select></label>';
    field('Judul modul','title',$l['title']??'');multiline('Tujuan belajar','objective',$l['objective']??'');multiline('Materi belajar','body',$l['body']??'');multiline('Pertanyaan pemahaman','question',$l['question']??'');field('Jawaban acuan singkat','answer',$l['answer']??'');
    echo '<p class="small">Evaluasi saat ini memakai jawaban singkat dengan kecocokan teks normalisasi. Tidak digunakan sebagai penilaian akademik formal.</p><button type="submit">Simpan draf modul</button></form></section>';bottom();break;
case 'lesson_show':
    $actor=require_login();$id=number_param('id');$l=get_lesson($id);
    $editor=in_array($actor['role'],['admin','curator'],true)&&($actor['role']==='admin'||(int)$l['author_id']===(int)$actor['id']);
    if(!Policy::canViewLesson($actor,$l) && !($editor&&$l['status']==='draft')){http_response_code(403);exit('Pelajaran belum tersedia.');}
    top($l['title']);echo '<article class="card lesson-article"><div class="lesson-meta"><span class="status-chip">'.escape($l['status']).'</span><span class="source-label">Sumber terverifikasi: '.escape($l['knowledge_title']).'</span></div><section class="lesson-section"><span class="section-step" aria-hidden="true">01</span><div><h2>Tujuan pembelajaran</h2><p>'.nl2br(escape($l['objective'])).'</p></div></section><section class="lesson-section"><span class="section-step" aria-hidden="true">02</span><div><h2>Materi</h2><div class="prose">'.nl2br(escape($l['body'])).'</div></div></section><section class="lesson-section"><span class="section-step" aria-hidden="true">03</span><div><h2>Pertanyaan pemahaman</h2><p>'.nl2br(escape($l['question'])).'</p></div></section></article>';
    if($editor&&$l['status']==='draft'){echo '<div class="actions"><a class="button" href="'.escape(page_url('lesson_form',['id'=>$id])).'">Edit pelajaran</a>';if($actor['role']==='admin'&&$l['knowledge_status']==='published'){form_begin('lesson_publish','lesson_show',['id'=>$id]);echo '<input type="hidden" name="id" value="'.$id.'"><button type="submit">Terbitkan pelajaran</button></form>';}echo '</div>';}
    if(in_array($actor['role'],['teacher','student'],true)&&Policy::canViewLesson($actor,$l)){echo '<section class="card"><h2>Jawab pertanyaan</h2>';form_begin('answer_submit','lesson_show',['id'=>$id]);echo '<input type="hidden" name="id" value="'.$id.'">';field('Jawaban singkat Anda','answer');echo '<button type="submit">Kirim jawaban</button></form></section>';}
    bottom();break;
case 'audit':
    require_roles(['admin']);top('Log aktivitas');
    echo '<p class="small">Log menyimpan perubahan status dan akun aktor, bukan isi rekaman atau catatan budaya.</p><section class="card scroll"><table><tr><th>Waktu</th><th>Aktor</th><th>Aksi</th><th>Objek</th><th>Detail</th></tr>';
    foreach(query('SELECT a.*,u.name AS actor FROM audit_events a LEFT JOIN users u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 100')->fetchAll() as $r){echo '<tr><td>'.escape($r['created_at']).'</td><td>'.escape($r['actor']??'System').'</td><td>'.escape($r['action']).'</td><td>'.escape($r['entity_type']).' #'.(int)$r['entity_id'].'</td><td>'.escape($r['detail']).'</td></tr>';}
    echo '</table></section>';bottom();break;
default:
    http_response_code(404);top('Halaman tidak ditemukan');echo '<p>Rute tidak tersedia.</p>';bottom();break;
}
