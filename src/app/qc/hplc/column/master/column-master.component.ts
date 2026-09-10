import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';
declare let alertify: any;

@Component({
  selector: 'app-hplc-column-master',
  templateUrl: './column-master.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnMasterComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  rows: any[] = [];
  types = this.demo.getStandardColumnTypes();
  showForm = false;
  form: any = {};
  saving = false;
  actionId: number | string | null = null;

  constructor(public demo: HplcDemoDataService, private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadRows();
    this.resetForm();
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

  loadRows(): void {
    this.service.get('qc/hplc.php?type=getHPLCLog').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.rows = list.map((r: any) => ({
          ...r,
          master_no: r.column_no || r.master_no || '',
          column_type: r.column_name || r.column_type || '',
          usp_code: r.usp_l_code || r.usp_code || '',
          dimensions: r.dimensions_mm || r.dimensions || '',
          particle_um: r.particle_size_um || r.particle_um || '',
          pore_a: r.pore_size_a || r.pore_a || '',
          catalog: r.catalog_no || r.catalog || '',
          pharmacopoeia: r.pharmacopoeia_reference || r.pharmacopoeia || '',
        }));
        if (this.rows.length === 0 && this.demoMode) {
          this.rows = this.demo.getColumnMaster();
        }
      },
      error: () => {
        this.rows = this.demoMode ? this.demo.getColumnMaster() : [];
      },
    });
  }

  resetForm(): void {
    this.form = {
      column_type: 'C18',
      stationary_phase: '',
      usp_code: 'L1',
      dimensions: '',
      particle_um: 5,
      pore_a: 100,
      end_capped: 'Yes',
      pH_range: '2–8',
      max_pressure_bar: 400,
      manufacturer: '',
      catalog: '',
      pharmacopoeia: 'USP / Ph. Eur.',
    };
    this.onTypeChange();
  }

  onTypeChange(): void {
    const t = this.types.find((x) => x.code === this.form.column_type);
    if (t) {
      this.form.stationary_phase = t.phase;
      this.form.usp_code = t.usp;
      this.form.dimensions = t.typical;
    }
  }

  isPending(r: any): boolean {
    return String(r?.status || '').toLowerCase() === 'pending';
  }

  save(): void {
    if (this.saving) {
      return;
    }
    if (!String(this.form.column_type || '').trim()) {
      this.notify(false, 'Column type is required.');
      return;
    }
    this.saving = true;
    const payload = {
      technique: 'HPLC',
      column_name: this.form.column_type,
      usp_l_code: this.form.usp_code,
      pharmacopoeia_reference: this.form.pharmacopoeia,
      stationary_phase: this.form.stationary_phase,
      dimensions_mm: this.form.dimensions,
      particle_size_um: String(this.form.particle_um ?? ''),
      pore_size_a: String(this.form.pore_a ?? ''),
      end_capped: this.form.end_capped,
      manufacturer: this.form.manufacturer,
      catalog_no: this.form.catalog,
    };
    this.service.post('qc/hplc.php?type=saveHPLC', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        this.saving = false;
        if (response?.status === 'success') {
          this.notify(true, 'Column master saved as Pending — approve to use in Ordering.');
          this.showForm = false;
          this.resetForm();
          this.loadRows();
        } else {
          this.notify(false, response?.status || 'Save failed');
        }
      },
      error: () => {
        this.saving = false;
        this.notify(false, 'Save failed');
      },
    });
  }

  updateStatus(row: any, action: 'approve' | 'reject'): void {
    const id = row?.id;
    if (!id || this.actionId != null) {
      return;
    }
    this.actionId = id;
    this.service.get('qc/hplc.php?type=updateHPLC&id=' + encodeURIComponent(String(id)) + '&status=' + action).subscribe({
      next: (response: any) => {
        this.actionId = null;
        if (response?.status === 'success') {
          this.notify(true, action === 'approve' ? 'Column master approved' : 'Column master rejected');
          this.loadRows();
        } else {
          this.notify(false, response?.status || 'Update failed');
        }
      },
      error: () => {
        this.actionId = null;
        this.notify(false, 'Update failed');
      },
    });
  }
}
