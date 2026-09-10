/**
 * Shared parsing of saved eBMR step/substep payloads into table-friendly views
 * (same column labels as Proceed / check screen) and HTML for print/PDF preview.
 */

export type EbmrPayloadView =
  | { kind: 'empty' }
  | {
      kind: 'row-table';
      columns: { key: string; label: string }[];
      rows: any[];
    }
  | {
      kind: 'wrapper';
      meta: { label: string; value: string }[];
      clearance: EbmrPayloadView;
    }
  | { kind: 'kv'; pairs: { label: string; value: string }[] }
  | { kind: 'text'; text: string };

const COL_PERSONS: { key: string; label: string }[] = [
  { key: 'emp_name', label: 'Name of the Person' },
  { key: 'emp_id', label: 'Employee Code' },
  { key: 'department', label: 'Department' },
  { key: 'designation', label: 'Designation' },
  { key: 'joining_date', label: 'Joining date' },
];

const COL_RECONCILIATION_DQU: { key: string; label: string }[] = [
  { key: 'Description', label: 'Description' },
  { key: 'Quantity', label: 'Quantity' },
  { key: 'UOM', label: 'UOM' },
];

const COL_RAW_MATERIAL: { key: string; label: string }[] = [
  { key: 'material_code', label: 'Material Code' },
  { key: 'material_name', label: 'Ingredients' },
  { key: 'grade', label: 'Specification / Grade' },
  { key: 'qty', label: 'Quantity (mg/ml)' },
  { key: 'total_qty', label: 'Quantity (mg/vial)' },
  { key: 'Qty2', label: 'Quantity (Kg/batch)' },
];

const COL_PRIMARY_PM: { key: string; label: string }[] = [
  { key: 'material_code', label: 'Item Code Number' },
  { key: 'material_name', label: 'Primary Packing Material' },
  { key: 'gradeName', label: 'Grade' },
  { key: 'manufacturer', label: 'Manufacturer' },
  { key: 'unit_name', label: 'Unit' },
  { key: 'qty', label: 'Standard Quantity' },
  { key: 'total_qty', label: 'Quantity with 5% Excess' },
];

const COL_EQUIPMENT_USED: { key: string; label: string }[] = [
  { key: 'equipment_name', label: 'EQUIPMENT NAME' },
  { key: 'make', label: 'MAKE' },
  { key: 'equipment_code', label: 'EQUIPMENT I.D.' },
];

const COL_VIAL_SEALING_CHECK: { key: string; label: string }[] = [
  { key: 'date', label: 'Date' },
  { key: 'time', label: 'Time' },
  { key: 'crimping', label: 'Crimping' },
  { key: 'rolling', label: 'Rolling' },
  { key: 'colourVariation', label: 'Colour variation' },
  { key: 'intactSeal', label: 'Intact seal' },
  { key: 'dented', label: 'Dented' },
  { key: 'cakeSize', label: 'Cake size' },
];

const EBMR_CHECK_STEP_COLUMNS: Record<string, { key: string; label: string }[]> = {
  'Persons involved': COL_PERSONS,
  'PERSONS MAKING ENTRIES IN THE BATCH PACKING RECORD (BPR)': COL_PERSONS,
  'Reconciliation after filling': COL_RECONCILIATION_DQU,
  'Reconciliation after sealing of vials': COL_RECONCILIATION_DQU,
  'List of equipments used': COL_EQUIPMENT_USED,
  'In Process Record for Vial Sealing Check': COL_VIAL_SEALING_CHECK,
  'Raw Material': COL_RAW_MATERIAL,
  'Primary Packaging Material': COL_PRIMARY_PM,
  'General Instructions': [
    { key: 'general_instruction', label: 'General instruction' },
  ],
};

