"""Chromium on loopback; run against the dedicated local fixture only."""
import os
from playwright.sync_api import sync_playwright
base=os.environ['SCK008_URL']
assert base=='http://127.0.0.1:18768'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=os.environ.get('BROWSER_BIN','/usr/bin/chromium'),args=['--no-sandbox','--disable-dev-shm-usage'])
    ctx=browser.new_context(viewport={'width':390,'height':844})
    page=ctx.new_page()
    page.goto(base)
    assert 'heritage.php' in page.url and page.get_by_text('Jelajah Budaya',exact=True).count()==1
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    for width in (320,768,1280,390):
        page.set_viewport_size({'width':width,'height':844})
        assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.evaluate('navigator.serviceWorker.ready')
    manifest=ctx.new_cdp_session(page).send('Page.getAppManifest')
    assert manifest['errors']==[] and 'manifest.webmanifest' in manifest['url']

    page.wait_for_function('navigator.serviceWorker.controller !== null')
    print('PASS PWA01 Chromium '+browser.version+' 390px, public landing, service worker active',flush=True)
    page.get_by_role('link',name='Masuk',exact=True).click()
    page.locator('[name=email]').fill('teacher@example.test')
    page.locator('[name=password]').fill(os.environ['ADMIN_PASSWORD'])
    page.get_by_role('button',name='Masuk').click()
    page.get_by_role('link',name='Rekam Budaya',exact=True).first.click()
    page.locator('[name=title]').fill('Draf perangkat sintetis')
    ctx.set_offline(True)
    page.click('#save-local')
    page.wait_for_function("document.querySelector('#draft-status').textContent.includes('Metadata tersimpan')")
    ctx.set_offline(False)
    page.reload()
    page.wait_for_function("document.querySelector('[name=title]').value === 'Draf perangkat sintetis'")
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.get_by_role('button',name='Simpan ke server').click()
    page.wait_for_url('**/heritage.php?v=detail&id=*')
    page.get_by_role('link',name='Rekam Budaya',exact=True).first.click()
    assert page.locator('[name=title]').input_value()==''
    keys=page.evaluate("async () => (await (await caches.open('budayago-shell-v008-1')).keys()).map(x=>new URL(x.url).pathname).sort()")
    assert keys==sorted(['/offline.html','/assets/heritage.css','/assets/heritage.js','/assets/icon-192.png','/assets/icon-512.png'])
    ctx.set_offline(True)
    page.reload()
    assert page.get_by_role('heading',name='Perangkat sedang luring').is_visible()
    print('PASS PWA02 offline save in open form, reload restoration, server acknowledgement, static-only cache, cold offline fallback',flush=True)
    browser.close()
