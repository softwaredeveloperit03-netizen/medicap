import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import jsPDF from 'jspdf';

interface BatchListRow {
  product_code: string;
  work_order_no: string;
  batch_number: string;
  status: 'Started' | 'Completed' | string;
  saved_rows?: number;
  last_saved_at?: string | null;
  approved_at?: string | null;
  approved_by_emp_id?: string | null;
}

interface SopStepGroup {
  stepId: string;
  stepName: string;
  rows: any[];
}

interface SopStageGroup {
  stageTitle: string;
  steps: SopStepGroup[];
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isview = false;
  results: BatchListRow[] = [];
  selectedResult: BatchListRow | null = null;
  stages: any[] = [];
  loading = false;

  product_code = '';
  batch_number = '';
  status_filter = '';

  get sopViewGroups(): SopStageGroup[] {
    const stageMap: Record<string, Record<string, SopStepGroup>> = {};
    const stageOrder: string[] = [];
    const stepOrderByStage: Record<string, string[]> = {};

    for (const row of this.stages || []) {
      const stageTitle = String(row?.stageTitle || 'NA');
      if (!stageMap[stageTitle]) {
        stageMap[stageTitle] = {};
        stageOrder.push(stageTitle);
        stepOrderByStage[stageTitle] = [];
      }
      const stepId = String(row?.stepId || '');
      const stepName = String(row?.stepName || '');
      const stepKey = `${stepId}__${stepName}`;
      if (!stageMap[stageTitle][stepKey]) {
        stageMap[stageTitle][stepKey] = { stepId, stepName, rows: [] };
        stepOrderByStage[stageTitle].push(stepKey);
      }
      stageMap[stageTitle][stepKey].rows.push(row);
    }

    return stageOrder.map((s) => ({
      stageTitle: s,
      steps: stepOrderByStage[s].map((k) => stageMap[s][k]),
    }));
  }

  getPayloadColumns(row: any): Array<{ key: string; header: string; type?: string }> {
    const p = row?.payloadObj || {};
    const cols = Array.isArray(p?.table_columns) ? p.table_columns : [];
    return cols
      .map((c: any) => ({
        key: String(c?.key || '').trim(),
        header: String(c?.header || c?.key || '').trim(),
        type: String(c?.type || '').trim(),
      }))
      .filter((c: any) => c.key !== '');
  }

  payloadTableColumns(row: any): Array<{ key: string; label: string }> {
    return this.getPayloadColumns(row).map((c) => ({ key: c.key, label: c.header || c.key }));
  }

  getPayloadRows(row: any): Array<Record<string, any>> {
    const p = row?.payloadObj || {};
    const rows = Array.isArray(p?.table_rows) ? p.table_rows : [];
    return rows.filter((r: any) => r && typeof r === 'object');
  }

  getPayloadKvPairs(row: any): Array<{ key: string; value: string }> {
    const p = row?.payloadObj || {};
    if (!p || typeof p !== 'object') {
      return [];
    }
    return Object.keys(p)
      .filter((k) => k !== 'table_columns' && k !== 'table_rows')
      .map((k) => ({
        key: k,
        value: p[k] == null ? '' : String(p[k]),
      }));
  }

  hasPayloadTable(row: any): boolean {
    return this.getPayloadColumns(row).length > 0;
  }

  mainStepRow(step: SopStepGroup): any | null {
    if (!step || !Array.isArray(step.rows)) {
      return null;
    }
    return step.rows.find((r: any) => !String(r?.substepId || '').trim()) || null;
  }

  substepRows(step: SopStepGroup): any[] {
    if (!step || !Array.isArray(step.rows)) {
      return [];
    }
    return step.rows.filter((r: any) => String(r?.substepId || '').trim() !== '');
  }