const EBMR_FINGERPRINT_LAYOUTS: {
  requiredKeys: string[];
  columns: { key: string; label: string }[];
}[] = [
  {
    requiredKeys: [
      'filterDesc',
      'make',
      'catalogueNo',
      'lotNo',
      'location',
      'eqId',
      'test',
      'limit',
      'activityDate',
      'from',
      'to',
      'result',
      'doneBy',
      'checkedBy',
      'verifiedBy',
    ],
    columns: [
      { key: 'filterDesc', label: 'Filter Description' },
      { key: 'make', label: 'Make' },
      { key: 'catalogueNo', label: 'Catalogue No.' },
      { key: 'lotNo', label: 'Lot No. / Sr. No.' },
      { key: 'location', label: 'Location' },
      { key: 'eqId', label: 'Equipment ID' },
      { key: 'test', label: 'Test' },
      { key: 'limit', label: 'Limit' },
      { key: 'activityDate', label: 'Activity date' },
      { key: 'from', label: 'From' },
      { key: 'to', label: 'To' },
      { key: 'result', label: 'Result' },
      { key: 'doneBy', label: 'Done by' },
      { key: 'checkedBy', label: 'Checked by' },
      { key: 'verifiedBy', label: 'Verified by' },
    ],
  },
  {
    requiredKeys: [
      'date',
      'equipmentId',
      'cycleStartTime',
      'sterilizationFrom',
      'sterilizationTo',
      'cycleEndTime',
      'doneBy',
      'checkedBy',
      'verifiedBy',
    ],
    columns: [
      { key: 'date', label: 'Date' },
      { key: 'equipmentId', label: 'Equipment ID' },
      { key: 'cycleStartTime', label: 'Cycle Start Time' },
      { key: 'sterilizationFrom', label: 'Sterilization From Time' },
      { key: 'sterilizationTo', label: 'Sterilization To Time' },
      { key: 'cycleEndTime', label: 'Cycle End Time' },
      { key: 'doneBy', label: 'Done by Production' },
      { key: 'checkedBy', label: 'Checked by Production (Sign/Date)' },
      { key: 'verifiedBy', label: 'Verified by IPQA (Sign/Date)' },
    ],
  },
  {
    requiredKeys: [
      'date',
      'equipmentId',
      'startTime',
      'endTime',
      'doneBy',
      'checkedBy',
      'verifiedBy',
    ],
    columns: [
      { key: 'date', label: 'Date' },
      { key: 'equipmentId', label: 'Equipment ID' },
      { key: 'startTime', label: 'Start Time' },
      { key: 'endTime', label: 'End Time' },
      { key: 'doneBy', label: 'Done By (Production)' },
      { key: 'checkedBy', label: 'Checked By Production (Sign & Date)' },
      { key: 'verifiedBy', label: 'Verified By IPQA (Sign & Date)' },
    ],
  },
  {
    requiredKeys: [
      'material_code',
      'material_name',
      'grade',
      'qty',
      'total_qty',
      'Qty2',
    ],
    columns: COL_RAW_MATERIAL,
  },
  {
    requiredKeys: [
      'material_code',
      'material_name',
      'gradeName',
      'unit_name',
      'qty',
      'total_qty',
    ],
    columns: COL_PRIMARY_PM,
  },
  {
    requiredKeys: [
      'material_code',
      'material_name',
      'unit_name',
      'total_qty',
      'Vendor',
      'Store_personName',
    ],
    columns: [
      { key: 'material_code', label: 'Item Code' },
      { key: 'material_name', label: 'Material Description' },
      { key: 'unit_name', label: 'UOM' },
      { key: 'total_qty', label: 'Standard Qty./Batch' },
      { key: 'Vendor', label: 'Vendor' },
      { key: 'Store_personName', label: 'Issued by Stores (Sign/Date)' },
    ],
  },
  {
    requiredKeys: [
      'date',
      'time',
      'crimping',
      'rolling',
      'colourVariation',
      'intactSeal',
      'dented',
      'cakeSize',
    ],
    columns: COL_VIAL_SEALING_CHECK,
  },
  {
    requiredKeys: [
      'item',
      'cleaning_from',
      'cleaning_to',
      'duration',
      'done_by',
      'checked_by',
      'remark',
    ],
    columns: [
      { key: 'item', label: 'Item' },
      { key: 'cleaning_from', label: 'Cleaning Time From (HH:MM)' },
      { key: 'cleaning_to', label: 'Cleaning Time To (HH:MM)' },
      { key: 'duration', label: 'Cleaning Duration' },
      { key: 'done_by', label: 'Done by' },
      { key: 'checked_by', label: 'Checked by' },
      { key: 'remark', label: 'Remark' },
    ],
  },
  {
    requiredKeys: ['filterLocation', 'filterType', 'integrityDoneOn'],
    columns: [
      { key: 'filterLocation', label: 'Filter Location' },
      { key: 'filterType', label: 'Filter Type' },
      { key: 'integrityDoneOn', label: 'Integrity Done On' },
      { key: 'doneBy', label: 'Done By Production (Name)' },
      { key: 'doneByDate', label: 'Done By Date' },
      { key: 'checkedBy', label: 'Checked By Production' },
      { key: 'checkedByDate', label: 'Checked By Date' },
      { key: 'verifiedBy', label: 'Verified By IPQA' },
      { key: 'verifiedByDate', label: 'Verified By Date' },
    ],
  },
  {
    requiredKeys: ['Description', 'Quantity', 'UOM'],
    columns: COL_RECONCILIATION_DQU,
  },
  {
    requiredKeys: ['equipment_name', 'make', 'equipment_code'],
    columns: COL_EQUIPMENT_USED,
  },
  {
    requiredKeys: ['emp_name', 'emp_id', 'department'],
    columns: COL_PERSONS,
  },
  {
    requiredKeys: ['activity', 'remark'],
    columns: [
      { key: 'activity', label: 'Activity' },
      { key: 'remark', label: 'Remark' },
    ],
  },
  {
    requiredKeys: ['general_instruction'],
    columns: [{ key: 'general_instruction', label: 'General instruction' }],
  },
  {
    requiredKeys: ['key', 'value'],
    columns: [
      { key: 'key', label: 'Abbreviation' },
      { key: 'value', label: 'Meaning' },
    ],
  },
].sort((a, b) => b.requiredKeys.length - a.requiredKeys.length);

