#!/usr/bin/env python3
"""Extract cleaned QC lab chemicals from Zuma SQL dump into Medicap seed JSON/TS/PHP."""
import json
import re
from pathlib import Path

ZUMA_SQL = Path(r"c:\xampp\htdocs\Zuma\_db_backups\local-aurenyxgmp_zuma-20260710-182829.sql")
OUT_DIR = Path(r"c:\xampp\htdocs\Medicap\src\app\master\chemical\shared")
OUT_JSON = OUT_DIR / "standard-chemicals.json"
OUT_TS = OUT_DIR / "standard-chemicals.constants.ts"
OUT_PHP = Path(r"c:\xampp\htdocs\Medicap\php\phpdevlop\phpmedicap\qc\standard_chemicals_seed.php")

JUNK_NAMES = {
    "test", "sdsd", "ddsa", "acid", "floride", "sggs",
    "schemical1", "schemical2", "l-quanine",
}
JUNK_CAS = {
    "asd", "qc", "store", "test ", "789", "7391", "0123", "89",
    "dddd", "sda", "55", "35", "4530", "0001", "1512", "ca056",
    "c-121", "95555555555555555555559", "5646", "test",
}


def split_fields(chunk: str):
    fields = []
    cur = ""
    inq = False
    i = 0
    while i < len(chunk):
        c = chunk[i]
        if inq:
            if c == "\\" and i + 1 < len(chunk):
                cur += chunk[i + 1]
                i += 2
                continue
            if c == "'":
                if i + 1 < len(chunk) and chunk[i + 1] == "'":
                    cur += "'"
                    i += 2
                    continue
                inq = False
                i += 1
                continue
            cur += c
            i += 1
            continue
        if c == "'":
            inq = True
            i += 1
            continue
        if c == ",":
            fields.append(cur)
            cur = ""
            i += 1
            continue
        cur += c
        i += 1
    fields.append(cur)
    return fields


def main():
    line = None
    with ZUMA_SQL.open("r", encoding="utf-8", errors="ignore") as f:
        for raw in f:
            if raw.startswith("INSERT INTO `chemical` VALUES"):
                line = raw
                break
    if not line:
        raise SystemExit("chemical INSERT not found")

    data = line[len("INSERT INTO `chemical` VALUES") :].strip().rstrip(";")
    if data.startswith("("):
        data = data[1:]
    if data.endswith(")"):
        data = data[:-1]
    chunks = re.split(r"\),\(", data)

    seen = set()
    out = []
    for ch in chunks:
        fields = split_fields(ch)
        if len(fields) < 8:
            continue
        name = fields[3].replace("\t", "").strip()
        cas = fields[6].strip()
        mw = fields[4].strip()
        grade = (fields[7].strip() or "AR")
        if grade == "Commecial":
            grade = "Commercial"
        if not name or name.lower() in JUNK_NAMES:
            continue
        if cas.lower() in JUNK_CAS and len(name) < 8:
            continue
        if not mw and not cas:
            continue
        key = name.lower()
        if key in seen:
            continue
        # Drop empty-name / placeholder HCl duplicates without CAS
        if name.lower() == "hydrochloric acid" and not cas:
            continue
        seen.add(key)
        unit = "L" if any(x in name.lower() for x in ("hplc", "solution", "methanol", "acetonitrile", "acetone", "ethanol", "chloroform", "toluene", "dioxane")) else "g"
        out.append(
            {
                "chemical_name": name,
                "molecular_wt": mw,
                "cas_name": cas,
                "grade": grade,
                "unit": unit,
                "chem_type": "Chemical",
            }
        )

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    OUT_JSON.write_text(json.dumps(out, indent=2), encoding="utf-8")

    ts_items = ",\n".join(
        "  {\n"
        + f"    chemical_name: {json.dumps(r['chemical_name'])},\n"
        + f"    molecular_wt: {json.dumps(r['molecular_wt'])},\n"
        + f"    cas_name: {json.dumps(r['cas_name'])},\n"
        + f"    grade: {json.dumps(r['grade'])},\n"
        + f"    unit: {json.dumps(r['unit'])},\n"
        + f"    chem_type: {json.dumps(r['chem_type'])},\n"
        + "  }"
        for r in out
    )
    OUT_TS.write_text(
        "export interface StandardChemicalTemplate {\n"
        "  chemical_name: string;\n"
        "  molecular_wt: string;\n"
        "  cas_name: string;\n"
        "  grade: string;\n"
        "  unit: string;\n"
        "  chem_type: string;\n"
        "}\n\n"
        "/** QC lab chemical master templates (from standard GMP chemical set). */\n"
        "export const STANDARD_CHEMICAL_TEMPLATES: StandardChemicalTemplate[] = [\n"
        + ts_items
        + "\n];\n",
        encoding="utf-8",
    )

    php_rows = []
    for r in out:
        php_rows.append(
            "    array("
            + "'chemical_name' => " + json.dumps(r["chemical_name"]) + ", "
            + "'molecular_wt' => " + json.dumps(r["molecular_wt"]) + ", "
            + "'cas_name' => " + json.dumps(r["cas_name"]) + ", "
            + "'grade' => " + json.dumps(r["grade"]) + ", "
            + "'unit' => " + json.dumps(r["unit"]) + ", "
            + "'chem_type' => " + json.dumps(r["chem_type"])
            + ")"
        )
    OUT_PHP.write_text(
        "<?php\n"
        "/** Default QC lab chemical seed list for Chemical Master. */\n"
        "function medicap_standard_chemicals_seed() {\n"
        "    return array(\n"
        + ",\n".join(php_rows)
        + "\n    );\n"
        "}\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(out)} chemicals")
    print(f"  {OUT_JSON}")
    print(f"  {OUT_TS}")
    print(f"  {OUT_PHP}")


if __name__ == "__main__":
    main()
