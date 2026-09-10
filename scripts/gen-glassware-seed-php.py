#!/usr/bin/env python3
import re
from pathlib import Path

ts = Path(r"c:\xampp\htdocs\Medicap\src\app\master\glassware\shared\standard-glasswares.constants.ts").read_text(encoding="utf-8")
pat = re.compile(
    r"\{\s*name:\s*'([^']+)',\s*capacity:\s*'([^']+)',\s*unit:\s*'([^']+)',\s*"
    r"glassware_class:\s*'([^']+)',\s*description:\s*'([^']+)',\s*make:\s*'([^']+)'\s*\}"
)
rows = []
for m in pat.finditer(ts):
    name, capacity, unit, gclass, desc, make = m.groups()
    rows.append(
        "    array("
        f"'name' => {name!r}, "
        f"'capacity' => {capacity!r}, "
        f"'unit' => {unit!r}, "
        f"'glassware_class' => {gclass!r}, "
        f"'description' => {desc!r}, "
        f"'make' => {make!r}"
        ")"
    )
out = Path(r"c:\xampp\htdocs\Medicap\php\phpdevlop\phpmedicap\qc\standard_glasswares_seed.php")
out.write_text(
    "<?php\n"
    "/** Default laboratory glassware seed list for Glassware Master. */\n"
    "function medicap_standard_glasswares_seed() {\n"
    "    return array(\n"
    + ",\n".join(rows)
    + "\n    );\n"
    "}\n",
    encoding="utf-8",
)
print(f"Wrote {len(rows)} glasswares -> {out}")
