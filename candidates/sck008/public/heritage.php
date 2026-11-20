<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/HeritageRights.php';
require dirname(__DIR__) . '/app/HeritageStream.php';
boot_http();
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; media-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Permissions-Policy: geolocation=(), camera=(self), microphone=(self)');
function h(?string $s): string { return escape($s); }
function urlh(string $view='gallery', int $id=0): string {return 'heritage.php?'.http_build_query(['v'=>$view]+($id?['id'=>$id]:[]));}
function failh(int $code=403): never {http_response_code($code);exit($code===404?'Koleksi tidak ditemukan.':'Akses ditolak.');}
function recordh(int $id): array { $r=query('SELECT * FROM heritage_collections WHERE id=?',[$id])->fetch();if(!$r)failh(404);return $r;}
function ownerh(?array $u,array $r): bool {return $u!==null&&($u['role']==='admin'||(int)$u['id']===(int)$r['author_id']||(int)$u['id']===(int)$r['holder_id']);}
function publich(array $r): bool {return cfg('HERITAGE_PUBLICATION_ENABLED','false')==='true'&&$r['status']==='PUBLISHED'&&(int)$r['public_consent']===1&&(int)$r['record_consent']===1&&(int)$r['archive_consent']===1&&rights_reviewed((int)$r['id']);}
function readh(?array $u,array $r): bool {return publich($r)||ownerh($u,$r)||($u!==null&&$u['role']==='curator');}
function posth(string $action,int $id=0): void {echo '<form method="post" action="'.h(urlh('detail',$id)).'">'.csrf_input().'<input type="hidden" name="action" value="'.h($action).'"><input type="hidden" name="id" value="'.$id.'">';}
function changeh(int $id,?array $u,string $event,string $status): void {query('UPDATE heritage_collections SET status=? WHERE id=?',[$status,$id]);audit_event((int)$u['id'],$event,'heritage_collection',$id);}
$u=current_user();$v=(string)($_GET['v']??'gallery');$id=max(0,(int)($_GET['id']??$_POST['id']??0));
if($v==='media'){
 $asset=query('SELECT * FROM heritage_assets WHERE id=?',[$id])->fetch();if(!$asset)failh(404);
 $r=recordh((int)$asset['collection_id']);if(!readh($u,$r))failh();
 $staff=ownerh($u,$r)||($u&&$u['role']==='curator');
 if(!$staff && !query('SELECT 1 FROM heritage_asset_grants WHERE asset_id=?',[$id])->fetchColumn())failh();
 if(!preg_match('/^[a-f0-9]{32}$/D',$asset['storage_key']))failh(404);
 $path=media_directory().'/'.$asset['storage_key'];if(!is_file($path))failh(404);
 stream_asset($path,$asset['mime']);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$a=(string)($_POST['action']??'');
 $dest=null;
 try {
  database()->beginTransaction();
  if($a==='create'){
   $actor=require_roles(['admin','curator','holder','teacher']);
   $title=trim((string)($_POST['title']??''));$category=(string)($_POST['category']??'');$region=trim((string)($_POST['region']??''));$description=trim((string)($_POST['description']??''));$holder=(int)($_POST['holder_id']??0);
   if(mb_strlen($title)<3||mb_strlen($title)>180||!in_array($category,['Tutur','Manuskrip','Objek','Tradisi'],true)||mb_strlen($region)>160||mb_strlen($description)>5000)throw new DomainException('Metadata tidak valid.');
   $validHolder=query("SELECT id FROM users WHERE id=? AND role='holder' AND active=1",[$holder])->fetch();if(!$validHolder)throw new DomainException('Pemangku hak yang terverifikasi diperlukan.');
   $key=(string)($_POST['draft_key']??'');
   $existing=claim_draft((int)$actor['id'],$key);
   if($existing){$id=$existing;}else{
   query('INSERT INTO heritage_collections(title,category,region,description,author_id,holder_id) VALUES(?,?,?,?,?,?)',[$title,$category,$region,$description,$actor['id'],$holder]);$id=(int)database()->lastInsertId();audit_event((int)$actor['id'],'heritage_create','heritage_collection',$id);query('UPDATE heritage_draft_receipts SET collection_id=? WHERE author_id=? AND request_key=?',[$id,$actor['id'],$key]);}
   message('Draf koleksi tersimpan di server.');
  } else {
   $actor=require_login();$r=query('SELECT * FROM heritage_collections WHERE id=? FOR UPDATE',[$id])->fetch();if(!$r)failh(404);
   if($a==='upload'){
    if(!ownerh($actor,$r)||!in_array($r['status'],['DRAFT','NEEDS_REVISION'],true))failh();
    if(!isset($_FILES['media'])||!is_array($_FILES['media'])||$_FILES['media']['error']!==UPLOAD_ERR_OK)throw new DomainException('Unggahan gagal. Pilih berkas yang sesuai batas server.');
    $f=$_FILES['media'];if((int)$f['size']<1||(int)$f['size']>12*1024*1024||!is_uploaded_file($f['tmp_name']))throw new DomainException('Ukuran media harus 1 byte hingga 12 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed=['image/jpeg','image/png','image/webp','audio/mpeg','audio/mp4','audio/ogg','audio/webm','video/mp4','video/webm','application/pdf'];
    if(mb_strlen(trim((string)($_POST['capture_ref']??'')))<5||mb_strlen((string)$_POST['capture_ref'])>180)throw new DomainException('Isi referensi izin perekaman atau akuisisi yang sudah diperiksa.');
    if(!in_array($mime,$allowed,true))throw new DomainException('Jenis berkas tidak diizinkan.');
    $dir=media_directory();
    $key=bin2hex(random_bytes(16));$dest=$dir.'/'.$key;
    $digest=hash_file('sha256',$f['tmp_name']);
    $prior=query('SELECT asset_id FROM heritage_upload_receipts WHERE collection_id=? AND content_sha256=?',[$id,$digest])->fetchColumn();
    if($prior){message('Berkas identik sudah tersimpan.');$dest=null;}else{
    if(!move_uploaded_file($f['tmp_name'],$dest))throw new RuntimeException('Media storage error');chmod($dest,0600);
    query('INSERT INTO heritage_assets(collection_id,storage_key,original_name,mime,size_bytes) VALUES(?,?,?,?,?)',[$id,$key,mb_substr(basename((string)$f['name']),0,190),$mime,(int)$f['size']]);
    query('INSERT INTO heritage_upload_receipts(collection_id,content_sha256,asset_id,capture_ref) VALUES(?,?,?,?)',[$id,$digest,(int)database()->lastInsertId(),trim((string)$_POST['capture_ref'])]);
    audit_event((int)$actor['id'],'heritage_upload','heritage_collection',$id);message('Berkas tersimpan secara privat.');}
   } elseif($a==='submit'){
    if(!ownerh($actor,$r)||!in_array($r['status'],['DRAFT','NEEDS_REVISION'],true))failh();
    changeh($id,$actor,'heritage_submit','CONSENT_PENDING');message('Menunggu peninjauan izin pemangku hak.');
   } elseif($a==='consent'){
    if((int)$actor['id']!==(int)$r['holder_id']||$actor['role']!=='holder'||$r['status']!=='CONSENT_PENDING')failh();
    $consent=(string)($_POST['decision']??'');if($consent==='reject'){changeh($id,$actor,'heritage_consent_reject','NEEDS_REVISION');}
    elseif($consent==='approve'){
     $reference=trim((string)($_POST['evidence_ref']??''));
     if(mb_strlen($reference)<5||mb_strlen($reference)>250||empty($_POST['record'])||empty($_POST['archive'])||empty($_POST['public']))throw new DomainException('Bukti persetujuan privat dan tiga izin terpisah diperlukan.');
     register_rights($actor,$id,$reference);
     query('UPDATE heritage_collections SET record_consent=1,archive_consent=1,public_consent=1,consent_evidence_ref=?,status=? WHERE id=?',[$reference,'CURATION',$id]);audit_event((int)$actor['id'],'heritage_consent_approve','heritage_collection',$id);
    }else throw new DomainException('Keputusan tidak valid.');message('Keputusan izin dicatat.');
   } elseif($a==='curate'){
    if($actor['role']!=='curator'||$r['status']!=='CURATION'||(int)$actor['id']===(int)$r['holder_id'])failh();
    $review=query('UPDATE heritage_rights SET reviewer_id=?,reviewed_at=CURRENT_TIMESTAMP WHERE collection_id=? AND reviewer_id IS NULL',[$actor['id'],$id]);
    if($review->rowCount()!==1)throw new DomainException('Persetujuan terperinci belum tersedia atau telaah sudah selesai.');
    audit_event((int)$actor['id'],'heritage_curate','heritage_collection',$id);
    message('Telaah kurator dicatat.');
   } elseif($a==='publish'){
    if(cfg('HERITAGE_PUBLICATION_ENABLED','false')!=='true'||!rights_reviewed($id))failh();
    if($actor['role']!=='admin'||$r['status']!=='CURATION'||!(int)$r['record_consent']||!(int)$r['archive_consent']||!(int)$r['public_consent'])failh();
    changeh($id,$actor,'heritage_publish','PUBLISHED');message('Koleksi dipublikasikan.');
   } elseif($a==='withdraw'){
    if(!ownerh($actor,$r))failh();changeh($id,$actor,'heritage_withdraw','WITHDRAWN');message('Koleksi ditarik. Akses publik dinonaktifkan.');
   } elseif($a==='contribute'){
    if(!publich($r)||!in_array($actor['role'],['holder','curator','admin'],true))failh();
    $text=trim((string)($_POST['text_body']??''));if(mb_strlen($text)<10||mb_strlen($text)>5000)throw new DomainException('Kontribusi harus 10–5000 karakter.');
    query('INSERT INTO heritage_contributions(collection_id,author_id,text_body) VALUES(?,?,?)',[$id,$actor['id'],$text]);audit_event((int)$actor['id'],'heritage_contribute','heritage_collection',$id);message('Kontribusi dikirim untuk moderasi.');
   } elseif($a==='moderate'){
    if(!in_array($actor['role'],['admin','curator'],true))failh();$cid=(int)($_POST['contribution_id']??0);$decision=(string)($_POST['decision']??'');if(!in_array($decision,['APPROVED','REJECTED'],true))throw new DomainException('Keputusan tidak valid.');
    $updated=query("UPDATE heritage_contributions SET status=? WHERE id=? AND collection_id=? AND status='PENDING'",[$decision,$cid,$id]);if($updated->rowCount()!==1)throw new DomainException('Kontribusi sudah diproses atau tidak tersedia.');audit_event((int)$actor['id'],'heritage_moderate','heritage_contribution',$cid);message('Tanggapan telah dimoderasi.');
   } else throw new DomainException('Operasi tidak dikenali.');
  }
  database()->commit();
  if($a==='create')$_SESSION['heritage_saved_key']=$key;
 } catch(Throwable $e){
  if(database()->inTransaction()){
   database()->rollBack();
   if($dest && is_file($dest))unlink($dest);
  }
  if($e instanceof DomainException){message($e->getMessage());}
  else{error_log('Heritage operation failed '.get_class($e));message('Operasi gagal. Draf lokal dapat dikirim ulang.');}
 }
 header('Location: '.urlh($id?'detail':'gallery',$id),true,303);exit;
}
if (!in_array($v,['gallery','create','mine','detail'],true)) failh(404);
if ($v==='create') require_roles(['admin','curator','holder','teacher']);
if ($v==='mine') require_login();
if ($v==='detail') {
    $r=recordh($id);
    if(!readh($u,$r))failh();
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#20473C"><meta name="description" content="BUDAYAGO — Living Heritage Library"><link rel="manifest" href="manifest.webmanifest"><link rel="icon" href="assets/icon-192.png"><link rel="stylesheet" href="assets/heritage.css"><script src="assets/heritage.js" defer></script><title>BUDAYAGO — Living Heritage Library</title></head><body>
<a href="#main" class="skip">Lewati navigasi</a><header><a href="heritage.php"><strong>BUDAYAGO</strong><small> · Living Heritage Library</small></a><nav aria-label="Navigasi utama"><a href="heritage.php">Jelajah Budaya</a><?php if($u):?><?php if($u['role']!=='student'):?><a href="<?=h(urlh('create'))?>">Rekam Budaya</a><?php endif;?><a href="<?=h(urlh('mine'))?>">Kontribusi dan Kurasi</a><?php if($u['role']==='admin'):?><a href="index.php?p=users">Administrasi</a><?php endif;endif;?><a href="index.php?p=home">Pembelajaran</a><?php if(!$u):?><a href="index.php?p=login&amp;heritage=1">Masuk</a><?php endif;?></nav><?php if($u):?><form method="post" class="signout" action="index.php?p=home"><?=csrf_input()?><input type="hidden" name="action" value="logout"><button>Keluar</button></form><?php endif;?><span id="netstatus" aria-live="polite"></span></header>
<?php if($v==='gallery'):?><section class="hero"><div class="eyebrow">Warisan yang hidup · Cerita yang terjaga</div><h1>Dengarkan tutur.<br><em>Temukan warisan.<br>Jaga ingatan.</em></h1><p>Perpustakaan budaya hidup Sulawesi Barat: cerita, manuskrip, objek, dan tradisi. Setiap koleksi melewati proses persetujuan pemangku hak serta kurasi.</p><a href="#koleksi" class="button light">Jelajahi koleksi</a> <?php if($u&&$u['role']!=='student'):?><a class="button light" href="<?=h(urlh('create'))?>">+ Rekam Budaya</a><?php endif;?></section><?php endif;?>
<main id="main" <?php if($u):?>data-account="<?=(int)$u['id']?>" data-saved-key="<?=h($_SESSION['heritage_saved_key']??'')?>"<?php unset($_SESSION['heritage_saved_key']);endif;?>><?php if(isset($_SESSION['sck_message'])):?><div class="notice" role="status"><?=h((string)$_SESSION['sck_message'])?></div><?php unset($_SESSION['sck_message']);endif;?>
<?php if($v==='gallery'):?>
<section id="koleksi"><p class="eyebrow">Perpustakaan budaya</p><h2>Jelajahi jejak warisan</h2><p class="muted">Hanya koleksi dengan persetujuan eksplisit yang dapat tampil di sini.</p><?php $term=mb_substr(trim((string)($_GET['q']??'')),0,120);?><form method="get" class="search"><label>Cari judul, wilayah, atau cerita<input type="search" name="q" value="<?=h($term)?>" maxlength="120"></label><button>Cari koleksi</button></form><div class="grid">
<?php $rows=cfg('HERITAGE_PUBLICATION_ENABLED','false')!=='true'?[]:query("SELECT id,title,category,region,description FROM heritage_collections WHERE status='PUBLISHED' AND record_consent=1 AND archive_consent=1 AND public_consent=1 AND EXISTS (SELECT 1 FROM heritage_rights g WHERE g.collection_id=heritage_collections.id AND g.reviewer_id IS NOT NULL) AND (title LIKE ? OR region LIKE ? OR description LIKE ?) ORDER BY updated_at DESC LIMIT 100",array_fill(0,3,'%'.$term.'%'))->fetchAll();foreach($rows as $r):?><article class="card"><span class="tag"><?=h($r['category'])?></span><h3><?=h($r['title'])?></h3><p><?=h(mb_strimwidth($r['description'],0,180,'…'))?></p><p class="muted"><?=h($r['region'])?></p><a class="button" href="<?=h(urlh('detail',(int)$r['id']))?>">Buka koleksi</a></article><?php endforeach; if(!$rows):?><article class="card"><h3><?=$term?'Koleksi tidak ditemukan':'Galeri menanti koleksi terkurasi'?></h3><p><?=$term?'Coba kata lain atau kosongkan pencarian.':'Koleksi pertama akan tampil setelah izin dan telaah selesai.'?></p></article><?php endif;?></div></section>
<?php elseif($v==='create'): $actor=require_roles(['admin','curator','holder','teacher']);$holders=query("SELECT id,name FROM users WHERE role='holder' AND active=1 ORDER BY name")->fetchAll();?>
<h1>Rekam Budaya</h1><p>Catat sumber, konteks, dan pemangku hak sebelum mengambil dokumentasi. Draf metadata dapat disimpan lokal saat jaringan lemah; media hanya diunggah setelah koleksi tersimpan di server.</p>
<?php if(!$holders):?><div class="notice">Belum ada akun pemangku budaya terverifikasi. Admin perlu membuat akun pemangku hak sebelum merekam koleksi.</div><?php else:?>
<div class="card"><form id="heritage-create" method="post" action="heritage.php"><?=csrf_input()?><input type="hidden" name="action" value="create"><input type="hidden" name="draft_key" value="<?=bin2hex(random_bytes(16))?>"><label>Judul koleksi<input name="title" maxlength="180" required minlength="3"></label><label>Kategori<select name="category"><option value="Tutur">Tutur lisan</option><option value="Manuskrip">Manuskrip</option><option value="Objek">Objek budaya</option><option value="Tradisi">Tradisi</option></select></label><label>Wilayah umum (tanpa alamat privat)<input name="region" maxlength="160"></label><label>Deskripsi / asal informasi<textarea name="description" maxlength="5000" rows="5"></textarea></label><label>Pemangku hak yang terverifikasi<select name="holder_id" required><?php foreach($holders as $h):?><option value="<?=(int)$h['id']?>"><?=h($h['name'])?></option><?php endforeach;?></select></label><p class="draft-hint">Persetujuan dokumentasi harus dimintakan sebelum rekaman. Gunakan perangkat pribadi. Simpan hanya metadata non-sensitif. Satu draf lokal tersedia per akun pada peramban ini. Pertahankan tab saat luring dan pilih kembali media setelah tersambung.</p><div class="actions"><button type="button" id="save-local">Simpan metadata draf offline</button><button type="button" id="delete-local">Hapus draf perangkat</button><button type="submit">Simpan ke server</button></div><p id="draft-status" role="status"></p></form></div><?php endif;?>
<?php elseif($v==='mine'): $actor=require_login();?><h1>Draf & Kurasi</h1><div class="grid"><?php $rows=$actor['role']==='admin'||$actor['role']==='curator'?query('SELECT * FROM heritage_collections ORDER BY updated_at DESC LIMIT 100')->fetchAll():query('SELECT * FROM heritage_collections WHERE author_id=? OR holder_id=? ORDER BY updated_at DESC LIMIT 100',[$actor['id'],$actor['id']])->fetchAll();foreach($rows as $r):?><article class="card"><span class="tag"><?=h($r['status'])?></span><h3><?=h($r['title'])?></h3><a class="button" href="<?=h(urlh('detail',(int)$r['id']))?>">Kelola</a></article><?php endforeach;?></div>
<?php elseif($v==='detail'):?><p class="eyebrow">Koleksi · <?=h($r['category'])?></p><h1><?=h($r['title'])?></h1><p class="tag"><?=h($r['status'])?></p><p><?=nl2br(h($r['description']))?></p><p class="muted">Wilayah umum: <?=h($r['region'])?></p><h2>Dokumentasi multimedia</h2><div class="grid">
<?php $assets=query('SELECT * FROM heritage_assets WHERE collection_id=? ORDER BY id',[$id])->fetchAll();foreach($assets as $m):if(!ownerh($u,$r)&&($u['role']??'')!=='curator'&&!query('SELECT 1 FROM heritage_asset_grants WHERE asset_id=?',[$m['id']])->fetchColumn())continue;?><div class="card"><p>Media <?=(int)$m['id']?> · <?=h($m['mime'])?></p><?php $media=urlh('media',(int)$m['id']);if(str_starts_with($m['mime'],'image/')):?><img class="media" src="<?=h($media)?>" alt="Dokumentasi koleksi: <?=h($r['title'])?>" loading="lazy"><?php elseif(str_starts_with($m['mime'],'audio/')):?><audio class="media" controls preload="none" src="<?=h($media)?>"></audio><?php elseif(str_starts_with($m['mime'],'video/')):?><video class="media" controls preload="none" src="<?=h($media)?>"></video><?php else:?><a href="<?=h($media)?>">Lihat dokumen PDF</a><?php endif;?></div><?php endforeach;?></div>
<?php if($u&&ownerh($u,$r)&&in_array($r['status'],['DRAFT','NEEDS_REVISION'],true)):?><section class="card"><h2>Tambahkan media koleksi</h2><p>Pilih foto, rekaman audio/video atau PDF (maksimum 12 MB). Berkas asli tetap dalam direktori privat dan baru dapat diakses sesuai status izin koleksi.</p><form enctype="multipart/form-data" method="post" action="<?=h(urlh('detail',$id))?>"><?=csrf_input()?><input type="hidden" name="action" value="upload"><input type="hidden" name="id" value="<?=$id?>"><label>Referensi izin perekaman atau akuisisi sebelum dokumentasi<input name="capture_ref" minlength="5" maxlength="180" required></label><label>Media<input type="file" name="media" accept="image/jpeg,image/png,image/webp,audio/*,video/mp4,video/webm,application/pdf" required></label><label>Pilih sumber<select id="capture-source"><option value="file">Berkas perangkat</option><option value="photo">Kamera foto</option><option value="audio">Mikrofon</option><option value="video">Kamera video</option></select></label><button type="submit">Unggah media</button></form><hr><?php posth('submit',$id);?><button type="submit">Ajukan persetujuan</button></form></section><?php endif;?>
<?php if($u&&$u['role']==='holder'&&(int)$u['id']===(int)$r['holder_id']&&$r['status']==='CONSENT_PENDING'):?><section class="card"><h2>Persetujuan pemangku hak</h2><p>Periksa isi serta media sebelum menyatakan izin. Izin hanya dapat dinyatakan bila tersedia bukti persetujuan di arsip privat di luar kolom ini.</p><?php posth('consent',$id);?><label><input type="checkbox" name="record" value="1" required> Izin perekaman terdokumentasi</label><label><input type="checkbox" name="archive" value="1" required> Izin penyimpanan arsip terdokumentasi</label><label><input type="checkbox" name="public" value="1" required> Izin publikasi multimedia secara terbuka terdokumentasi</label><label>Referensi kewenangan pemangku hak<input name="authority_ref" minlength="5" maxlength="180" required></label><fieldset><legend>Media yang boleh ditampilkan kepada publik</legend><p>Media yang tidak dipilih tetap privat. Izin tayang tidak memberikan izin redistribusi atau pelatihan AI.</p><?php foreach($assets as $asset):?><label><input type="checkbox" name="public_assets[]" value="<?=(int)$asset['id']?>">Media <?=(int)$asset['id']?> · <?=h($asset['mime'])?> · Rujukan akuisisi <?=h((string)query('SELECT capture_ref FROM heritage_upload_receipts WHERE asset_id=?',[$asset['id']])->fetchColumn())?></label><?php endforeach;?></fieldset><label>Nomor referensi bukti izin privat (bukan identitas/isi bukti)<input name="evidence_ref" minlength="5" maxlength="250" required></label><div class="actions"><button name="decision" value="approve">Setujui untuk kurasi</button><button name="decision" value="reject" class="danger" formnovalidate>Tolak / revisi</button></div></form></section><?php endif;?>
<?php if($u&&in_array($u['role'],['curator','admin'],true)&&$r['status']==='CURATION'):
 $g=query('SELECT * FROM heritage_rights WHERE collection_id=?',[$id])->fetch();?>
 <?php if($g):?><section class="card"><h2>Telaah izin dan media</h2><p>Rujukan kewenangan <?=h($g['authority_ref'])?> · Bukti izin <?=h($g['evidence_ref'])?></p><p>Media dengan izin tayang: <?=h(implode(', ',query('SELECT a.id FROM heritage_assets a JOIN heritage_asset_grants g ON g.asset_id=a.id WHERE a.collection_id=? ORDER BY a.id',[$id])->fetchAll(PDO::FETCH_COLUMN)) ?: 'Tidak ada')?></p>
 <?php if($u['role']==='curator'&&!$g['reviewer_id']):?><?php posth('curate',$id);?><button>Saya telah memeriksa bukti dan cakupan izin</button></form><?php endif;?></section><?php endif;endif;?>
<?php if($u&&$u['role']==='admin'&&$r['status']==='CURATION'&&rights_reviewed($id)&&cfg('HERITAGE_PUBLICATION_ENABLED')==='true'):?><section class="card"><h2>Publikasi admin</h2><?php posth('publish',$id);?><button>Publikasikan koleksi terizin</button></form></section><?php endif;?>
<?php if($u&&ownerh($u,$r)&&$r['status']!=='WITHDRAWN'):?><?php posth('withdraw',$id);?><button class="danger">Tarik publikasi / batalkan koleksi</button></form><?php endif;?>
<?php if(publich($r)):?><hr><h2>Perspektif dan kontribusi terkurasi</h2><?php $cs=query("SELECT c.*,u.name FROM heritage_contributions c JOIN users u ON u.id=c.author_id WHERE c.collection_id=? AND (c.status='APPROVED' OR (?=1)) ORDER BY c.id",[$id,$u&&in_array($u['role'],['admin','curator'],true)?1:0])->fetchAll();foreach($cs as $c):?><div class="card"><span class="tag"><?=h($c['status'])?></span><p><?=nl2br(h($c['text_body']))?></p><p class="muted">Perspektif kontributor<?php if($u&&in_array($u['role'],['admin','curator'],true)):?> · <?=h($c['name'])?><?php endif;?></p><?php if($u&&in_array($u['role'],['admin','curator'],true)&&$c['status']==='PENDING'):?><?php posth('moderate',$id);?><input type="hidden" name="contribution_id" value="<?=(int)$c['id']?>"><button name="decision" value="APPROVED">Terima</button> <button name="decision" value="REJECTED">Tolak</button></form><?php endif;?></div><?php endforeach;?><?php if($u&&in_array($u['role'],['holder','curator','admin'],true)):?><section class="card"><h3>Tambahkan perspektif</h3><?php posth('contribute',$id);?><label>Kontribusi beserta konteks / rujukan<textarea name="text_body" minlength="10" maxlength="5000" required></textarea></label><button>Kirim untuk moderasi</button></form></section><?php endif;?><?php endif;?>
<?php else: failh(404);endif;?></main><footer>BUDAYAGO — Dengarkan tutur. Temukan warisan. Jaga ingatan. · <a href="index.php?p=home">Sistem pembelajaran terdahulu</a></footer></body></html>