function humanizeKey(key: string): string {
  const spaced = key
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/_/g, ' ');
  return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

function coercePayload(raw: unknown): unknown {
  if (typeof raw === 'string') {
    const t = raw.replace(/^\uFEFF/, '').trim();
    if (
      (t.startsWith('[') && t.endsWith(']')) ||
      (t.startsWith('{') && t.endsWith('}'))
    ) {
      try {
        return JSON.parse(t);
      } catch {
        return raw;
      }
    }
  }
  return raw;
}

function looksLikeSaveWrapper(o: Record<string, unknown>): boolean {
  const keys = Object.keys(o);
  const hasMeta =
    'lineclearance' in o &&
    (keys.includes('step') ||
      keys.includes('stage_id') ||
      keys.includes('step_id'));
  return hasMeta && keys.length <= 8;
}

function columnsForStepName(
  stepName: string | undefined
): { key: string; label: string }[] | null {
  if (!stepName) {
    return null;
  }
  const n = stepName.trim();
  if (EBMR_CHECK_STEP_COLUMNS[n]) {
    return EBMR_CHECK_STEP_COLUMNS[n];
  }
  const found = Object.keys(EBMR_CHECK_STEP_COLUMNS).find(
    (k) => k.toLowerCase() === n.toLowerCase()
  );
  return found ? EBMR_CHECK_STEP_COLUMNS[found] : null;
}

function unionRowKeys(rows: any[]): Set<string> {
  const s = new Set<string>();
  for (const r of rows) {
    if (r != null && typeof r === 'object' && !Array.isArray(r)) {
      for (const k of Object.keys(r)) {
        s.add(k);
      }
    }
  }
  return s;
}

function mergeColumnsWithExtraKeys(
  base: { key: string; label: string }[],
  allRowKeys: Set<string>
): { key: string; label: string }[] {
  const seen = new Set(base.map((c) => c.key));
  const extra = [...allRowKeys].filter((k) => !seen.has(k)).sort();
  const rest = extra.map((k) => ({ key: k, label: humanizeKey(k) }));
  return [...base, ...rest];
}

