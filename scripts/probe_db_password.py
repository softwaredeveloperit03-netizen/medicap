#!/usr/bin/env python3
import json
import re
import time
import urllib.request
from ftplib import FTP
from pathlib import Path

base = Path(r"c:\xampp\htdocs\Medicap\php\phpdevlop\phpmedicap")
cfg_path = base / "db.config.php"
original = cfg_path.read_text(encoding="utf-8")
passwords = [
    "Prajwal@1979",
    "Cppl@1979",
    "prajwal@1979",
    "Cppl@1979 ",
]

ftp = FTP()
ftp.connect("ftp.aurenyxgmp.com", 21, timeout=60)
ftp.login("Medicap@aurenyxgmp.com", "Prajwal@1979")
ftp.set_pasv(True)

working = None
for pw in passwords:
    text = re.sub(
        r"('server'\s*=>\s*array\([\s\S]*?'password'\s*=>\s*)'[^']*'",
        r"\1'" + pw.replace("\\", "\\\\").replace("'", "\\'") + "'",
        original,
        count=1,
    )
    cfg_path.write_text(text, encoding="utf-8")
    with cfg_path.open("rb") as fh:
        ftp.storbinary("STOR /db.config.php", fh)
    time.sleep(0.4)
    try:
        body = urllib.request.urlopen(
            "https://aurenyxgmp.com/php/phpdevlop/phpmedicap/deploy_health.php",
            timeout=30,
        ).read().decode("utf-8", "replace")
        data = json.loads(body)
        msg = "ok" if data.get("ok") else (data.get("exception") or data.get("db_error") or body[:200])
        print(f"{pw!r} => {msg}")
        if data.get("ok"):
            working = pw
            print(json.dumps(data, indent=2)[:2000])
            break
    except Exception as exc:
        print(f"{pw!r} => ERR {exc}")

if working:
    # keep working password in local file
    text = re.sub(
        r"('server'\s*=>\s*array\([\s\S]*?'password'\s*=>\s*)'[^']*'",
        r"\1'" + working.replace("\\", "\\\\").replace("'", "\\'") + "'",
        original,
        count=1,
    )
    cfg_path.write_text(text, encoding="utf-8")
    print("Kept working password in local db.config.php:", working)
else:
    cfg_path.write_text(original, encoding="utf-8")
    print("No working password found; restored original local config")

ftp.quit()
