import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-direct-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class DirectApprovalComponent implements OnInit {
  results: any[] = [];
  isView = false;
  selectedResult: any = {};
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPending();
  }

  getPending() {
    this.loading = true;
    this.service.get('security/inward.php?type=getPendingDirectChallans').subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load pending direct inward entries');
      }
    });
  }

  view(row: any) {
    this.selectedResult = row || {};
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedResult = {};
  }

  getMaterialNames(row: any): string {
    if (!row) {
      return '-';
    }
    if (row.material_name && String(row.material_name).trim()) {
      return String(row.material_name).trim();
    }
    let materials = row.materials;
    if (typeof materials === 'string' && materials) {
      try {
        materials = JSON.parse(materials);
      } catch {
        materials = [];
      }
    }
    if (!Array.isArray(materials) || materials.length === 0) {
      return '-';
    }
    const names = materials
      .map((m) => (m && (m.material_name || m.chemical_name || m.name || m.material_code)) || '')
      .map((n) => String(n).trim())
      .filter((name) => !!name);
    return names.length ? Array.from(new Set(names)).join(', ') : '-';
  }

  updateStatus(status: 'approve' | 'reject') {
    if (!this.selectedResult || !this.selectedResult.id) {
      alertify.error('Invalid entry');
      return;
    }
    this.service
      .get(
        'security/inward.php?type=updateDirectChallanStatus&status=' +
          status +
          '&id=' +
          this.selectedResult.id
      )
      .subscribe({
        next: (response: any) => {
          if (response && response.status === 'success') {
            alertify.success(status === 'approve' ? 'Approved successfully' : 'Rejected successfully');
            this.closeView();
            this.getPending();
          } else {
            alertify.error((response && response.message) || 'Update failed');
          }
        },
        error: () => {
          alertify.error('Update failed. Please try again.');
        }
      });
  }
}