function columnsFromFingerprint(
  rowKeys: Set<string>
): { key: string; label: string }[] | null {
  for (const fp of EBMR_FINGERPRINT_LAYOUTS) {
    if (fp.requiredKeys.every((k) => rowKeys.has(k))) {
      return fp.columns;
    }
  }
  return null;
}

function dynamicColumnsFromRows(rows: any[]): { key: string; label: string }[] {
  const keys = [...unionRowKeys(rows)].sort();
  if (keys.length === 0) {
    return [];
  }
  return keys.map((key) => ({ key, label: humanizeKey(key) }));
}

function resolveTableColumns(
  stepName: string | undefined,
  rows: any[]
): { key: string; label: string }[] {
  const allKeys = unionRowKeys(rows);
  const fromTitle = columnsForStepName(stepName);
  if (fromTitle) {
    return mergeColumnsWithExtraKeys(fromTitle, allKeys);
  }
  const first = rows.find(
    (r) => r != null && typeof r === 'object' && !Array.isArray(r)
  );
  if (!first) {
    return [];
  }
  const rowKeys = new Set(Object.keys(first));
  const fromFp = columnsFromFingerprint(rowKeys);
  if (fromFp) {
    return mergeColumnsWithExtraKeys(fromFp, allKeys);
  }
  return dynamicColumnsFromRows(rows);
}

function shallowObjectToPairs(
  o: Record<string, unknown>
): { label: string; value: string }[] {
  return Object.keys(o).map((k) => ({
    label: humanizeKey(k),
    value: formatEbmrCell(o[k]),
  }));
}

function parseJsonArrayIfNeeded(v: unknown): unknown[] | null {
  if (Array.isArray(v)) {
    return v;
  }
  if (typeof v === 'string') {
    const t = v.trim();
    if (!t) {
      return null;
    }
    if (!(t.startsWith('[') && t.endsWith(']'))) {
      return null;
    }
    try {
      const parsed = JSON.parse(t);
      return Array.isArray(parsed) ? parsed : null;
    } catch {
      return null;
    }
  }
  return null;
}

function pickStructuredTableObjectFields(
  o: Record<string, unknown>
): { columns: unknown[]; rows: unknown[] } | null {
  const directColumns =
    o['columns'] ?? o['tableColumns'] ?? o['table_columns'] ?? o['column'];
  const directRows = o['rows'] ?? o['tableRows'] ?? o['table_rows'] ?? o['data'];
  const c0 = parseJsonArrayIfNeeded(directColumns);
  const r0 = parseJsonArrayIfNeeded(directRows);
  if (c0 && r0) {
    return { columns: c0, rows: r0 };
  }

  const keys = Object.keys(o);
  const colKey = keys.find((k) => /(column|header)/i.test(k));
  const rowKey = keys.find((k) => /(row|data)/i.test(k));
  if (!colKey || !rowKey) {
    return null;
  }
  const c1 = parseJsonArrayIfNeeded(o[colKey]);
  const r1 = parseJsonArrayIfNeeded(o[rowKey]);
  if (c1 && r1) {
    return { columns: c1, rows: r1 };
  }
  return null;
}

function looksLikeStructuredTableObject(
  o: Record<string, unknown>
): boolean {
  return !!pickStructuredTableObjectFields(o);
}

function normalizeColumnsFromStructuredObject(
  rawCols: unknown[]
): { key: string; label: string }[] {
  const out: { key: string; label: string }[] = [];
  const used = new Set<string>();
  for (let i = 0; i < rawCols.length; i++) {
    const c = rawCols[i];
    let key = '';
    let label = '';
    if (typeof c === 'string') {
      key = c.trim();
      label = key;
    } else if (c && typeof c === 'object' && !Array.isArray(c)) {
      const co = c as Record<string, unknown>;
      key = String(
        co['key'] ??
          co['field'] ??
          co['name'] ??
          co['id'] ??
          co['header'] ??
          ''
      ).trim();
      label = String(
        co['label'] ??
          co['header'] ??
          co['title'] ??
          co['name'] ??
          co['key'] ??
          ''
      ).trim();
    }
    if (!key) {
      key = `col_${i + 1}`;
    }
    if (!label) {
      label = humanizeKey(key);
    }
    if (used.has(key)) {
      continue;
    }
    used.add(key);
    out.push({ key, label });
  }
  return out;
}

