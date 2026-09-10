import { ApplicationRef, Injectable } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Injectable({ providedIn: 'root' })
export class VolumetricLogModalService {
  editModal = false;
  historyModal = false;
  authModal = false;

  historyRows: any[] = [];
  historyTitle = '';

  editForm: any = {
    id: null,
    solution_name: '',
    percentage: '',
    strength: '',
    strength_unit: 'M',
    solution_type: 'Molar',
  };

  authPassword = '';
  pendingUpdatePayload: any = null;

  /** Set by log table after refresh so update can reload list */
  reloadList: (() => void) | null = null;

  constructor(
    private service: DataAccessService,
    private appRef: ApplicationRef
  ) {}

  private refreshUi(): void {
    try {
      this.appRef.tick();
    } catch {
      /* ignore */
    }
  }

  private notifyError(message: string): void {
    if (typeof alertify !== 'undefined' && alertify?.error) {
      alertify.error(message);
      return;
    }
    window.alert(message);
  }

  private notifySuccess(message: string): void {
    if (typeof alertify !== 'undefined' && alertify?.success) {
      alertify.success(message);
      return;
    }
    window.alert(message);
  }

  rowId(row: any): number | null {
    const candidates = [row?.id, row?.ID, row?.solution_id, row?.master_id];
    for (const candidate of candidates) {
      const n = Number(candidate);
      if (Number.isFinite(n) && n > 0) {
        return n;
      }
    }
    return null;
  }

  openEdit(row: any): void {
    const id = this.rowId(row);
    if (!id) {
      this.notifyError('Record id not found');
      return;
    }
    const s = (row?.statusText || row?.status || '').toString().toLowerCase();
    if (s === 'pending') {
      this.notifyError('Pending record cannot be edited until approval is completed');
      return;
    }
    if (s === 'deleted' || Number(row?.is_deleted) === 1) {
      this.notifyError('Deleted record cannot be edited');
      return;
    }
    this.editForm = {
      id,
      solution_name: row.solution_name || '',
      percentage: row.percentage || '',
      strength: row.strength || '',
      strength_unit: row.unit || row.strength_unit || 'M',
      solution_type: row.standard_type || 'Molar',
    };
    this.editModal = true;
    this.refreshUi();
  }

  closeEditModal(): void {
    this.editModal = false;
    this.refreshUi();
  }

  requestUpdateFromEdit(): void {
    if (!this.editForm.solution_name || !this.editForm.solution_type) {
      this.notifyError('Please fill required details');
      return;
    }
    this.pendingUpdatePayload = { ...this.editForm };
    this.authPassword = '';
    this.authModal = true;
    this.refreshUi();
  }

  closeAuthModal(): void {
    this.authModal = false;
    this.pendingUpdatePayload = null;
    this.authPassword = '';
    this.refreshUi();
  }

  submitAuthAction(): void {
    if (!this.authPassword) {
      this.notifyError('Please enter password');
      return;
    }
    if (!this.pendingUpdatePayload?.id) {
      this.notifyError('No update details to submit');
      return;
    }

    this.service.verifyAuthCredential(this.authPassword).subscribe({
      next: (res: any) => {
        if (res?.status !== 'success') {
          this.notifyError(res?.message || 'Invalid password or PIN');
          return;
        }

        this.service
          .postJson('qc/volumetric.php?type=requestVolumetricUpdate', JSON.stringify(this.pendingUpdatePayload))
          .subscribe({
            next: (raw: any) => {
              const r = this.service.parsePhpJson(raw);
              if (r?.status === 'success') {
                this.notifySuccess('Update request sent for approval');
                this.editModal = false;
                this.closeAuthModal();
                this.reloadList?.();
                this.refreshUi();
              } else {
                this.notifyError(r?.status || r?.message || 'Failed to send update request');
              }
            },
            error: () => this.notifyError('Failed to send update request'),
          });
      },
      error: () => this.notifyError('Password verification failed'),
    });
  }

  openHistory(row: any): void {
    const id = this.rowId(row);
    this.historyTitle = row?.solution_name || row?.solution_no || '';
    this.historyRows = [];
    this.historyModal = true;
    this.refreshUi();

    if (!id) {
      this.notifyError('Record id not found — history cannot be loaded');
      return;
    }

    this.service
      .get('qc/volumetric.php?type=getVolumetricUpdateHistory&solution_id=' + id)
      .subscribe({
        next: (response: any) => {
          this.historyRows = Array.isArray(response) ? response : [];
          this.refreshUi();
        },
        error: () => {
          this.historyRows = [];
          this.notifyError('Could not load update history');
        },
      });
  }

  closeHistoryModal(): void {
    this.historyModal = false;
    this.historyRows = [];
    this.refreshUi();
  }
}
