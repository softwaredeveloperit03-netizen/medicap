import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';
declare let alertify: any;

@Component({
  selector: 'app-hplc-column-ordering',
  templateUrl: './column-ordering.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnOrderingComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  rows: any[] = [];
  masters: any[] = [];
  showForm = false;
  form: any = { qty: 1 };
  saving = false;

  constructor(public demo: HplcDemoDataService, private service: DataAccessService) {}

  ngOnInit(): void {
    this.demo.fetchList('column_orders', [], (rows) => {
      this.rows = rows;
    });
    this.loadMasters();
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

  loadMasters(): void {
    this.service.get('qc/hplc.php?type=getHPLCLog').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.masters = list
          .filter((r: any) => String(r.status || '').toLowerCase() === 'approved')
          .map((r: any) => ({
            ...r,
            master_no: r.column_no || r.master_no || '',
            column_type: r.column_name || r.column_type || '',
          }));
        if (!this.masters.length && this.demoMode) {
          this.masters = this.demo.getColumnMaster().filter((m) => String(m.status).toLowerCase() === 'approved');
        }
      },
      error: () => {
        this.masters = this.demoMode
          ? this.demo.getColumnMaster().filter((m) => String(m.status).toLowerCase() === 'approved')
          : [];
      },
    });
  }

  save(): void {
    if (this.saving) {
      return;
    }
    if (!String(this.form.master_ref || '').trim()) {
      this.notify(false, 'Select an Approved master reference.');
      return;
    }
    if (!(+this.form.qty > 0)) {
      this.notify(false, 'Quantity must be at least 1.');
      return;
    }
    this.saving = true;
    const master = this.masters.find((m) => m.master_no === this.form.master_ref);
    this.rows = [
      {
        id: Date.now(),
        order_no: 'CO-' + new Date().getFullYear() + '-' + String(100 + this.rows.length).padStart(3, '0'),
        ...this.form,
        column_type: master?.column_type || '',
        order_date: new Date().toISOString().slice(0, 10),
        status: 'Ordered',
      },
      ...this.rows,
    ];
    this.demo.persistList('column_orders', this.rows);
    this.saving = false;
    this.showForm = false;
    this.form = { qty: 1 };
    this.notify(true, 'Column order saved.');
  }
}
