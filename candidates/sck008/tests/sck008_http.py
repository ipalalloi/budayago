"""Local HTTP acceptance checks. Requires the fresh SCK008 fixture and its synthetic logins."""
import http.cookiejar
import os
from pathlib import Path
import re
import secrets
import subprocess
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('SCK008_URL')
assert BASE == 'http://127.0.0.1:18768'
assert os.environ['DB_DSN'] == 'mysql:host=127.0.0.1;port=33781;dbname=sck008_fixture;charset=utf8mb4'
assert not (ROOT/'config.php').exists()


def scalar(sql):
    return subprocess.check_output(['php','-r',
        'require $argv[1]; echo query($argv[2])->fetchColumn();',str(ROOT/'app/bootstrap.php'),sql],text=True)


def passed(case):
    print('PASS',case,flush=True)


class Session:
    def __init__(self, role=None):
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}),
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = ''
        if role:
            self.call('/index.php?p=login')
            assert self.call('/index.php?p=login',dict(action='login',email=role+'@example.test',password=os.environ['ADMIN_PASSWORD']))[0] == 200

    def call(self, path, fields=None, media=None):
        body = None
        headers = {}
        if fields is not None:
            fields = dict(csrf=self.token) | fields
            if media:
                boundary = 'SCK'+secrets.token_hex(12)
                parts = [f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode() for key,value in fields.items()]
                parts.extend([f'--{boundary}\r\nContent-Disposition: form-data; name="media"; filename="synthetic.bin"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode(),media,f'\r\n--{boundary}--\r\n'.encode()])
                body = b''.join(parts)
                headers['Content-Type'] = 'multipart/form-data; boundary='+boundary
            else:
                body = urllib.parse.urlencode(fields,doseq=True).encode()
        try:
            response = self.opener.open(urllib.request.Request(BASE+path,body,headers),timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        data = response.read()
        match = re.search(rb'name="csrf" value="([0-9a-f]+)"',data)
        if match: self.token = match[1].decode()
        return response.status,data,response.headers


guest=Session()
teacher,holder,curator,admin,student=[Session(role) for role in ('teacher','holder','curator','admin','student')]
create='/heritage.php?v=create'
detail='/heritage.php?v=detail&id=1'
media='/heritage.php?v=media&id='
title='Sintetis <Arsip> 008'
page=teacher.call(create)[1]
key=re.search(rb'name="draft_key" value="(\w+)"',page)[1].decode()
fields=dict(action='create',draft_key=key,title=title,category='Tutur',region='Wilayah uji',description='Deskripsi sintetis',holder_id=scalar("SELECT id FROM users WHERE role='holder'"))
assert student.call(create)[0]==403
assert teacher.call('/heritage.php',fields)[0]==200
assert teacher.call('/heritage.php',fields)[0]==200 and scalar('SELECT COUNT(*) FROM heritage_collections')=='1'
assert b'&lt;Arsip&gt;' in teacher.call(detail)[1]
passed('LH01 login, RBAC, create/retry idempotent, escaped title')
assert guest.call(detail)[0]==403
assert b'Jenis berkas tidak diizinkan' in teacher.call(detail,dict(action='upload',capture_ref='SYNTH-CAPTURE'),b'synthetic plain text')[1]
assert scalar('SELECT COUNT(*) FROM heritage_assets')=='0'
png=(ROOT/'public/assets/icon-192.png').read_bytes()
assert b'Berkas tersimpan' in teacher.call(detail,dict(action='upload',capture_ref='SYNTH-CAPTURE'),png)[1]
assert b'identik sudah tersimpan' in teacher.call(detail,dict(action='upload',capture_ref='SYNTH-CAPTURE'),png)[1]
audio=subprocess.check_output(['ffmpeg','-loglevel','error','-f','lavfi','-i','sine=frequency=440:duration=0.25','-f','mp3','pipe:1'])
assert b'Berkas tersimpan' in teacher.call(detail,dict(action='upload',capture_ref='SYNTH-CAPTURE'),audio)[1]
assert teacher.call(media+'2')[1]==audio
assert b'Unggahan gagal' in teacher.call(detail,dict(action='upload',capture_ref='SYNTH-CAPTURE'),bytes(12*1024*1024+1))[1]
assert scalar('SELECT COUNT(*) FROM heritage_assets')=='2'
assert guest.call(media+'1')[0]==403
passed('LH02 PNG, MP3, identical retry, oversize and MIME rejection, draft and private stream restrictions')
assert b'CONSENT_PENDING' in teacher.call(detail,dict(action='submit'))[1]
assert teacher.call(detail,dict(action='consent',decision='approve'))[0]==403
grants=dict(action='consent',decision='approve',record=1,archive=1,public=1,evidence_ref='SYNTH-008-REF',authority_ref='SYNTH-008-AUTH')
assert b'Media tidak termasuk' in holder.call(detail,grants|{'public_assets[]':['1','99999']})[1]
assert scalar('SELECT COUNT(*) FROM heritage_rights')=='0' and scalar('SELECT COUNT(*) FROM heritage_asset_grants')=='0'
assert b'CURATION' in holder.call(detail,grants|{'public_assets[]':['1']})[1]
assert admin.call(detail,dict(action='publish'))[0]==403
assert holder.call(detail,dict(action='curate'))[0]==403
assert b'Telaah kurator dicatat' in curator.call(detail,dict(action='curate'))[1]
assert b'PUBLISHED' in admin.call(detail,dict(action='publish'))[1]
passed('LH03 transaction rollback, assigned holder, per-asset grant, curator then admin')
assert b'&lt;Arsip&gt;' in guest.call('/heritage.php?q=Wilayah')[1]
assert b'&lt;Arsip&gt;' not in guest.call('/heritage.php?q=NothingMatches')[1]
public=guest.call(detail)[1]
assert b'Media 1' in public and b'Media 2' not in public and b'SYNTH-008' not in public
status,data,headers=guest.call(media+'1')
assert status==200 and data==png and 'no-store' in headers['Cache-Control']
part=guest.opener.open(urllib.request.Request(BASE+media+'1', headers={'Range':'bytes=0-7'}))
assert part.status==206 and part.read()==png[:8] and part.headers['Content-Range']==f'bytes 0-7/{len(png)}'
assert guest.call(media+'2')[0]==403 and curator.call(media+'2')[0]==200
for path in ('/config.php','/app/bootstrap.php','/database/mysql.sql','/ops/db_inventory.php','/private_media/probe'):
    assert guest.call(path)[0]==404
passed('LH04 gallery search, permitted stream, unselected asset private, evidence hidden')
text='Perspektif sintetis untuk pengujian.'
holder.call(detail,dict(action='contribute',text_body=text))
assert text.encode() not in guest.call(detail)[1]
curator.call(detail,dict(action='moderate',decision='APPROVED',contribution_id=1))
assert text.encode() in guest.call(detail)[1]
assert b'WITHDRAWN' in holder.call(detail,dict(action='withdraw'))[1]
assert guest.call(detail)[0]==403 and guest.call(media+'1')[0]==403 and b'&lt;Arsip&gt;' not in guest.call('/heritage.php')[1]
assert scalar("SELECT COUNT(DISTINCT action) FROM audit_events WHERE action LIKE 'heritage_%'")=='9'
passed('LH05 contribution moderation, withdrawal of gallery/detail/stream, audit')
lesson='/index.php?p=lesson_show&id=1'
page=student.call(lesson)[1]
assert b'Fixture question?' in page and b'fixture-exact-327' not in page
passed('UAT-26 active lesson HTML excludes answer key')
for answer,expected in [('other',b'Jawaban belum tepat'),('fixture-exact-327',b'Jawaban benar')]:
    assert expected in student.call(lesson,dict(action='answer_submit',id=1,answer=answer))[1]
assert scalar("SELECT GROUP_CONCAT(correct ORDER BY id) FROM attempts")=='0,1'
passed('UAT-25 wrong and correct responses persisted in order 0,1')
holder.call('/index.php?p=knowledge_show&id=1',dict(action='knowledge_withdraw',id=1,reason='Synthetic withdrawal'))
assert student.call(lesson,dict(action='answer_submit',id=1,answer='fixture-exact-327'))[0]==403
assert scalar('SELECT COUNT(*) FROM attempts')=='2'
passed('UAT-28 authenticated valid-CSRF submission after withdrawal returns 403; attempts remain 2')
admin.call('/index.php?p=home',dict(action='logout'))
assert b'Kelola pengguna' not in admin.call('/index.php?p=users')[1]
passed('LH06 logout removes administration access')
