import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  /** Existing full entry form */
  isForm = false;

  logFilter = 'All';
  material_type = '';
  entries: any[] = [];
  filteredEntries: any[] = [];
  products: any[] = [];
  employee: any[] = [];
  units;
  racks: any[] = [];
  selectedEntry: any = null;
  editingId: number | null = null;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getPendingLog();
    this.getEmployees();
    this.getUnits();
    this.getRacks();
  }

  applyLogFilter() {
    const filter = this.logFilter || 'All';
    const list = Array.isArray(this.entries) ? this.entries : [];
    if (filter === 'All') {
      this.filteredEntries = list;
      return;
    }
    this.filteredEntries = list.filter((e: any) => {
      const t = String(e?.material_type || '').trim();
      if (filter === 'Finished Product') {
        return t === 'Finished Product' || t === 'Finish Product';
      }
      return t === filter;
    });
  }

  proceed(entry: any) {
    this.selectedEntry = {
      material_code: '',
      batch_no: '',
      batch_size: '',
      ar_no: '',
      analysis_date: '',
      release_date: '',
      sample_by: '',
      sampling_date: '',
      mfg_date: '',
      exp_date: '',
      sample_quantity: '',
      actual_control_sample: '',
      unit: '',
      pack_no: '',
      rack_no: '',
      room_temp: '',
      humidity: '',
      ...(entry || {})
    };
    if (!this.selectedEntry.sampling_date && this.selectedEntry['sampling_start_time']) {
      this.selectedEntry.sampling_date = String(this.selectedEntry['sampling_start_time']).substring(0, 10);
    }
    if (!this.selectedEntry.sample_quantity && this.selectedEntry['control_sample']) {
      this.selectedEntry.sample_quantity = String(this.selectedEntry['control_sample']);
    }
    if (!this.selectedEntry.unit && entry?.unit) {
      this.selectedEntry.unit = String(entry.unit);
    }
    if (this.selectedEntry.analysis_date) {
      this.selectedEntry.analysis_date = String(this.selectedEntry.analysis_date).substring(0, 10);
    }
    this.selectedEntry.unit = String(this.selectedEntry.unit || '').trim();
    this.selectedEntry.rack_no = String(this.selectedEntry.rack_no || '').trim();
    this.selectedEntry.room_temp = String(this.selectedEntry.room_temp || '').trim();
    this.selectedEntry.humidity = String(this.selectedEntry.humidity || '').trim();
    this.selectedEntry.sample_quantity = String(this.selectedEntry.sample_quantity || '').trim();
    this.selectedEntry.actual_control_sample = String(this.selectedEntry.actual_control_sample || '').trim();
    this.selectedEntry.pack_no = String(this.selectedEntry.pack_no || '').trim();

    if (this.selectedEntry.mfg_date && String(this.selectedEntry.mfg_date).length >= 7) {
      this.selectedEntry.mfg_date = String(this.selectedEntry.mfg_date).substring(0, 7);
    }
    if (this.selectedEntry.exp_date && String(this.selectedEntry.exp_date).length >= 7) {
      this.selectedEntry.exp_date = String(this.selectedEntry.exp_date).substring(0, 7);
    }
    this.editingId = entry?.id ? +entry.id : null;
    this.material_type =
      entry?.material_type === 'Finish Product' ? 'Finished Product' : (entry?.material_type || '');
    this.isForm = true;
    this.loadMasterProductsForForm();
    this.getEmployees();
    this.service.get('common.php?type=getUnits').subscribe({
      next: (response: any) => {
        this.units = Array.isArray(response) ? response : [];
        if (this.selectedEntry?.unit) {
          const u = this.selectedEntry.unit;
          this.selectedEntry.unit = '';
          setTimeout(() => {
            if (this.selectedEntry) {
              this.selectedEntry.unit = u;
            }
          });
        }
      },
      error: () => {
        this.units = [];
      }
    });
  }

  loadMasterProductsForForm() {
    if (!this.material_type) {
      this.products = [];
      return;
    }
    this.service
      .get('qa/controlsample.php?type=getProducts&material_type=' + encodeURIComponent(this.material_type))
      .subscribe({
        next: (response: any) => {
          const list = Array.isArray(response) ? response : [];
          this.products = list.map((p: any) => {
            const code = p.material_code || p.product_code || '';
            const name = p.material_name || p.product_name || '';
            return {
              ...p,
              material_code: code,
              product_code: p.product_code || code,
              display_name: name,
              grade: p.grade || ''
            };
          });
        },
        error: () => {
          this.products = [];
        }
      });
  }

  closeForm() {
    this.isForm = false;
    this.editingId = null;
    this.selectedEntry = null;
    this.getPendingLog();
  }

  saveForm(data) {
    if (!data.valid) {
      alertify.warning('All fields are required!');
      return;
    }
    const value = { ...(data.value || {}) };
    if (this.editingId) {
      value.id = this.editingId;
    }
    if (!value.material_type && this.material_type) {
      value.material_type = this.material_type;
    }
    if (this.selectedEntry?.material_code && !value.material_code && !value.product_code) {
      if (this.material_type === 'Finished Product') {
        value.product_code = this.selectedEntry.material_code;
      } else {
        value.material_code = this.selectedEntry.material_code;
      }
    }

    const endpoint = this.editingId
      ? 'qa/controlsample.php?type=updateControlSample'
      : 'qa/controlsample.php?type=saveControlSample';

    this.service.post(endpoint, JSON.stringify(value)).subscribe({
      next: (response) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.reset();
          this.material_type = '';
          this.editingId = null;
          this.selectedEntry = null;
          this.isForm = false;
          alertify.success('Saved Successfully');
          this.router.navigate(['/qc/controlsample/log']);
        } else {
          alertify.error(result.status || this.service.t('common.errorOccurred'));
        }
      },
      error: () => alertify.error('Failed to save control sample')
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getRacks() {
    this.service.get('qa/controlsample.php?type=getRacksLog').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.racks = list.filter((r: any) => String(r?.rack_no || '').trim() !== '');
      },
      error: () => {
        this.racks = [];
      }
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getQCPersons').subscribe({
      next: (response: any) => {
        this.employee = Array.isArray(response) ? response : [];
        if (this.employee.length === 0) {
          this.loadAllEmployeesFallback();
        }
      },
      error: () => this.loadAllEmployeesFallback()
    });
  }

  private loadAllEmployeesFallback() {
    this.service.get('employee.php?type=getEmp').subscribe({
      next: (response: any) => {
        this.employee = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.employee = [];
      }
    });
  }

  getPendingLog() {
    this.service.getJsonArray('qa/controlsample.php?type=getPendingControlSampleEntryLog').subscribe({
      next: (response: any[]) => {
        this.entries = Array.isArray(response) ? response : [];
        this.applyLogFilter();
      },
      error: () => {
        this.entries = [];
        this.filteredEntries = [];
        alertify.error('Could not load control sample entry log. Please refresh or sign in again.');
      }
    });
  }

  close() {
    this.router.navigate(['/qc/controlsample']);
  }

}