  formatCell(v: any): string {
    if (v == null) {
      return '';
    }
    if (Array.isArray(v)) {
      if (v.length === 0) {
        return '';
      }
      return v.map((x) => this.formatCell(x)).filter((x) => x !== '').join(', ');
    }
    if (typeof v === 'object') {
      // Prefer common human-readable fields first.
      const priorityKeys = ['label', 'name', 'title', 'value', 'id', 'code', 'emp_id', 'equipment_code'];
      for (const k of priorityKeys) {
        if (v[k] != null && String(v[k]).trim() !== '') {
          return String(v[k]);
        }
      }
      // Fallback: flatten object as "key: value" pairs.
      const keys = Object.keys(v);
      if (!keys.length) {
        return '';
      }
      const parts: string[] = [];
      for (const k of keys) {
        const val = v[k];
        if (val == null || (typeof val === 'string' && val.trim() === '')) {
          continue;
        }
        if (typeof val === 'object') {
          parts.push(`${k}: ${this.formatCell(val)}`);
        } else {
          parts.push(`${k}: ${String(val)}`);
        }
      }
      return parts.join(', ');
    }
    return String(v);
  }

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
    this.getTechnicalLog();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }





  getTechnicalLog() {
    this.loading = true;
    this.service
      .get('bmr/ebmr_filled_step_api.php?type=get_started_or_completed_batches')
      .subscribe((response: any) => {
        const rows = response?.batches ?? response;
        const arr: BatchListRow[] = Array.isArray(rows) ? rows : [];
        this.results = arr.filter((r) => {
          const pcOk = !this.product_code.trim() || String(r.product_code || '').toLowerCase().includes(this.product_code.trim().toLowerCase());
          const bnOk = !this.batch_number.trim() || String(r.batch_number || '').toLowerCase().includes(this.batch_number.trim().toLowerCase());
          const stOk = !this.status_filter || String(r.status || '') === this.status_filter;
          return pcOk && bnOk && stOk;
        });
        this.loading = false;
      }, () => {
        this.results = [];
        this.loading = false;
      });
  }

  private loadMasterMap(productCode: string): Promise<Record<string, any>> {
    return new Promise((resolve) => {
      this.service
        .get('bmr/packing_ebmr_master_api.php?type=get_master_tree&product_code=' + encodeURIComponent(productCode || ''))
        .subscribe((res: any) => {
          const map: Record<string, any> = {};
          const byStepName: Record<string, any> = {};
          const root = typeof res?.master_json === 'string' ? JSON.parse(res.master_json || '{}') : (res?.master_json || {});
          const stages = Array.isArray(root?.Stages) ? root.Stages : [];
          for (const st of stages) {
            const stageTitle = String(st?.Stage || '').trim();
            const steps = Array.isArray(st?.Steps) ? st.Steps : [];
            for (const sp of steps) {
              const stepId = String(sp?.StepID || '').trim();
              const stepName = String(sp?.StepName || '').trim();
              map[`${stepId}__`] = { stageTitle, stepName, substepName: '' };
              if (stepName) {
                byStepName[stepName.toLowerCase()] = { stageTitle, stepName, substepName: '' };
              }
              const subs = Array.isArray(sp?.Substeps) ? sp.Substeps : [];
              for (const sb of subs) {
                const subId = String(sb?.SubstepID || '').trim();
                const subName = String(sb?.SubstepName || '').trim();
                map[`${stepId}__${subId}`] = { stageTitle, stepName, substepName: subName };
              }
            }
          }
          map.__byStepName = byStepName;
          resolve(map);
        }, () => resolve({}));
    });
  }

  async view(val: number) {
    this.selectedResult = this.results[val];
    if (!this.selectedResult) {
      return;
    }
    const masterMap = await this.loadMasterMap(this.selectedResult.product_code);
    const api =
      'bmr/ebmr_filled_step_api.php?type=get_batch_steps' +
      '&product_code=' + encodeURIComponent(this.selectedResult.product_code || '') +
      '&work_order_no=' + encodeURIComponent(this.selectedResult.work_order_no || '') +
      '&batch_number=' + encodeURIComponent(this.selectedResult.batch_number || '');
    this.service.get(api).subscribe((res: any) => {
      const steps = res?.steps || {};
      const subs = res?.substeps || {};
      const rows: any[] = [];

      Object.keys(steps).forEach((stepId) => {
        const v = steps[stepId] || {};
        const stepNameRaw = String(v.step_name || '').trim();
        const m =
          masterMap[`${stepId}__`] ||
          ((masterMap.__byStepName || {})[stepNameRaw.toLowerCase()] || {}) ||
          {};
        rows.push({
          stageTitle: m.stageTitle || 'Outside current BMR tree',
          stepId,
          stepName: stepNameRaw || m.stepName || '',
          substepId: '',
          substepName: '',
          savedAt: v.saved_at || '',
          payloadObj: v.data ?? {},
          payloadText: JSON.stringify(v.data ?? {}, null, 2),
        });
      });

      Object.keys(subs).forEach((key) => {
        const v = subs[key] || {};
        const parts = String(key).split('__');
        const stepId = parts[0] || '';
        const substepId = parts[1] || '';
        const m = masterMap[`${stepId}__${substepId}`] || masterMap[`${stepId}__`] || {};
        rows.push({
          stageTitle: m.stageTitle || 'Outside current BMR tree',
          stepId,
          stepName: m.stepName || '',
          substepId,
          substepName: m.substepName || '',
          savedAt: v.saved_at || '',
          payloadObj: v.data ?? {},
          payloadText: JSON.stringify(v.data ?? {}, null, 2),
        });
      });

      this.stages = rows;
      this.isview = true;
    });
  }

  downloadLog() {
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    let y = 12;
    doc.setFontSize(12);
    doc.text('Packing eBMR Started/Completed Batches', pageWidth / 2, y, { align: 'center' });
    y += 8;
    doc.setFontSize(9);
    this.results.forEach((r, i) => {
      const line = `${i + 1}. ${r.product_code} | ${r.work_order_no} | ${r.batch_number} | ${r.status} | rows: ${r.saved_rows || 0}`;
      const lines = doc.splitTextToSize(line, pageWidth - 16);
      lines.forEach((txt: string) => {
        if (y > pageHeight - 10) {
          doc.addPage();
          y = 12;
        }
        doc.text(txt, 8, y);
        y += 5;
      });
    });
    doc.save('packing-bmr-batches.pdf');
  }

  downloadRecord() {
    if (!this.selectedResult) {
      return;
    }
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    let y = 12;
    doc.setFontSize(12);
    doc.text('Packing eBMR Filled/Saved Data', pageWidth / 2, y, { align: 'center' });
    y += 7;
    doc.setFontSize(9);
    doc.text(`Product: ${this.selectedResult.product_code}`, 8, y); y += 5;
    doc.text(`WO: ${this.selectedResult.work_order_no}`, 8, y); y += 5;
    doc.text(`Batch: ${this.selectedResult.batch_number}`, 8, y); y += 7;
    this.sopViewGroups.forEach((g, gi) => {
      const stageLine = `Stage ${gi + 1} - ${g.stageTitle}`;
      if (y > pageHeight - 12) { doc.addPage(); y = 12; }
      doc.setFontSize(10);
      doc.text(stageLine, 8, y);
      y += 5;
      g.steps.forEach((s, si) => {
        const stepLine = `Step ${gi + 1}.${si + 1} - ${s.stepId} - ${s.stepName}`;
        if (y > pageHeight - 12) { doc.addPage(); y = 12; }
        doc.setFontSize(9);
        doc.text(stepLine, 10, y);
        y += 4.5;

        const main = this.mainStepRow(s);
        if (main) {
          y = this.printPayloadInPdf(doc, pageWidth, pageHeight, y, main, `Main step ${gi + 1}.${si + 1}`);
        }
        this.substepRows(s).forEach((sub: any, subi: number) => {
          y = this.printPayloadInPdf(
            doc,
            pageWidth,
            pageHeight,
            y,
            sub,
            `Substep ${gi + 1}.${si + 1}.${subi + 1} - ${sub.substepId} - ${sub.substepName || ''}`
          );
        });
      });
      y += 2;
    });
    doc.save('packing-bmr-filled-data.pdf');
  }

  private printPayloadInPdf(
    doc: jsPDF,
    pageWidth: number,
    pageHeight: number,
    y: number,
    row: any,
    label: string
  ): number {
    const ensure = (need = 8) => {
      if (y > pageHeight - need) {
        doc.addPage();
        y = 12;
      }
    };
    ensure();
    doc.setFontSize(8.5);
    doc.text(`${label}${row?.savedAt ? ' | Saved: ' + row.savedAt : ''}`, 12, y);
    y += 4;

    const cols = this.payloadTableColumns(row);
    const dataRows = this.getPayloadRows(row);
    if (cols.length) {
      const header = cols.map((c) => c.label).join(' | ');
      const h = doc.splitTextToSize(header, pageWidth - 16);
      h.forEach((t: string) => { ensure(); doc.text(t, 14, y); y += 4; });
      dataRows.forEach((r: any, i: number) => {
        const line = `${i + 1}. ` + cols.map((c) => `${c.label}: ${this.formatCell(r?.[c.key])}`).join(', ');
        const wrapped = doc.splitTextToSize(line, pageWidth - 18);
        wrapped.forEach((t: string) => { ensure(); doc.text(t, 14, y); y += 4; });
      });
      if (!dataRows.length) {
        ensure();
        doc.text('No row entries', 14, y);
        y += 4;
      }
    } else {
      const pairs = this.getPayloadKvPairs(row);
      if (!pairs.length) {
        ensure();
        doc.text('No data', 14, y);
        y += 4;
      } else {
        pairs.forEach((p) => {
          const wrapped = doc.splitTextToSize(`${p.key}: ${p.value}`, pageWidth - 18);
          wrapped.forEach((t: string) => { ensure(); doc.text(t, 14, y); y += 4; });
        });
      }
    }
    y += 1.5;
    return y;
  }

}