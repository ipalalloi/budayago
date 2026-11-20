'use strict';
const badge = document.querySelector('#netstatus');
function networkStatus() {
  if (badge) badge.textContent = navigator.onLine ? 'Online' : 'Luring · simpan draf teks';
}
networkStatus();
window.addEventListener('online', networkStatus);
window.addEventListener('offline', networkStatus);
if ('serviceWorker' in navigator && isSecureContext) {
  navigator.serviceWorker.register('./sw.js').catch(() => {
    if (badge) badge.textContent = 'Mode daring · layanan luring belum tersedia';
  });
}

const source = document.querySelector('#capture-source');
if (source) {
  const input = source.form.elements.media;
  const accept = input.accept;
  source.addEventListener('change', () => {
    input.accept = ({photo:'image/*', audio:'audio/*', video:'video/*'})[source.value] || accept;
    if (source.value === 'file') input.removeAttribute('capture');
    else input.setAttribute('capture', source.value === 'audio' ? 'user' : 'environment');
  });
}

async function localDraft() {
  const main = document.querySelector('main[data-account]');
  if (!main) return;
  const form = document.querySelector('#heritage-create');
  const status = document.querySelector('#draft-status');
  const say = text => { if (status) status.textContent = text; };
  const request = action => new Promise((resolve, reject) => {
    action.onsuccess = () => resolve(action.result);
    action.onerror = () => reject(action.error);
  });
  // Earlier Alpha records have no owner. Do not read them into an authenticated form.
  const open = indexedDB.open('budayago-account-drafts', 1);
  open.onupgradeneeded = () => open.result.createObjectStore('drafts');
  const db = await request(open);
  const account = main.dataset.account;
  const fields = ['title', 'category', 'region', 'description', 'draft_key'];
  async function update(callback) {
    const tx = db.transaction('drafts', 'readwrite');
    const store = tx.objectStore('drafts');
    const done = new Promise((resolve,reject) => {
      tx.oncomplete = resolve;
      tx.onabort = tx.onerror = () => reject(tx.error);
    });
    const get = store.get(account);
    get.onsuccess = () => callback(store,get.result);
    await done;
  }
  if (main.dataset.savedKey) {
    await update((store, record) => {
      if (record && record.draft_key === main.dataset.savedKey) store.delete(account);
    });
  }
  if (!form) return;
  const record = await request(db.transaction('drafts').objectStore('drafts').get(account));
  if (record) {
    for (const key of fields) form.elements[key].value = record[key] || '';
    say('Draf perangkat dipulihkan. Periksa kembali pemangku hak sebelum mengirim.');
  }
  function save() {
    const record = Object.fromEntries(fields.map(key => [key, form.elements[key].value]));
    return update(store => store.put(record,account));
  }
  form.querySelector('#save-local').onclick = async () => {
    try { await save(); say('Metadata tersimpan di perangkat.'); }
    catch { say('Penyimpanan gagal. Pertahankan tab ini dan coba kembali.'); }
  };
  form.querySelector('#delete-local').onclick = async () => {
    try { await update(store => store.delete(account)); say('Salinan perangkat dihapus.'); }
    catch { say('Salinan belum berhasil dihapus.'); }
  };
  form.addEventListener('submit', async event => {
    event.preventDefault();
    try {
      await save();
      if (navigator.onLine) HTMLFormElement.prototype.submit.call(form);
      else say('Draf tersimpan. Kirim kembali saat jaringan tersedia.');
    } catch { say('Gagal menyimpan draf. Gunakan perangkat dengan penyimpanan tersedia.'); }
  });
}
localDraft().catch(() => {
  const status = document.querySelector('#draft-status');
  if (status) status.textContent = 'Penyimpanan lokal tidak tersedia. Pertahankan tab hingga server mengonfirmasi.';
});
