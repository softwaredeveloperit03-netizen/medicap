import { Component, OnInit } from '@angular/core';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';
import { REGEN_METRIC_SPECS } from '../../shared/hplc-workflow.config';
declare let alertify: any;

@Component({
  selector: 'app-hplc-column-regeneration',
  templateUrl: './column-regeneration.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnRegenerationComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  rows: any[] = [];
  inventory: any[] = [];
  specRows = REGEN_METRIC_SPECS;
  showForm = false;
  form: any = { metrics: [] };
  selected: any;
  saving = false;

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.demo.fetchList('column_regeneration', [], (rows) => {
      this.rows = rows || [];
    });
    this.demo.fetchList('column_inventory', [], (rows) => {
      this.inventory = rows || [];
    });
    this.initMetrics();
  }

  private notify(ok: boolean, msg: string): void {
    if (typeof alertify !== 'undefined') {
      if (ok) {
        alertify.success(msg);
      } else {
        alertify.error(msg);
      }
    } else {
      alert(msg);
    }
  }

  initMetrics(): void {
    this.form.metrics = this.specRows.map((s) => ({ ...s, before: '', after: '', pass: null }));
  }

  onColumnPick(): void {
    const inv = this.inventory.find((i) => i.column_id === this.form.column_id);
    if (inv) {
      this.form.column_type = inv.type || '';
    }
  }

  openDetail(r: any): void {
    this.selected = r;
    this.showForm = false;
  }

  calcPass(m: any): void {
    if (m.before === '' || m.after === '') {
      m.pass = null;
      return;
    }
    const a = +m.after;
    if (m.key === 'plates') {
      m.pass = a >= 2000;
    } else if (m.key === 'tailing') {
      m.pass = a <= 2.0;
    } else if (m.key === 'resolution') {
      m.pass = a >= 2.0;
    } else if (m.key === 'back_pressure') {
      m.pass = a <= 300;
    } else if (m.key === 'retention_shift') {
      m.pass = a <= 2;
    } else {
      m.pass = a <= +m.before;
    }
  }

  save(): void {
    if (this.saving) {
      return;
    }
    if (!String(this.form.column_id || '').trim()) {
      this.notify(false, 'Select a Column ID from inventory.');
      return;
    }
    this.saving = true;
    this.form.metrics.forEach((m: any) => this.calcPass(m));
    const allPass = this.form.metrics.every((m: any) => m.pass === true);
    const status = allPass ? 'Approved' : 'Pending verification';
    this.rows = [
      {
        id: Date.now(),
        regen_no: 'REG-' + new Date().getFullYear() + '-' + String(10 + this.rows.length).padStart(3, '0'),
        regen_date: new Date().toISOString().slice(0, 10),
        status,
        metrics: this.form.metrics.map((x: any) => ({ ...x })),
        column_id: this.form.column_id,
        column_type: this.form.column_type,
        reason: this.form.reason,
        solvent_sequence: this.form.solvent_sequence,
        duration_h: this.form.duration_h,
        method: this.form.method,
        injection_ul: this.form.injection_ul,
        procedure: this.form.procedure,
        performed_by: this.form.performed_by,
        verified_by: this.form.verified_by,
      },
      ...this.rows,
    ];
    this.demo.persistList('column_regeneration', this.rows);

    const inv = this.inventory.find((i) => i.column_id === this.form.column_id);
    if (inv) {
      inv.status = allPass ? 'Active' : 'Regen due';
      this.demo.persistList('column_inventory', this.inventory);
    }

    this.saving = false;
    this.showForm = false;
    this.form = { metrics: [] };
    this.initMetrics();
    this.notify(true, allPass ? 'Regeneration saved — metrics Pass; column set Active.' : 'Regeneration saved — Pending verification.');
  }
}
