export interface EquipmentUsageRow {
  start_date: string;
  start_time: string;
  end_date: string;
  end_time: string;
  remark: string;
  user_sign: string;
  user_sign_at: string;
}

export interface LogbookEntry {
  entry_id: string;
  activity: string;
  stage_name?: string;
  batch_no: string;
  start_date: string;
  start_time: string;
  end_date: string;
  end_time: string;
  remark: string;
  user_sign: string;
  user_sign_at: string;
  step_id?: number;
}

export interface EquipmentLogbook {
  equipment_code: string;
  equipment_name: string;
  equipment_id: string;
  location: string;
  entries: LogbookEntry[];
}

export function emptyEquipmentUsage(): EquipmentUsageRow {
  return {
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    remark: '',
    user_sign: '',
    user_sign_at: '',
  };
}

export function equipmentKey(eq: any): string {
  return String(eq?.equipment_code || eq?.id_no || '').trim() || String(eq?.name || '').trim();
}

export function findEquipmentMeta(batch: any, key: string, fallback?: any): {
  code: string;
  name: string;
  id: string;
  location: string;
} {
  const sel = batch?.static?.equipment_selection || [];
  const row =
    sel.find(
      (r: any) =>
        equipmentKey(r) === key ||
        String(r?.id_no || '').trim() === key ||
        String(r?.equipment_code || '').trim() === key
    ) || fallback;
  return {
    code: String(row?.equipment_code || row?.id_no || key || '').trim(),
    name: String(row?.name || fallback?.name || '').trim(),
    id: String(row?.id_no || row?.equipment_code || fallback?.id_no || key || '').trim(),
    location: String(row?.location || row?.purpose || fallback?.location || '').trim(),
  };
}

export function normalizeExecLogbook(execTabs: any): any {
  const tabs = execTabs || {};
  if (!tabs.logbook || typeof tabs.logbook !== 'object') tabs.logbook = {};
  if (!tabs.logbook.equipment_books || typeof tabs.logbook.equipment_books !== 'object') {
    tabs.logbook.equipment_books = {};
  }
  if (!Array.isArray(tabs.logbook.rows)) tabs.logbook.rows = [];
  return tabs;
}

/** All equipment referenced in batch steps + static selection. */
export function collectBatchEquipment(batch: any): any[] {
  const map = new Map<string, any>();
  (batch?.static?.equipment_selection || []).forEach((r: any) => {
    const k = equipmentKey(r);
    if (k) map.set(k, { ...r, equipment_code: r.equipment_code || r.id_no });
  });
  (batch?.steps || []).forEach((s: any) => {
    (s?.template?.equipment || []).forEach((eq: any) => {
      const k = equipmentKey(eq);
      if (!k) return;
      if (!map.has(k)) map.set(k, { ...eq });
      else map.set(k, { ...map.get(k), ...eq });
    });
  });
  return Array.from(map.values()).sort((a, b) =>
    String(a.name || '').localeCompare(String(b.name || ''))
  );
}

export function getEquipmentLogbook(execTabs: any, key: string, batch: any, eq?: any): EquipmentLogbook {
  const tabs = normalizeExecLogbook(execTabs);
  const books = tabs.logbook.equipment_books;
  if (books[key]) return books[key];
  const meta = findEquipmentMeta(batch, key, eq);
  return {
    equipment_code: meta.code,
    equipment_name: meta.name || eq?.name || key,
    equipment_id: meta.id,
    location: meta.location || '—',
    entries: [],
  };
}

export function hasEquipmentUsage(row: EquipmentUsageRow): boolean {
  return !!(row.start_date || row.start_time || row.end_date || row.end_time);
}

/** Prefer equipment_usage times; fall back to step_timestamp (stage/step process times). */
export function resolveEquipmentTimes(step: any, usageKey: string): EquipmentUsageRow {
  const usage = step?.data?.equipment_usage || {};
  const u: EquipmentUsageRow = { ...emptyEquipmentUsage(), ...(usage[usageKey] || {}) };
  const ts = step?.data?.step_timestamp || {};
  return {
    start_date: u.start_date || ts.date || '',
    start_time: u.start_time || ts.start_time || '',
    end_date: u.end_date || u.start_date || ts.date || '',
    end_time: u.end_time || ts.end_time || '',
    remark: u.remark || '',
    user_sign: u.user_sign || '',
    user_sign_at: u.user_sign_at || '',
  };
}

