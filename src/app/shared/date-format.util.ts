/** Display format used across Canadian / Medicap forms */
export const DATE_DISPLAY_FORMAT = 'dd/MM/yyyy';
export const DATE_DISPLAY_REGEX = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
export const DATE_DISPLAY_HTML_PATTERN =
  '^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[0-2])/[0-9]{4}$';

/** ISO yyyy-MM-dd → dd/MM/yyyy */
export function formatIsoToDisplay(value: unknown): string {
  if (value == null || value === '') {
    return '';
  }
  const s = String(value).trim();
  const iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (iso) {
    return `${iso[3]}/${iso[2]}/${iso[1]}`;
  }
  if (DATE_DISPLAY_REGEX.test(s)) {
    return s;
  }
  const d = new Date(s);
  if (!isNaN(d.getTime())) {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
  }
  return s;
}

/** dd/MM/yyyy → ISO yyyy-MM-dd (null if invalid) */
export function parseDisplayToIso(value: unknown): string | null {
  if (value == null || String(value).trim() === '') {
    return null;
  }
  const s = String(value).trim();
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    return s.slice(0, 10);
  }
  const m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!m) {
    return null;
  }
  const day = +m[1];
  const month = +m[2];
  const year = +m[3];
  const dt = new Date(year, month - 1, day);
  if (dt.getFullYear() !== year || dt.getMonth() !== month - 1 || dt.getDate() !== day) {
    return null;
  }
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

/** Normalize user input to dd/MM/yyyy while typing (digits only, max 8). */
export function maskDateDisplayInput(raw: string): string {
  const digits = String(raw || '').replace(/\D/g, '').slice(0, 8);
  if (digits.length <= 2) {
    return digits;
  }
  if (digits.length <= 4) {
    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }
  return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
}

/** Parse ISO or dd/MM/yyyy to local Date (no UTC shift). */
export function parseToLocalDate(value: unknown): Date | null {
  const iso = parseDisplayToIso(value);
  if (!iso) {
    return null;
  }
  const p = iso.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!p) {
    return null;
  }
  return new Date(+p[1], +p[2] - 1, +p[3]);
}

export function todayDisplay(): string {
  return formatIsoToDisplay(new Date().toISOString().slice(0, 10));
}

export function toIsoDate(value: unknown): string {
  return parseDisplayToIso(value) || '';
}
