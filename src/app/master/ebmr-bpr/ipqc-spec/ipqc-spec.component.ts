import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-ipqc-spec',
  templateUrl: './ipqc-spec.component.html',
  styleUrls: ['../ebmr-bpr.theme.css'],
})
export class IpqcSpecComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = {};

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
    let url = 'master/ebmr_bpr.php?type=getIpqcSpecs';
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
        alertify.error('Failed to load IPQC specifications');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.spec_code, r.spec_name, r.parameter, r.stage_ref].join(' ').toLowerCase().includes(q)
    );
  }

  newRow(): void {
    this.editing = false;
    this.form = {
      spec_name: '',
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      parameter: '',
      test_method: '',
      specification: '',
      uom: '',
      min_limit: '',
      max_limit: '',
      frequency: '',
      is_critical: 'No',
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
    if (!this.form.spec_name || !this.form.parameter) {
      alertify.error('Specification name and parameter are required');
      return;
    }
    const mn = parseFloat(this.form.min_limit);
    const mx = parseFloat(this.form.max_limit);
    if (!isNaN(mn) && !isNaN(mx) && mn > mx) {
      alertify.error('Min limit cannot be greater than max limit');
      return;
    }
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateIpqcSpec&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveIpqcSpec';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:ipqc_spec', detail: 'IPQC spec: ' + (this.form.parameter || this.form.spec_name || ''), recordRef: this.form.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(this.form)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editing ? 'Specification updated' : 'Specification added');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Specification', `Delete "${row.spec_name}"?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteIpqcSpec&id=' + row.id).subscribe((r: any) => {
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
