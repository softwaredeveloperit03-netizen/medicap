import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inactive',
  templateUrl: './inactive.component.html',
  styleUrls: ['./inactive.component.css']
})
export class InactiveComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getMaterialsLog();
  }

  results;
  loading = false;
  material_type = 'Raw Material';

  getMaterialsLog() {
    this.loading = true;
    const matType = encodeURIComponent(this.material_type);
    this.service
      .get('master/material.php?type=getMaterialsByStatus&material_type=' + matType + '&status=In-Active')
      .subscribe({
        next: (response) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  selectedResult = [];
  isView = false;

  view(id) {
    const materialIndex = this.results.findIndex(
      (material) => material.id === id
    );
    if (materialIndex !== -1) {
      this.selectedResult = this.results[materialIndex];
    } else {
      console.error('Material not found for the given result id.');
    }

    this.isView = true;
  }

  getDisplayVal(val: any): string | number | any {
    if (val === undefined || val === null) return 'NA';
    if (typeof val === 'string' && val.trim() === '') return 'NA';
    return val;
  }

  viewMsds(url) {
    url = this.service.url + '../../upload/product/' + url;
    window.open(url, '_blank');
  }

  isStatusPasswordModal = false;
  statusAuthPassword = '';
  pendingStatusChange: { id: number | null; status: string | null } = { id: null, status: null };

  openStatusAuthModal(id: number, status: string) {
    this.pendingStatusChange = { id, status };
    this.statusAuthPassword = '';
    this.isStatusPasswordModal = true;
  }

  closeStatusAuthModal() {
    this.isStatusPasswordModal = false;
    this.pendingStatusChange = { id: null, status: null };
    this.statusAuthPassword = '';
  }

  submitStatusAuth(form: { valid: boolean; reset?: () => void }) {
    if (!form.valid || !this.statusAuthPassword || !this.pendingStatusChange.id || !this.pendingStatusChange.status) {
      alertify.error('Please enter password or PIN');
      return;
    }
    const id = this.pendingStatusChange.id;
    const status = this.pendingStatusChange.status;
    this.service.verifyAuthCredential(this.statusAuthPassword).subscribe({
      next: (response) => {
        if (response.status === 'success') {
          alertify.success('Password verified');
          this.closeStatusAuthModal();
          if (form.reset) form.reset();
          this.changeStatusById(id, status);
        } else {
          alertify.error(response.message || 'Invalid password or PIN. Action not allowed.');
        }
      },
      error: () => {
        alertify.error('Could not verify password. Check your connection.');
      },
    });
  }

  changeStatusById(id: number | string, status: string) {
    let url =
      'master/material.php?type=ChangeMaterialStatus&id=' +
      id +
      '&status=' +
      encodeURIComponent(status);
    this.service.get(url).subscribe({
      next: (response: any) => {
        const ok = response && String(response['status']).toLowerCase() === 'success';
        if (ok) {
          alertify.success('Material status changed successfully');
          this.isView = false;
          this.getMaterialsLog();
        } else {
          const msg = response && response['status'] ? String(response['status']) : 'Update failed';
          alertify.error(msg);
        }
      },
      error: () => alertify.error('Failed to change material status.'),
    });
  }

  changeStatus(status) {
    if (!this.selectedResult?.['id']) {
      return;
    }
    this.changeStatusById(this.selectedResult['id'], status);
  }

  searchQuery;

  formatEntryDate(val: any): string {
    if (val == null || val === '') return 'NA';
    const d = typeof val === 'string' ? new Date(val) : val;
    if (isNaN(d.getTime())) return 'NA';
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  getEntryByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.entry_by_name || result.created_by_name || result.entry_by || result.created_by || '';
    const id = result.entry_by_id || result.created_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  getInactivatedByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.inactivated_by_name || result.inactive_by_name || result.inactivated_by_emp_name || result.inactivated_by || '';
    const id = result.inactivated_by_id || result.inactive_by_id || result.inactivated_by_emp_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
}
