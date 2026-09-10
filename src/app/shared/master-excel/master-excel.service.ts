import { Injectable } from '@angular/core';
import * as ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';
import { MasterExcelConfig, MasterExcelColumn } from './master-excel.types';

@Injectable({ providedIn: 'root' })
export class MasterExcelService {
  private readonly headerColors = [
    'FF0f2744',
    'FF1d4ed8',
    'FF7c3aed',
    'FFbe185d',
    'FFc2410c',
    'FFea580c',
    'FF16a34a',
    'FF0891b2',
    'FFca8a04',
    'FF475569',
    'FF0f766e',
    'FF4338ca',
  ];

  async downloadTemplate(config: MasterExcelConfig): Promise<void> {
    const wb = new ExcelJS.Workbook();
    wb.creator = 'Medicap';
    wb.created = new Date();
    const ws = wb.addWorksheet(config.sheetName, {
      views: [{ showGridLines: true, state: 'frozen', ySplit: 3 }],
    });

    const numCols = config.columns.length;
    ws.mergeCells(1, 1, 1, numCols);
    const title = ws.getCell(1, 1);
    title.value = config.title;
    title.font = { bold: true, size: 16, color: { argb: 'FF0f2744' } };
    title.alignment = { vertical: 'middle', horizontal: 'center' };
    ws.getRow(1).height = 28;

    ws.mergeCells(2, 1, 2, numCols);
    const sub = ws.getCell(2, 1);
    sub.value =
      'Fill data from row 4 downward. Do not rename header cells. Required columns are marked with *. Exported: ' +
      new Date().toLocaleString();
    sub.font = { italic: true, size: 10, color: { argb: 'FF64748b' } };
    sub.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
    ws.getRow(2).height = 32;

    const headers = config.columns.map((c) => (c.required ? c.header + ' *' : c.header));
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      const idx = colNumber - 1;
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: this.headerColors[idx % this.headerColors.length] },
      };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    headerRow.height = 24;

    // Example row (overwrite) + one empty row with light zebra fill
    const exampleValues = config.columns.map((c) => c.sample ?? '');
    for (let r = 0; r < 2; r++) {
      const row = ws.addRow(r === 0 ? exampleValues : config.columns.map(() => ''));
      row.height = 20;
      row.eachCell((cell) => {
        cell.border = {
          top: { style: 'thin' },
          bottom: { style: 'thin' },
          left: { style: 'thin' },
          right: { style: 'thin' },
        };
        if (r === 0) {
          cell.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF9C3' },
          };
          cell.font = { italic: true, color: { argb: 'FF64748b' }, size: 10 };
        } else {
          cell.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF8FAFC' },
          };
        }
      });
    }

    config.columns.forEach((col, i) => {
      ws.getColumn(i + 1).width = col.width ?? 16;
    });

    const inst = wb.addWorksheet('Instructions');
    inst.getColumn(1).width = 28;
    inst.getColumn(2).width = 56;
    const instTitle = inst.addRow([config.title + ' — Instructions']);
    instTitle.font = { bold: true, size: 13, color: { argb: 'FF0f2744' } };
    inst.mergeCells(1, 1, 1, 2);
    inst.addRow([]);
    inst.addRow(['How to use', 'Download this file, overwrite the yellow example row, add more rows from row 5 downward, then Upload from Excel Provision.']);
    inst.addRow(['Do not rename', 'Keep header labels exactly as in row 3 (required columns end with *).']);
    inst.addRow(['Max rows', 'Upload up to 500 data rows per file.']);
    inst.addRow(['Duplicates', 'Rows that already exist for this plant (by code/name) are skipped and reported.']);
    inst.addRow([]);
    const colHead = inst.addRow(['Column', 'Notes']);
    colHead.eachCell((cell) => {
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF0f2744' },
      };
    });
    config.columns.forEach((col) => {
      const req = col.required ? 'Required. ' : 'Optional. ';
      const note = col.note || (col.sample ? `Example: ${col.sample}` : '');
      inst.addRow([col.required ? col.header + ' *' : col.header, req + note]);
    });

    const buf = await wb.xlsx.writeBuffer();
    const blob = new Blob([buf], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    saveAs(blob, `${config.filePrefix}_${this.slugDate()}.xlsx`);
  }

  async parseWorkbook(
    file: File,
    columns: MasterExcelColumn[]
  ): Promise<Record<string, string>[]> {
    const buffer = await file.arrayBuffer();
    const wb = new ExcelJS.Workbook();
    await wb.xlsx.load(buffer);
    const ws = wb.worksheets[0];
    if (!ws) {
      throw new Error('No worksheet found in the Excel file');
    }

    const headerMap = new Map<number, string>();
    const headerRow = ws.getRow(3);
    headerRow.eachCell({ includeEmpty: false }, (cell, colNumber) => {
      const raw = String(cell.value ?? '')
        .replace(/\s*\*\s*$/, '')
        .trim()
        .toLowerCase();
      const col = columns.find((c) => c.header.trim().toLowerCase() === raw);
      if (col) {
        headerMap.set(colNumber, col.key);
      }
    });

    if (headerMap.size === 0) {
      // Fallback: assume row 3 headers match config order
      columns.forEach((c, i) => headerMap.set(i + 1, c.key));
    }

    const rows: Record<string, string>[] = [];
    ws.eachRow({ includeEmpty: false }, (row, rowNumber) => {
      if (rowNumber <= 3) {
        return;
      }
      const obj: Record<string, string> = {};
      let any = false;
      headerMap.forEach((key, colNumber) => {
        const cell = row.getCell(colNumber);
        let val = '';
        const v = cell.value as any;
        if (v == null) {
          val = '';
        } else if (typeof v === 'object' && v.text != null) {
          val = String(v.text);
        } else if (typeof v === 'object' && v.result != null) {
          val = String(v.result);
        } else {
          val = String(v);
        }
        val = val.trim();
        if (val) {
          any = true;
        }
        obj[key] = val;
      });
      if (any) {
        rows.push(obj);
      }
    });

    return rows;
  }

  validateRows(
    rows: Record<string, string>[],
    columns: MasterExcelColumn[]
  ): { ok: Record<string, string>[]; errors: string[] } {
    const required = columns.filter((c) => c.required);
    const ok: Record<string, string>[] = [];
    const errors: string[] = [];
    const maxRows = 500;
    if (rows.length > maxRows) {
      errors.push(`Too many rows (${rows.length}). Maximum is ${maxRows} per upload.`);
      rows = rows.slice(0, maxRows);
    }
    rows.forEach((row, i) => {
      const missing = required.filter((c) => !String(row[c.key] || '').trim()).map((c) => c.header);
      if (missing.length) {
        errors.push(`Row ${i + 4}: missing ${missing.join(', ')}`);
      } else {
        ok.push(row);
      }
    });
    return { ok, errors };
  }

  private slugDate(): string {
    const d = new Date();
    const p = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(
      d.getMinutes()
    )}`;
  }
}
