/** Fast row search for log tables — avoids JSON.stringify on every change-detection cycle. */
export function filterRowsBySearch<T extends Record<string, unknown>>(
  rows: T[] | null | undefined,
  query: string
): T[] {
  const list = rows || [];
  const q = (query || '').trim().toLowerCase();
  if (!q) {
    return list;
  }
  return list.filter((row) => {
    if (!row || typeof row !== 'object') {
      return false;
    }
    for (const value of Object.values(row)) {
      if (value == null || typeof value === 'object') {
        continue;
      }
      if (String(value).toLowerCase().includes(q)) {
        return true;
      }
    }
    return false;
  });
}

/** Stable trackBy for clr-datagrid rows (use with *clrDgItems). */
export function trackByRowId(index: number, row: { id?: unknown; material_code?: unknown } | null): unknown {
  return row?.id ?? row?.material_code ?? index;
}

/** Single-row check for use inside .filter() callbacks. */
export function rowMatchesSearch(row: Record<string, unknown> | null | undefined, query: string): boolean {
  if (!row || typeof row !== 'object') {
    return false;
  }
  const q = (query || '').trim().toLowerCase();
  if (!q) {
    return true;
  }
  for (const value of Object.values(row)) {
    if (value == null || typeof value === 'object') {
      continue;
    }
    if (String(value).toLowerCase().includes(q)) {
      return true;
    }
  }
  return false;
}
