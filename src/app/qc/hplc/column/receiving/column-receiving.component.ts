import { Component, OnInit } from '@angular/core';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';
declare let alertify: any;

@Component({
  selector: 'app-hplc-column-receiving',
  templateUrl: './column-receiving.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnReceivingComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  rows: any[] = [];
  /** All loaded orders (for display). */
  allOrders: any[] = [];
  /** Orders still available to receive against. */
  orders: any[] = [];
  inventory: any[] = [];
  showForm = false;
  form: any = { coa_ok: 'Yes', visual_ok: 'Yes', recv_date: '' };
  saving = false;
  releasingId: number | string | null = null;

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.form.recv_date = new Date().toISOString().slice(0, 10);
    this.reload();
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

  /** Open for receiving unless fully closed/cancelled. */
  private isOpenOrder(o: any): boolean {
    const s = String(o?.status || '')
      .trim()
      .toLowerCase();
    if (!s) {
      return true;
    }
    return s !== 'received' && s !== 'closed' && s !== 'cancelled' && s !== 'canceled' && s !== 'fully received';
  }

  reload(): void {
    this.demo.fetchList('column_receiving', [], (rows) => {
      this.rows = rows || [];
    });
    this.demo.fetchList('column_orders', [], (rows) => {
      this.allOrders = rows || [];
      this.orders = this.allOrders.filter((o) => this.isOpenOrder(o));
    });
    this.demo.fetchList('column_inventory', [], (rows) => {
      this.inventory = rows || [];
    });
  }

  startReceive(order: any): void {
    this.showForm = true;
    this.form = {
      coa_ok: 'Yes',
      visual_ok: 'Yes',
      recv_date: new Date().toISOString().slice(0, 10),
      order_no: order?.order_no || '',
      master_ref: order?.master_ref || '',
      column_type: order?.column_type || '',
      serial: '',
      column_id: '',
    };
    this.onOrderChange();
  }

  onOrderChange(): void {
    const o = this.orders.find((x) => x.order_no === this.form.order_no) || this.allOrders.find((x) => x.order_no === this.form.order_no);
    if (o) {
      this.form.master_ref = o.master_ref || this.form.master_ref || '';
      this.form.column_type = o.column_type || this.form.column_type || '';
      if (!this.form.column_id) {
        const suffix = String(Date.now()).slice(-5);
        const typePart = String(o.column_type || o.master_ref || 'COL').replace(/\s+/g, '');
        this.form.column_id = 'COL-' + typePart + '-' + suffix;
      }
    }
  }

  save(): void {
    if (this.saving) {
      return;
    }
    if (!String(this.form.order_no || '').trim()) {
      this.notify(false, 'Select a PO / Order no.');
      return;
    }
    if (!String(this.form.serial || '').trim()) {
      this.notify(false, 'Serial number is required.');
      return;
    }
    if (!String(this.form.column_id || '').trim()) {
      this.notify(false, 'Column ID is required.');
      return;
    }
    this.saving = true;
    this.rows = [
      {
        id: Date.now(),
        recv_no: 'CR-' + new Date().getFullYear() + '-' + String(40 + this.rows.length).padStart(3, '0'),
        recv_date: this.form.recv_date || new Date().toISOString().slice(0, 10),
        status: 'Quarantine',
        ...this.form,
      },
      ...this.rows,
    ];
    this.demo.persistList('column_receiving', this.rows);
    this.saving = false;
    this.showForm = false;
    this.form = { coa_ok: 'Yes', visual_ok: 'Yes', recv_date: new Date().toISOString().slice(0, 10) };
    this.notify(true, 'Column receiving saved as Quarantine. Release to add to Logbook.');
  }

  release(row: any): void {
    if (!row || this.releasingId != null) {
      return;
    }
    if (String(row.status || '').toLowerCase() === 'released') {
      return;
    }
    this.releasingId = row.id;
    row.status = 'Released';
    this.demo.persistList('column_receiving', this.rows);

    const type =
      row.column_type ||
      (this.allOrders.find((o) => o.order_no === row.order_no)?.column_type) ||
      String(row.master_ref || '').replace(/^HGC/i, '') ||
      '';
    const existing = this.inventory.findIndex((i) => i.column_id === row.column_id);
    const invRow = {
      column_id: row.column_id,
      type,
      master_ref: row.master_ref || '',
      serial: row.serial || '',
      injections: 0,
      last_used: '',
      hplc: '',
      status: 'Active',
      recv_no: row.recv_no,
    };
    if (existing >= 0) {
      this.inventory[existing] = { ...this.inventory[existing], ...invRow, injections: this.inventory[existing].injections || 0 };
    } else {
      this.inventory = [invRow, ...this.inventory];
    }
    this.demo.persistList('column_inventory', this.inventory);
    this.releasingId = null;
    this.notify(true, 'Column released to inventory / Logbook.');
  }

  isQuarantine(r: any): boolean {
    return String(r?.status || '').toLowerCase() === 'quarantine';
  }
}
