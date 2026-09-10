import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';
declare let alertify: any;

@Component({
  selector: 'app-hplc-column-logbook',
  templateUrl: './column-logbook.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnLogbookComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  form: any = { injections: 1 };

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.demo.fetchList('column_inventory', [], (rows) => {
      this.rows = rows || [];
    });
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

  logUse(): void {
    if (!String(this.form.column_id || '').trim()) {
      this.notify(false, 'Select a Column ID.');
      return;
    }
    const row = this.rows.find((r) => r.column_id === this.form.column_id);
    if (!row) {
      this.notify(false, 'Column not found in inventory. Release a receiving entry first.');
      return;
    }
    const add = +this.form.injections || 1;
    row.injections = (+row.injections || 0) + add;
    row.last_used = new Date().toISOString().slice(0, 10);
    row.hplc = this.form.hplc_id || row.hplc;
    if (row.injections >= 1000 && String(row.status).toLowerCase() === 'active') {
      row.status = 'Regen due';
    }
    this.demo.persistList('column_inventory', this.rows);
    this.notify(true, 'Usage logged.');
    this.form = { injections: 1 };
  }
}
