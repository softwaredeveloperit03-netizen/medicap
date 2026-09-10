#!/usr/bin/env python3
"""Generate qc/standard_hplc_columns_seed.php from Angular constants."""
from pathlib import Path
import re

ts_path = Path(r"c:\xampp\htdocs\Medicap\src\app\master\hplc\shared\standard-hplc-columns.constants.ts")
out_path = Path(r"c:\xampp\htdocs\Medicap\php\phpdevlop\phpmedicap\qc\standard_hplc_columns_seed.php")

text = ts_path.read_text(encoding="utf-8")
m = re.search(
    r"export const STANDARD_HPLC_COLUMN_TEMPLATES[^=]*=\s*(\[[\s\S]*?\]);",
    text,
)
if not m:
    raise SystemExit("Could not find STANDARD_HPLC_COLUMN_TEMPLATES")

arr_src = m.group(1)
# Drop TS line comments, then convert object keys to quoted Python dict keys
arr_src = re.sub(r"//.*?$", "", arr_src, flags=re.M)
py = re.sub(r"(\w+)\s*:", r"'\1':", arr_src)
rows = eval(py, {"__builtins__": {}})

def esc(s: str) -> str:
    return str(s).replace("\\", "\\\\").replace("'", "\\'")

lines = [
    "<?php",
    "function medicap_standard_hplc_columns_seed() {",
    "    return array(",
]
for r in rows:
    lines.append("        array(")
    for k, v in r.items():
        lines.append(f"            '{k}' => '{esc(v)}',")
    lines.append("        ),")
lines.append("    );")
lines.append("}")
lines.append("")

out_path.write_text("\n".join(lines), encoding="utf-8")
print(f"Wrote {len(rows)} columns to {out_path}")
