export type FormTableType = 'yield' | 'weighing';

export interface DynamicColumn {
  key: string;
  label: string;
  col_type: 'text' | 'number';
}

export type DynamicRow = Record<string, string>;

export interface DynamicTableDef {
  columns: DynamicColumn[];
  rows: DynamicRow[];
}

export function nextColumnKey(columns: DynamicColumn[]): string {
  let n = 1;
  const keys = new Set((columns || []).map((c) => c.key));
  while (keys.has('col_' + n)) n++;
  return 'col_' + n;
}

export function addColumn(columns: DynamicColumn[], label = ''): DynamicColumn {
  const key = nextColumnKey(columns);
  const col: DynamicColumn = {
    key,
    label: label || 'Column ' + (columns.length + 1),
    col_type: 'text',
  };
  columns.push(col);
  return col;
}

export function removeColumn(columns: DynamicColumn[], rows: DynamicRow[], key: string): void {
  const idx = columns.findIndex((c) => c.key === key);
  if (idx < 0) return;
  columns.splice(idx, 1);
  rows.forEach((row) => delete row[key]);
}

export function blankRow(columns: DynamicColumn[]): DynamicRow {
  const row: DynamicRow = {};
  (columns || []).forEach((c) => (row[c.key] = ''));
  return row;
}

export function addRow(columns: DynamicColumn[], rows: DynamicRow[]): DynamicRow {
  const row = blankRow(columns);
  rows.push(row);
  return row;
}

export function removeRow(rows: DynamicRow[], index: number): void {
  if (index < 0 || index >= rows.length) return;
  rows.splice(index, 1);
}

export function ensureRowKeys(columns: DynamicColumn[], rows: DynamicRow[]): void {
  rows.forEach((row) => {
    columns.forEach((c) => {
      if (row[c.key] === undefined) row[c.key] = '';
    });
    Object.keys(row).forEach((k) => {
      if (!columns.some((c) => c.key === k)) delete row[k];
    });
  });
}

export function defaultYieldTable(): DynamicTableDef {
  return {
    columns: [
      { key: 'col_1', label: 'Item / Stage', col_type: 'text' },
      { key: 'col_2', label: 'Theoretical', col_type: 'number' },
      { key: 'col_3', label: 'Actual', col_type: 'number' },
      { key: 'col_4', label: 'UOM', col_type: 'text' },
      { key: 'col_5', label: 'Limit %', col_type: 'number' },
    ],
    rows: [blankRow([
      { key: 'col_1', label: 'Item / Stage', col_type: 'text' },
      { key: 'col_2', label: 'Theoretical', col_type: 'number' },
      { key: 'col_3', label: 'Actual', col_type: 'number' },
      { key: 'col_4', label: 'UOM', col_type: 'text' },
      { key: 'col_5', label: 'Limit %', col_type: 'number' },
    ])],
  };
}

export function defaultWeighingTable(): DynamicTableDef {
  return {
    columns: [
      { key: 'col_1', label: 'Material / Item', col_type: 'text' },
      { key: 'col_2', label: 'Required Qty', col_type: 'number' },
      { key: 'col_3', label: 'Actual Qty', col_type: 'number' },
      { key: 'col_4', label: 'UOM', col_type: 'text' },
      { key: 'col_5', label: 'Balance ID', col_type: 'text' },
      { key: 'col_6', label: 'Done By', col_type: 'text' },
    ],
    rows: [blankRow([
      { key: 'col_1', label: 'Material / Item', col_type: 'text' },
      { key: 'col_2', label: 'Required Qty', col_type: 'number' },
      { key: 'col_3', label: 'Actual Qty', col_type: 'number' },
      { key: 'col_4', label: 'UOM', col_type: 'text' },
      { key: 'col_5', label: 'Balance ID', col_type: 'text' },
      { key: 'col_6', label: 'Done By', col_type: 'text' },
    ])],
  };
}

export function defaultTableForType(tableType: FormTableType): DynamicTableDef {
  return tableType === 'weighing' ? defaultWeighingTable() : defaultYieldTable();
}

export const FORM_TABLE_META: Record<
  FormTableType,
  { title: string; icon: string; sub: string; codePrefix: string; route: string }
> = {
  yield: {
    title: 'Yield Table Master',
    icon: 'fas fa-chart-line',
    sub: 'Define yield reconciliation table layouts — add columns & rows',
    codePrefix: 'YLD',
    route: 'yield-table-master',
  },
  weighing: {
    title: 'Weighing Table Master',
    icon: 'fas fa-weight-scale',
    sub: 'Define dispensing / weighing table layouts — add columns & rows',
    codePrefix: 'WGT',
    route: 'weighing-table-master',
  },
};
