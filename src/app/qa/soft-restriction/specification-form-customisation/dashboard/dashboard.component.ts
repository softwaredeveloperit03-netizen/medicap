import { Component, OnInit } from '@angular/core';
import { SpecificationCustomisationLogMeta, SpecificationScope } from '../specification-form-customisation.constants';
import { SpecificationFormCustomisationService } from '../specification-form-customisation.service';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-specification-form-customisation-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  scope: SpecificationScope = 'Raw Material';
  logs: SpecificationCustomisationLogMeta[] = [];
  loading = false;
  activeLogId: number | null = null;
  authModal = false;
  authPassword = '';
  pendingActiveRow: SpecificationCustomisationLogMeta | null = null;

  readonly scopes: SpecificationScope[] = ['Raw Material', 'Packing Material', 'Finish Product'];

  constructor(
    private service: SpecificationFormCustomisationService,
    private dataService: DataAccessService
  ) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.service.getActiveLayout(this.scope).subscribe({
      next: (activeRes: any) => {
        this.activeLogId = activeRes?.log_id ? Number(activeRes.log_id) : null;
        this.service.getLog(this.scope).subscribe({
          next: (res: any) => {
            const rows = Array.isArray(res) ? res : [];
            this.logs = rows.sort((a: any, b: any) => {
              const aId = Number(a?.id || 0);
              const bId = Number(b?.id || 0);
              if (aId === this.activeLogId && bId !== this.activeLogId) return -1;
              if (bId === this.activeLogId && aId !== this.activeLogId) return 1;
              return bId - aId;
            });
            this.loading = false;
          },
          error: () => {
            this.logs = [];
            this.loading = false;
          },
        });
      },
      error: () => {
        this.activeLogId = null;
        this.service.getLog(this.scope).subscribe({
          next: (res: any) => {
            this.logs = Array.isArray(res) ? res : [];
            this.loading = false;
          },
          error: () => {
            this.logs = [];
            this.loading = false;
          },
        });
      },
    });
  }

  setScope(scope: SpecificationScope): void {
    if (this.scope === scope) {
      return;
    }
    this.scope = scope;
    this.load();
  }

  getActiveStatus(row: SpecificationCustomisationLogMeta): string {
    const id = Number(row?.id || 0);
    if (this.activeLogId && id === this.activeLogId) {
      return 'Current Active';
    }
    return 'Obsolete';
  }

  canRequestCurrentActive(row: SpecificationCustomisationLogMeta): boolean {
    const rowStatus = String(row?.status || '').toLowerCase();
    return this.getActiveStatus(row) !== 'Current Active' && rowStatus === 'approved';
  }

  openMakeCurrentActive(row: SpecificationCustomisationLogMeta): void {
    if (!this.canRequestCurrentActive(row)) {
      return;
    }
    this.pendingActiveRow = row;
    this.authPassword = '';
    this.authModal = true;
  }

  closeAuthModal(): void {
    this.authModal = false;
    this.authPassword = '';
    this.pendingActiveRow = null;
  }

  submitMakeCurrentActive(): void {
    if (!this.pendingActiveRow?.id) {
      return;
    }
    if (!this.authPassword) {
      alertify.error('Please enter password');
      return;
    }
    this.dataService.verifyPassword(this.authPassword).subscribe(
      (res: any) => {
        if (res?.status !== 'success') {
          alertify.error('Invalid password');
          return;
        }
        const fields = Array.isArray(this.pendingActiveRow?.fields) ? this.pendingActiveRow?.fields : [];
        if (!fields || fields.length === 0) {
          alertify.error('Selected log has no fields to submit');
          return;
        }
        const entryBy = localStorage.getItem('username') || 'Unknown';
        this.service
          .saveRequest({
            scope: this.scope,
            entry_by: entryBy,
            fields,
          })
          .subscribe(
            (saveRes: any) => {
              if (saveRes?.status === 'success') {
                alertify.success('Sent for approval. After approval it will be Current Active.');
                this.closeAuthModal();
                this.load();
              } else {
                alertify.error(saveRes?.message || 'Failed to send for approval');
              }
            },
            () => alertify.error('Failed to send for approval')
          );
      },
      () => alertify.error('Password verification failed')
    );
  }
}