export interface MachineLogRow {
  equipment_key: string;
  equipment_name: string;
  equipment_id: string;
  location: string;
  stage_name: string;
  step_name: string;
  step_id: number;
  date: string;
  start_time: string;
  end_time: string;
  remark: string;
  user_sign: string;
  user_sign_at: string;
  eq: any;
}

/** Flat machine-log rows: equipment on each stage/step + date/start/end from usage or step timestamp. */
export function buildMachineLogRows(batch: any): MachineLogRow[] {
  const rows: MachineLogRow[] = [];
  (batch?.steps || []).forEach((step: any) => {
    const tpl = step?.template?.equipment || [];
    if (!tpl.length) return;
    tpl.forEach((eq: any) => {
      const key = equipmentKey(eq);
      if (!key) return;
      const times = resolveEquipmentTimes(step, key);
      const meta = findEquipmentMeta(batch, key, eq);
      rows.push({
        equipment_key: key,
        equipment_name: eq.name || meta.name || key,
        equipment_id: eq.id_no || meta.id || key,
        location: meta.location || '—',
        stage_name: step.stage_name || '',
        step_name: step.step_name || '',
        step_id: Number(step.id) || 0,
        date: times.start_date || '',
        start_time: times.start_time || '',
        end_time: times.end_time || '',
        remark: times.remark || '',
        user_sign: times.user_sign || '',
        user_sign_at: times.user_sign_at || '',
        eq: { ...eq, equipment_code: eq.equipment_code || eq.id_no || key },
      });
    });
  });
  rows.sort((a, b) => {
    const da = `${a.date} ${a.start_time} ${a.stage_name} ${a.step_name}`;
    const db = `${b.date} ${b.start_time} ${b.stage_name} ${b.step_name}`;
    return da.localeCompare(db);
  });
  return rows;
}

export function syncStepEquipmentToLogbook(
  step: any,
  batch: any,
  execTabs: any,
  userSign?: { name: string; at: string }
): void {
  const tabs = normalizeExecLogbook(execTabs);
  const books = tabs.logbook.equipment_books;
  const tpl = step?.template?.equipment || [];
  const batchNo = String(batch?.batch_no || batch?.header?.batch_no || '').trim();

  tpl.forEach((eq: any) => {
    const key = equipmentKey(eq);
    if (!key) return;
    const u = resolveEquipmentTimes(step, key);
    if (!hasEquipmentUsage(u)) return;

    const meta = findEquipmentMeta(batch, key, eq);
    if (!books[key]) {
      books[key] = {
        equipment_code: meta.code,
        equipment_name: eq.name || meta.name,
        equipment_id: eq.id_no || meta.id,
        location: meta.location || '—',
        entries: [],
      };
    }
    const book: EquipmentLogbook = books[key];
    const entryId = `step_${step.id}_${key}`;
    const row: LogbookEntry = {
      entry_id: entryId,
      activity: step.step_name || '',
      stage_name: step.stage_name || '',
      batch_no: batchNo,
      start_date: u.start_date || '',
      start_time: u.start_time || '',
      end_date: u.end_date || u.start_date || '',
      end_time: u.end_time || '',
      remark: u.remark || '',
      user_sign: u.user_sign || userSign?.name || '',
      user_sign_at: u.user_sign_at || userSign?.at || '',
      step_id: step.id,
    };
    const idx = book.entries.findIndex((e) => e.entry_id === entryId);
    if (idx >= 0) book.entries[idx] = row;
    else book.entries.push(row);
    book.entries.sort((a, b) => {
      const da = `${a.start_date} ${a.start_time}`;
      const db = `${b.start_date} ${b.start_time}`;
      return da.localeCompare(db);
    });
  });
}

export function rebuildLogbookFromBatch(batch: any, execTabs: any): void {
  (batch?.steps || []).forEach((s: any) => syncStepEquipmentToLogbook(s, batch, execTabs));
}
