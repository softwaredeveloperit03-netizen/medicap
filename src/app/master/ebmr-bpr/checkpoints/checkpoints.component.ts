import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-checkpoints',
  templateUrl: './checkpoints.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './checkpoints.component.css'],
})
export class CheckpointsComponent implements OnInit {
  category = 'line_clearance';
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  modalOpen = false;
  editing = false;
  form: any = {};

  responseTypes = ['Yes/No', 'Yes/No/NA', 'Done/Not Done', 'Verified/Not Verified', 'Text'];

  meta: any = {
    line_clearance: { title: 'Line Clearance Checkpoints', icon: 'fas fa-broom', sub: 'Pre-start line clearance verification points' },
    department: { title: 'Department Checkpoints', icon: 'fas fa-clipboard-list', sub: 'Production / department in-process verification points' },
    qa: { title: 'QA Checkpoints', icon: 'fas fa-user-shield', sub: 'Quality assurance review and sign-off points' },
  };

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute, private esign: EsignService) {}

  ngOnInit(): void {
    this.route.queryParams.subscribe((p) => {
      this.category = p['category'] || 'line_clearance';
      this.load();
    });
    this.loadDosageForms();
  }

  get title(): string {
    return (this.meta[this.category] || {}).title || 'Checkpoints';
  }
  get icon(): string {
    return (this.meta[this.category] || {}).icon || 'fas fa-clipboard-check';
  }
  get sub(): string {
    return (this.meta[this.category] || {}).sub || '';
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getCheckpoints&category=' + this.category;
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
        alertify.error('Failed to load checkpoints');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.checkpoint_code, r.checkpoint_text, r.stage_ref, r.responsibility].join(' ').toLowerCase().includes(q)
    );
  }

  switchCategory(cat: string): void {
    this.router.navigate(['/master/ebmr-bpr/checkpoints'], { queryParams: { category: cat } });
  }

  newRow(): void {
    this.editing = false;
    this.form = {
      category: this.category,
      checkpoint_text: '',
      dosage_form: this.dosageFilter || '',
      stage_ref: '',
      expected_response: 'Yes/No',
      responsibility: '',
      seq_no: this.rows.length + 1,
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
    if (!this.form.checkpoint_text) {
      alertify.error('Checkpoint text is required');
      return;
    }
    this.form.category = this.category;
    const url = this.editing
      ? 'master/ebmr_bpr.php?type=updateCheckpoint&id=' + this.form.id
      : 'master/ebmr_bpr.php?type=saveCheckpoint';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:checkpoint:' + this.category, detail: this.title + ': ' + (this.form.checkpoint_text || ''), recordRef: this.form.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(this.form)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editing ? 'Checkpoint updated' : 'Checkpoint added');
            this.modalOpen = false;
            this.load();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }

  remove(row: any): void {
    alertify.confirm('Delete Checkpoint', 'Delete this checkpoint?', () => {
      this.service.get('master/ebmr_bpr.php?type=deleteCheckpoint&id=' + row.id).subscribe((r: any) => {
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