function normalizeRowsFromStructuredObject(
  rawRows: unknown[],
  colKeys: string[]
): any[] {
  const rows: any[] = [];
  for (let i = 0; i < rawRows.length; i++) {
    const row = rawRows[i];
    if (Array.isArray(row)) {
      const obj: Record<string, unknown> = {};
      row.forEach((v, idx) => {
        const key = colKeys[idx] || `col_${idx + 1}`;
        obj[key] = v;
      });
      rows.push(obj);
      continue;
    }
    if (row && typeof row === 'object') {
      rows.push(row);
      continue;
    }
    rows.push({ value: row });
  }
  return rows;
}

function groupedArrayRowsFromObject(
  o: Record<string, unknown>
): { key: string; rows: any[] }[] {
  const out: { key: string; rows: any[] }[] = [];
  for (const k of Object.keys(o)) {
    const v = o[k];
    if (!Array.isArray(v)) {
      continue;
    }
    const rows = v.filter(
      (r) => r != null && typeof r === 'object' && !Array.isArray(r)
    );
    if (rows.length > 0) {
      out.push({ key: k, rows: rows as any[] });
    }
  }
  return out;
}

export function formatEbmrCell(value: unknown): string {
  if (value == null || value === '') {
    return '';
  }
  if (typeof value === 'object') {
    if (Array.isArray(value)) {
      if (value.length === 0) {
        return '—';
      }
      if (
        value.every(
          (x) =>
            x == null || ['string', 'number', 'boolean'].includes(typeof x)
        )
      ) {
        return value.map((x) => String(x)).join(', ');
      }
      return value
        .map((x, i) => `${i + 1}. ${formatEbmrCell(x)}`)
        .join('; ');
    }
    const o = value as Record<string, unknown>;
    const keys = Object.keys(o);
    if (keys.length === 0) {
      return '—';
    }
    return keys
      .map((k) => `${humanizeKey(k)}: ${formatEbmrCell(o[k])}`)
      .join('; ');
  }
  return String(value);
}

