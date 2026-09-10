import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-c2c-transfer',
  templateUrl: './c2c-transfer.component.html',
  styleUrls: ['./c2c-transfer.component.css'],
})
export class C2cTransferComponent implements OnInit {
  loading = false;
  materialsLoading = false;
  saving = false;
  logRows: any[] = [];
  logSearch = '';
  logFromDate = '';
  logToDate = '';
  logLoading = false;

  fromSearch = '';
  toSearch = '';
  fromMaterials: any[] = [];
  toMaterials: any[] = [];
  filteredFrom: any[] = [];
  filteredTo: any[] = [];
  showFromPicker = false;
  showToPicker = false;
  private fromSearchTimer: ReturnType<typeof setTimeout> | null = null;
  private toSearchTimer: ReturnType<typeof setTimeout> | null = null;

  selectedFrom: any = null;
  selectedTo: any = null;
  availableQty = 0;
  transferQty: number | null = null;
  unit = '';
  requestRemark = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.setDefaultLogDateRange();
    this.loadLog();
  }

  private parseMaterialRows(data: any): any[] {
    if (Array.isArray(data)) {
      return data;
    }
    if (data?.status === 'error' && data?.message) {
      alertify.error(data.message);
    }
    return [];
  }

  fetchFromMaterials(search = ''): void {
    this.materialsLoading = true;
    let url = 'planning/c2c_transfer.php?type=getMaterialsWithAvailableStock';
    if (search.trim()) {
      url += '&search=' + encodeURIComponent(search.trim());
    }
    this.service.get(url).subscribe({
      next: (rows: any) => {
        this.fromMaterials = this.parseMaterialRows(rows);
        this.filteredFrom = [...this.fromMaterials];
        this.materialsLoading = false;
      },
      error: () => {
        this.fromMaterials = [];
        this.filteredFrom = [];
        this.materialsLoading = false;
        alertify.error('Could not load materials with stock.');
      },
    });
  }

  onFromSearchChange(): void {
    if (this.selectedFrom) {
      return;
    }
    this.showFromPicker = true;
    if (this.fromSearchTimer) {
      clearTimeout(this.fromSearchTimer);
    }
    const q = this.fromSearch.trim();
    if (q.length < 1) {
      this.filteredFrom = [];
      return;
    }
    this.fromSearchTimer = setTimeout(() => this.fetchFromMaterials(q), 280);
  }

  onFromFocus(): void {
    if (this.selectedFrom) {
      return;
    }
    this.showFromPicker = true;
    const q = this.fromSearch.trim();
    if (q.length >= 1) {
      this.fetchFromMaterials(q);
    }
  }

  fetchToMaterials(search = ''): void {
    if (!this.selectedFrom?.material_code) {
      return;
    }
    let url =
      'planning/c2c_transfer.php?type=getMaterialsWithAvailableStock' +
      '&exclude_material_code=' +
      encodeURIComponent(this.selectedFrom.material_code);
    if (this.selectedFrom.material_type) {
      url += '&material_type=' + encodeURIComponent(this.selectedFrom.material_type);
    }
    if (search.trim()) {
      url += '&search=' + encodeURIComponent(search.trim());
    }
    this.service.get(url).subscribe({
      next: (rows: any) => {
        this.toMaterials = this.parseMaterialRows(rows);
        this.filteredTo = [...this.toMaterials];
      },
      error: () => {
        this.toMaterials = [];
        this.filteredTo = [];
      },
    });
  }

  onToSearchChange(): void {
    if (this.selectedTo || !this.selectedFrom) {
      return;
    }
    this.showToPicker = true;
    if (this.toSearchTimer) {
      clearTimeout(this.toSearchTimer);
    }
    const q = this.toSearch.trim();
    if (q.length < 1) {
      this.filteredTo = [];
      return;
    }
    this.toSearchTimer = setTimeout(() => this.fetchToMaterials(q), 280);
  }

  onToFocus(): void {
    if (this.selectedTo || !this.selectedFrom) {
      return;
    }
    this.showToPicker = true;
    const q = this.toSearch.trim();
    if (q.length >= 1) {
      this.fetchToMaterials(q);
    }
  }

  private setDefaultLogDateRange(): void {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    this.logFromDate = this.toIsoDate(start);
    this.logToDate = this.toIsoDate(end);
  }

  private toIsoDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  loadToMaterials(excludeCode?: string, materialType?: string): void {
    this.toMaterials = [];
    this.filteredTo = [];
    if (!excludeCode) {
      return;
    }
    let url =
      'planning/c2c_transfer.php?type=getMaterialsWithAvailableStock' +
      '&exclude_material_code=' +
      encodeURIComponent(excludeCode);
    if (materialType) {
      url += '&material_type=' + encodeURIComponent(materialType);
    }
    this.service.get(url).subscribe({
      next: (rows: any) => {
        this.toMaterials = this.parseMaterialRows(rows);
        this.filteredTo = [...this.toMaterials];
      },
      error: () => {
        this.toMaterials = [];
        this.filteredTo = [];
      },
    });
  }

  filterFrom(): void {
    const q = this.fromSearch.trim().toLowerCase();
    if (!q) {
      this.filteredFrom = [...this.fromMaterials];
      return;
    }
    this.filteredFrom = this.fromMaterials.filter((m) =>
      `${m.material_code} ${m.material_name}`.toLowerCase().includes(q)
    );
  }

  filterTo(): void {
    const q = this.toSearch.trim().toLowerCase();
    if (!q) {
      this.filteredTo = [...this.toMaterials];
      return;
    }
    this.filteredTo = this.toMaterials.filter((m) =>
      `${m.material_code} ${m.material_name}`.toLowerCase().includes(q)
    );
  }

  selectFrom(mat: any): void {
    this.selectedFrom = mat;
    this.fromSearch = `${mat.material_code} — ${mat.material_name || ''}`;
    this.filteredFrom = [];
    this.showFromPicker = false;
    this.availableQty = Number(mat.available_qty ?? 0);
    this.unit = mat.unit || '';
    this.selectedTo = null;
    this.toSearch = '';
    this.transferQty = null;
    this.loadToMaterials(mat.material_code, mat.material_type);
  }

  selectTo(mat: any): void {
    this.selectedTo = mat;
    this.toSearch = `${mat.material_code} — ${mat.material_name || ''}`;
    this.filteredTo = [];
    this.showToPicker = false;
    if (!this.unit && mat.unit) {
      this.unit = mat.unit;
    }
  }

  refreshAvailableQty(): void {
    if (!this.selectedFrom?.material_code) {
      return;
    }
    this.service
      .get(
        'planning/c2c_transfer.php?type=getMaterialAvailableQty&material_code=' +
          encodeURIComponent(this.selectedFrom.material_code)
      )
      .subscribe((res: any) => {
        this.availableQty = Number(res?.available_qty ?? 0);
        if (res?.unit) {
          this.unit = res.unit;
        }
      });
  }

  saveRequest(): void {
    if (!this.selectedFrom?.material_code || !this.selectedTo?.material_code) {
      alertify.error('Select source and destination materials.');
      return;
    }
    const qty = Number(this.transferQty ?? 0);
    if (qty <= 0) {
      alertify.error('Enter transfer qty.');
      return;
    }
    if (qty > this.availableQty) {
      alertify.error('Transfer qty cannot exceed available qty.');
      return;
    }

    this.saving = true;
    const payload = {
      from_material_code: this.selectedFrom.material_code,
      to_material_code: this.selectedTo.material_code,
      transfer_qty: qty,
      request_remark: this.requestRemark,
      request_source: 'Planning',
    };
    this.service.post('planning/c2c_transfer.php?type=saveC2cTransferRequest', JSON.stringify(payload)).subscribe({
      next: (res: any) => {
        this.saving = false;
        if (res?.status === 'success') {
          alertify.success('Request ' + (res.request_no || '') + ' saved for dept head approval.');
          this.resetForm();
          this.loadLog();
        } else {
          alertify.error(res?.message || 'Could not save request.');
        }
      },
      error: () => {
        this.saving = false;
        alertify.error('Could not save request.');
      },
    });
  }

  resetForm(): void {
    this.selectedFrom = null;
    this.selectedTo = null;
    this.fromSearch = '';
    this.toSearch = '';
    this.availableQty = 0;
    this.transferQty = null;
    this.unit = '';
    this.requestRemark = '';
    this.toMaterials = [];
    this.filteredTo = [];
    this.showFromPicker = false;
    this.showToPicker = false;
    this.filterFrom();
  }

  loadLog(): void {
    if (!this.logFromDate || !this.logToDate) {
      alertify.error('Select From and To dates.');
      return;
    }
    if (this.logFromDate > this.logToDate) {
      alertify.error('From date cannot be after To date.');
      return;
    }
    this.logLoading = true;
    const url =
      'planning/c2c_transfer.php?type=getC2cTransferLog' +
      '&from_date=' + encodeURIComponent(this.logFromDate) +
      '&to_date=' + encodeURIComponent(this.logToDate) +
      '&search=' + encodeURIComponent(this.logSearch.trim());
    this.service.get(url).subscribe({
      next: (rows: any) => {
        this.logRows = Array.isArray(rows) ? rows : [];
        this.logLoading = false;
      },
      error: () => {
        this.logRows = [];
        this.logLoading = false;
      },
    });
  }

  statusLabel(status: string): string {
    return (status || '').replace(/_/g, ' ');
  }
}
