import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-profiles',
  templateUrl: './profiles.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './profiles.component.css'],
})
export class ProfilesComponent implements OnInit {
  recordType = 'eBMR';
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  search = '';

  modalOpen = false;
  form: any = {};

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute, private esign: EsignService) {}

  private builderBase(): string {
    return (this.route.snapshot.data?.['builderBase'] as string) || '/master/ebmr-bpr/builder';
  }

  ngOnInit(): void {
    this.route.queryParams.subscribe((p) => {
      this.recordType = p['type'] || 'eBMR';
      this.load();
    });
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  get titleNoun(): string {
    return this.recordType === 'eBPR' ? 'eBPR (Batch Packing Record)' : 'eBMR (Batch Manufacturing Record)';
  }

  load(): void {
    this.loading = true;
    this.service
      .get('master/ebmr_bpr.php?type=getProfiles&record_type=' + this.recordType)
      .subscribe({
        next: (r: any) => {
          this.rows = Array.isArray(r) ? r : [];
          this.loading = false;
        },
        error: () => {
          this.loading = false;
          alertify.error('Failed to load profiles');
        },
      });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.profile_code, r.profile_name, r.dosage_form].join(' ').toLowerCase().includes(q)
    );
  }

  newProfile(): void {
    this.form = {
      profile_name: '',
      record_type: this.recordType,
      dosage_form: '',
      version: '1.0',
    };
    this.modalOpen = true;
  }

  createProfile(): void {
    if (!this.form.profile_name || !this.form.dosage_form) {
      alertify.error('Profile name and dosage form are required');
      return;
    }
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:profile', detail: 'Create profile: ' + this.form.profile_name })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=saveProfile', JSON.stringify(this.form)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Profile created');
            this.modalOpen = false;
            this.router.navigate([this.builderBase(), r.id]);
          } else {
            alertify.error((r && r.message) || 'Create failed');
          }
        });
      });
  }

  openBuilder(row: any): void {
    this.router.navigate([this.builderBase(), row.id]);
  }

  remove(row: any): void {
    alertify.confirm('Delete Profile', `Delete profile "${row.profile_name}"?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteProfile&id=' + row.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Deleted');
          this.load();
        }
      });
    }, () => {});
  }

  statusClass(s: string): string {
    const v = (s || '').toLowerCase();
    if (v === 'approved') return 'eb-badge-ok';
    if (v === 'draft') return 'eb-badge-warn';
    return 'eb-badge-muted';
  }

  close(): void {
    const closeRoute = this.route.snapshot.data?.['closeRoute'] || '/master/ebmr-bpr';
    this.router.navigate([closeRoute]);
  }
}