export function buildEbmrPayloadView(
  stepName: string | undefined,
  raw: unknown
): EbmrPayloadView {
  const data = coercePayload(raw);
  if (data == null || data === '') {
    return { kind: 'empty' };
  }
  if (Array.isArray(data)) {
    if (data.length === 0) {
      return { kind: 'empty' };
    }
    const objects = data.filter(
      (r) => r != null && typeof r === 'object' && !Array.isArray(r)
    );
    if (objects.length === 0) {
      return { kind: 'text', text: formatEbmrCell(data) };
    }
    const columns = resolveTableColumns(stepName, data as any[]);
    if (columns.length === 0) {
      return { kind: 'text', text: formatEbmrCell(data) };
    }
    return {
      kind: 'row-table',
      columns,
      rows: data as any[],
    };
  }
  if (typeof data === 'object' && data !== null) {
    const o = data as Record<string, unknown>;
    if (looksLikeStructuredTableObject(o)) {
      const parts = pickStructuredTableObjectFields(o)!;
      const rawCols = parts.columns;
      const rawRows = parts.rows;
      let columns = normalizeColumnsFromStructuredObject(rawCols);
      const rows = normalizeRowsFromStructuredObject(
        rawRows,
        columns.map((c) => c.key)
      );
      if (columns.length === 0) {
        columns = resolveTableColumns(stepName, rows);
      }
      if (columns.length > 0) {
        return {
          kind: 'row-table',
          columns,
          rows,
        };
      }
      return { kind: 'text', text: formatEbmrCell(rawRows) };
    }
    const grouped = groupedArrayRowsFromObject(o);
    if (grouped.length > 0) {
      const mergedRows: any[] = [];
      for (const g of grouped) {
        for (const row of g.rows) {
          mergedRows.push({
            __section: humanizeKey(g.key),
            ...(row as Record<string, unknown>),
          });
        }
      }
      const columns = resolveTableColumns(stepName, mergedRows);
      return {
        kind: 'row-table',
        columns: [
          { key: '__section', label: 'Section' },
          ...columns.filter((c) => c.key !== '__section'),
        ],
        rows: mergedRows,
      };
    }
    if (looksLikeSaveWrapper(o)) {
      const meta: { label: string; value: string }[] = [];
      if (o['step'] != null) {
        meta.push({ label: 'Step', value: String(o['step']) });
      }
      if (o['stage_id'] != null) {
        meta.push({ label: 'Stage ID', value: String(o['stage_id']) });
      }
      if (o['step_id'] != null) {
        meta.push({ label: 'Step ID', value: String(o['step_id']) });
      }
      const clearanceRaw = o['lineclearance'];
      const clearance = buildEbmrPayloadView(
        typeof o['step'] === 'string' ? (o['step'] as string) : stepName,
        clearanceRaw
      );
      return { kind: 'wrapper', meta, clearance };
    }
    const objectKeys = Object.keys(o);
    if (objectKeys.length > 0) {
      // Generic fallback for unknown form payloads:
      // always render object fields as a header row + single data row.
      return {
        kind: 'row-table',
        columns: objectKeys.map((k) => ({ key: k, label: humanizeKey(k) })),
        rows: [o],
      };
    }
    return { kind: 'empty' };
  }
  return { kind: 'text', text: String(data) };
}

function escapeHtmlPrint(s: string): string {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function printTableFromPairs(pairs: { label: string; value: string }[]): string {
  const rows = pairs
    .map(
      (p) =>
        `<tr><th scope="row" class="bmr-k">${escapeHtmlPrint(p.label)}</th><td class="bmr-v">${escapeHtmlPrint(p.value)}</td></tr>`
    )
    .join('');
  return `<table class="bmr bmr-kv"><tbody>${rows}</tbody></table>`;
}

function printRowTableHtml(v: {
  columns: { key: string; label: string }[];
  rows: any[];
}): string {
  if (!v.columns.length) {
    return '';
  }
  const th =
    '<th class="bmr-idx">Sr. No.</th>' +
    v.columns
      .map((c) => `<th>${escapeHtmlPrint(c.label)}</th>`)
      .join('');
  const body = v.rows
    .map((r, i) => {
      const tds = v.columns
        .map((c) => `<td>${escapeHtmlPrint(formatEbmrCell(r?.[c.key]))}</td>`)
        .join('');
      return `<tr><td class="bmr-idx">${i + 1}</td>${tds}</tr>`;
    })
    .join('');
  return `<table class="bmr bmr-grid"><thead><tr>${th}</tr></thead><tbody>${body}</tbody></table>`;
}

/** BMR-style tables for browser print / “Save as PDF” (no raw JSON). */
export function payloadViewToPrintHtml(v: EbmrPayloadView): string {
  switch (v.kind) {
    case 'empty':
      return '<p class="bmr-empty">—</p>';
    case 'text':
      return `<div class="bmr-text">${escapeHtmlPrint(v.text)}</div>`;
    case 'kv':
      return printTableFromPairs(v.pairs);
    case 'row-table':
      return printRowTableHtml(v);
    case 'wrapper': {
      let html = printTableFromPairs(v.meta);
      if (v.clearance.kind !== 'empty') {
        html +=
          '<p class="bmr-subheading">Line clearance / checklist</p>' +
          payloadViewToPrintHtml(v.clearance);
      } else {
        html +=
          '<p class="bmr-subheading">Line clearance / checklist</p><p class="bmr-empty">—</p>';
      }
      return html;
    }
  }
}
