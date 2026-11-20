# BUDAYAGO · Living Heritage Library

**Dengarkan tutur. Temukan warisan. Jaga ingatan.**

Kandidat SCK-008 untuk staging CloudPanel terisolasi, 20 November 2026. Database lokal, alur multipart, otorisasi media dan browser telah diuji menggunakan akun sintetis. Flag publikasi bawaan tertutup. Gunakan folder public sebagai satu-satunya document root.

| Untuk | Buka |
|---|---|
| Validasi input dan perubahan | 01_BASELINE_REGISTRY.md, 02_DISCREPANCY_TRACKER.md, 03_IMPLEMENTATION_STATUS.md |
| Verifikasi paket | SHA256SUMS.txt dan RELEASE_MANIFEST.json. Di Windows, skrip scripts/PREPARE_SCK008.ps1 memeriksa digest luar dan dalam dan membuat direktori baru. |
| Instal staging | ops/CLOUDPANEL.md, ops/preflight.sh, ops/db_inventory.php, config.example.php, bin/install-admin.php dan database/ |
| Uji kembali | ops/COMPATIBILITY_SECURITY.md, tests/run_local_fixture.sh, evidence/ |
| Keputusan rilis | ops/ACCEPTANCE.md, ops/ROLLBACK_MAINTENANCE.md |
| Narasi proposal, roadmap dan kerangka biaya | track-b/ |

Jalur aplikasi publik adalah / dan /heritage.php. Layanan pembelajaran tetap tersedia di /index.php?p=home. Login melalui tautan Masuk di galeri. Hindari memakai dokumentasi Alpha atau skrip SAFE_STAGE_PREPARE lama untuk SCK-008. Manifest bernama SHA256SUMS_ALPHA.txt dan SHA256SUMS_SCK007.txt merujuk snapshot sebelum perbaikan. README_LIVING_HERITAGE.md dan dokumen bertanggal sebelum SCK-008 merupakan catatan historis.
