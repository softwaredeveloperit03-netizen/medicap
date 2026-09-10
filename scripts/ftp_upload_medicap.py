#!/usr/bin/env python3
"""Upload Medicap backend PHP and/or frontend dist via FTP."""
from __future__ import annotations

import argparse
import os
import sys
from ftplib import FTP, error_perm
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

# Backend lives at <root>/phpmedicap; older checkouts nested it under php/phpdevlop.
_backend_local = ROOT / "phpmedicap"
if not _backend_local.exists():
    _backend_local = ROOT / "php" / "phpdevlop" / "phpmedicap"

BACKEND = {
    "host": "ftp.aurenyxgmp.com",
    "user": "Medicap@aurenyxgmp.com",
    "password": "Prajwal@1979",
    "local": _backend_local,
    "remote": "/",
}
FRONTEND = {
    "host": "ftp.aurenyxgmp.com",
    "user": "ftpmed@medicap.aurenyxgmp.com",
    "password": "Prajwal@1979",
    "local": ROOT / "dist" / "apidemo",
    "remote": "/",
}

SKIP_NAMES = {
    ".ftpquota",
    "logs.txt",
    "token.txt",
    "sops.zip",
    ".git",
    ".DS_Store",
    "Thumbs.db",
}
SKIP_SUFFIXES = {".log", ".zip.bak"}
SKIP_DIRS = {"node_modules", ".git", "__pycache__"}


def should_skip(path: Path) -> bool:
    if path.name in SKIP_NAMES:
        return True
    if path.suffix.lower() in SKIP_SUFFIXES:
        return True
    # Skip huge runtime dumps
    if path.name.lower().endswith(".txt") and path.stat().st_size > 5_000_000:
        return True
    return False


def ensure_dir(ftp: FTP, remote_dir: str) -> None:
    parts = [p for p in remote_dir.replace("\\", "/").split("/") if p]
    path = ""
    for part in parts:
        path += "/" + part
        try:
            ftp.mkd(path)
        except error_perm:
            pass


def upload_file(ftp: FTP, local: Path, remote: str) -> None:
    remote = remote.replace("\\", "/")
    parent = "/".join(remote.split("/")[:-1])
    if parent and parent != "/":
        ensure_dir(ftp, parent)
    with local.open("rb") as fh:
        ftp.storbinary(f"STOR {remote}", fh)


def walk_upload(ftp: FTP, local_root: Path, remote_root: str) -> tuple[int, int]:
    uploaded = 0
    skipped = 0
    for root, dirs, files in os.walk(local_root):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        root_path = Path(root)
        rel = root_path.relative_to(local_root).as_posix()
        remote_dir = remote_root.rstrip("/") + ("/" + rel if rel != "." else "")
        if remote_dir == "":
            remote_dir = "/"
        for name in files:
            local_file = root_path / name
            if should_skip(local_file):
                skipped += 1
                continue
            remote_file = (remote_dir.rstrip("/") + "/" + name).replace("//", "/")
            try:
                upload_file(ftp, local_file, remote_file)
                uploaded += 1
                if uploaded % 25 == 0:
                    print(f"  uploaded {uploaded} files...", flush=True)
            except Exception as exc:  # noqa: BLE001
                print(f"FAIL {local_file} -> {remote_file}: {exc}", flush=True)
                skipped += 1
    return uploaded, skipped


def run_target(name: str, cfg: dict) -> None:
    local: Path = cfg["local"]
    if not local.exists():
        raise SystemExit(f"{name}: local path missing: {local}")
    print(f"=== {name} FTP upload ===", flush=True)
    print(f"Local:  {local}", flush=True)
    print(f"Remote: {cfg['user']}@{cfg['host']}{cfg['remote']}", flush=True)
    ftp = FTP()
    ftp.connect(cfg["host"], 21, timeout=60)
    ftp.login(cfg["user"], cfg["password"])
    ftp.set_pasv(True)
    uploaded, skipped = walk_upload(ftp, local, cfg["remote"])
    ftp.quit()
    print(f"{name}: uploaded={uploaded}, skipped={skipped}", flush=True)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--backend", action="store_true")
    parser.add_argument("--frontend", action="store_true")
    parser.add_argument("--all", action="store_true")
    args = parser.parse_args()
    if not (args.backend or args.frontend or args.all):
        args.all = True
    if args.all or args.backend:
        run_target("BACKEND", BACKEND)
    if args.all or args.frontend:
        run_target("FRONTEND", FRONTEND)


if __name__ == "__main__":
    main()
