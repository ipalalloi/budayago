# UAT v0.2 (all NOT EXECUTED on device/database)
- LH01 HTTPS app manifest valid / install prompt / standalone launch
- LH02 offline static assets cached, HTML/private API not cached
- LH03 offline metadata draft saved/reopened without leaking personal data
- LH04 no silent sync, no offline media claim
- LH05 unauthenticated cannot create/upload/access private records
- LH06 role checks including admin/holder separation
- LH07 form CSRF negative POST
- LH08 create collection with verified holder
- LH09 upload valid image/audio small file, mime sniff
- LH10 deny executable/polyglot oversized media (manual review)
- LH11 consent pending requires assigned holder, evidence reference, distinct permissions
- LH12 admin cannot publish before holder approval
- LH13 publish opens public gallery and media
- LH14 withdrawal hides gallery and blocks direct media access (no caches/CDN)
- LH15 contributor submits version; review before public display
- LH16 failed network/upload preserves local metadata
- LH17 responsive and assistive accessibility review
- LH18 baseline SCK-007 regression UAT-25/26/28
- LH19 install on Android and iOS browser where supported
- LH20 staging backup restore / safe rollback drill

Mark PASS only after attaching exact HTTP/DB/device evidence with timestamp and environment ID. Never infer real UAT from CLI tests.
