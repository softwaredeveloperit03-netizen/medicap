import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-work-allocation',
  templateUrl: './work-allocation.component.html',
  styleUrls: ['../ebmr-bpr.theme.css'],
})
export class WorkAllocationComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = {};

  skillLevels = ['Operator', 'Senior Operator', 'Supervisor', 'Officer', 'Executive', 'Technician'];

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.loadDosageForms();
    this.load();
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getWorkAllocations';
    if (this.dosageFilter) {
      url += '&dosage_form=' + encodeURIComponent(this.dosageFilter);
    }
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load work allocations');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.alloc_code, r.activity, r.designation, r.stage_ref].join(' ').toLowerCase().includes(q)
    );
  }

  newRow(): void {
    this.editing = false;
    this.form = {
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      activity: '',
      designation: '',
      responsibility: '',
      manpower_count: 1,
      skill_level: 'Operator',
      remarks: '',
    };
    this.modalOpen = true;
  }

  editRow(row: any): void {
    this.editing = true;
    this.form = { ...row };
    this.modalOpen = true;
  }

  save(): void {
    if (!this.form.activity) {
      alertify.error('Activity is required');
      return;
    }
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateWorkAllocation&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveWorkAllocation';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:work_allocation', detail: 'Work allocation: ' + (this.form.activity || ''), recordRef: this.form.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(this.form)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editing ? 'Allocation updated' : 'Allocation added');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Allocation', `Delete activity "${row.activity}"?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteWorkAllocation&id=' + row.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Deleted');
          this.load();
        }
      });
    }, () => {});
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
