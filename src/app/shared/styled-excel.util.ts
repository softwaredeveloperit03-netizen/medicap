import * as ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

export const XL = {
  titleBg: 'FF334155',
  headerFg: 'FFF8FAFC',
  stageBg: 'FF475569',
  summaryBg: 'FF3F3F46',
  actualBg: 'FF78716C',
  plannedBg: 'FF64748B',
  devBg: 'FF71717A',
  actualCellBg: 'FFFAFAF9',
  plannedCellBg: 'FFF8FAFC',
  rowAltBg: 'FFFAFAFA',
  footBg: 'FFE2E8F0',
  footFg: 'FF1E293B',
  border: 'FFCBD5E1',
  text: 'FF334155',
  okBg: 'FFDCFCE7',
  okFg: 'FF166534',
  warnBg: 'FFFEF9C3',
  warnFg: 'FF854D0E',
  lateBg: 'FFFEE2E2',
  lateFg: 'FF991B1B',
  earlyBg: 'FFEFF6FF',
  earlyFg: 'FF1D4ED8',
  neutralBg: 'FFF4F4F5',
  neutralFg: 'FF71717A',
  mrpHeaderBg: 'FF004A70',
  mrpHeaderFg: 'FFFFFFFF',
  sentBg: 'FFE8F4FC',
  sentFg: 'FF006899',
  pendingBg: 'FFFFF8E6',
  pendingFg: 'FFB8860B',
  holdBg: 'FFF1F5F9',
  holdFg: 'FF475569',
  cancelBg: 'FFFEE2E2',
  cancelFg: 'FF991B1B',
  canPlanBg: 'FFDCFCE7',
  canPlanFg: 'FF166534',
  mcUsedBg: 'FFFEF9C3',
  mcUsedFg: 'FF854D0E',
  cannotPlanBg: 'FFFEE2E2',
  cannotPlanFg: 'FF991B1B',
};

export function xlBorder(): Partial<ExcelJS.Borders> {
  const side: Partial<ExcelJS.Border> = { style: 'thin', color: { argb: XL.border } };
  return { top: side, left: side, bottom: side, right: side };
}

export function xlFill(argb: string): ExcelJS.Fill {
  return { type: 'pattern', pattern: 'solid', fgColor: { argb } };
}

export function xlStyle(
  cell: ExcelJS.Cell,
  opts: {
    bg?: string;
    fg?: string;
    bold?: boolean;
    hAlign?: 'left' | 'center' | 'right';
    wrap?: boolean;
    size?: number;
  }
): void {
  cell.border = xlBorder();
  cell.alignment = {
    vertical: 'middle',
    horizontal: opts.hAlign ?? 'center',
    wrapText: opts.wrap ?? false,
    shrinkToFit: false,
  };
  cell.font = {
    name: 'Calibri',
    size: opts.size ?? 10,
    bold: opts.bold ?? false,
    color: opts.fg ? { argb: opts.fg } : { argb: XL.text },
  };
  if (opts.bg) {
    cell.fill = xlFill(opts.bg);
  }
}

export function xlTitleRow(ws: ExcelJS.Worksheet, title: string, totalCols: number, height = 26): ExcelJS.Row {
  const row = ws.addRow([title]);
  ws.mergeCells(row.number, 1, row.number, totalCols);
  const cell = row.getCell(1);
  xlStyle(cell, { bg: XL.titleBg, fg: XL.headerFg, bold: true, hAlign: 'center', size: 13 });
  row.height = height;
  return row;
}

export function xlSubtitleRow(ws: ExcelJS.Worksheet, text: string, totalCols: number): ExcelJS.Row {
  const row = ws.addRow([text]);
  ws.mergeCells(row.number, 1, row.number, totalCols);
  xlStyle(row.getCell(1), { bg: XL.plannedCellBg, fg: XL.text, hAlign: 'left', wrap: true });
  row.height = 18;
  return row;
}

export function xlFooterRow(
  ws: ExcelJS.Worksheet,
  label: string,
  values: (string | number)[],
  totalCols: number
): ExcelJS.Row {
  const row = ws.addRow(Array(totalCols).fill(''));
  ws.mergeCells(row.number, 1, row.number, Math.min(5, totalCols));
  row.getCell(1).value = label;
  xlStyle(row.getCell(1), { bg: XL.footBg, fg: XL.footFg, bold: true, hAlign: 'left' });
  values.forEach((v, i) => {
    const col = Math.min(6 + i, totalCols);
    if (col <= totalCols) {
      row.getCell(col).value = v;
      xlStyle(row.getCell(col), { bg: XL.footBg, fg: XL.footFg, bold: true });
    }
  });
  row.height = 22;
  return row;
}

export async function xlDownload(wb: ExcelJS.Workbook, filename: string): Promise<void> {
  const buffer = await wb.xlsx.writeBuffer();
  saveAs(
    new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }),
    filename
  );
}

export function xlQty(val: number | null | undefined, cell: ExcelJS.Cell, dash = '—'): void {
  const n = Number(val);
  if (!Number.isFinite(n) || n === 0) {
    cell.value = dash;
    return;
  }
  cell.value = n;
  cell.numFmt = n % 1 === 0 ? '#,##0' : '#,##0.00';
}

export function xlDevStyle(days: number | null | undefined): { bg: string; fg: string } {
  if (days === null || days === undefined) return { bg: XL.neutralBg, fg: XL.neutralFg };
  if (days > 0) return { bg: XL.lateBg, fg: XL.lateFg };
  if (days < 0) return { bg: XL.earlyBg, fg: XL.earlyFg };
  return { bg: XL.okBg, fg: XL.okFg };
}

export function xlDevLabel(days: number | null | undefined): string {
  if (days === null || days === undefined) return '—';
  if (days === 0) return '0';
  return days > 0 ? `+${days}` : String(days);
}
