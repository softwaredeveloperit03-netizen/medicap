import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-generalmaterial',
  templateUrl: './generalmaterial.component.html',
  styleUrls: ['./generalmaterial.component.css'],
})
export class GeneralmaterialComponent implements OnInit {
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights: any[] = [];

  results: any[] = [];
  material_type = 'ALL';
  loading = false;
  selectedResult: any = {};
  isview = false;
  searchQuery = '';
  showCodingPattern = false;
  pageSize = 10;
  page = 1;
  total = 0;
  private searchTimer: any;

  constructor(public service: DataAccessService) {}

  ngOnInit(): void {
    this.get_rights();
    this.getGeneralMaterials();
  }

  get_rights() {
    const empId = encodeURIComponent(localStorage.getItem('emp_id') || '');
    const department = encodeURIComponent(localStorage.getItem('department') || '');
    const endpoint = `hr/employee.php?type=getrights&emp_id=${empId}&dep_name=${department}`;

    this.service.get(endpoint).subscribe(
      (response: any) => {
        this.rights = Array.isArray(response) ? response : [];
        if (this.rights.length > 0) {
          this.isuser = this.rights[0].isuser || 'No';
          this.ischecker = this.rights[0].ischecker || 'No';
          this.isapprover = this.rights[0].isapprover || 'No';
        }
      },
      () => {
        this.rights = [];
      }
    );
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.total / this.pageSize));
  }

  get pageStart(): number {
    return this.total === 0 ? 0 : (this.page - 1) * this.pageSize + 1;
  }

  get pageEnd(): number {
    return Math.min(this.page * this.pageSize, this.total);
  }

  rowNumber(index: number): number {
    return (this.page - 1) * this.pageSize + index + 1;
  }

  onSearchChange(): void {
    if (this.searchTimer) {
      clearTimeout(this.searchTimer);
    }
    this.searchTimer = setTimeout(() => {
      this.page = 1;
      this.getGeneralMaterials();
    }, 350);
  }

  onFilterChange(): void {
    this.page = 1;
    this.getGeneralMaterials();
  }

  onPageSizeChange(): void {
    this.page = 1;
    this.getGeneralMaterials();
  }

  goToPage(p: number): void {
    const next = Math.min(this.totalPages, Math.max(1, p));
    if (next === this.page) {
      return;
    }
    this.page = next;
    this.getGeneralMaterials();
  }

  getGeneralMaterials() {
    const params = [
      'type=getGeneralMaterials1',
      'material_type=' + encodeURIComponent(this.material_type || 'ALL'),
      'q=' + encodeURIComponent((this.searchQuery || '').trim()),
      'page=' + encodeURIComponent(String(this.page)),
      'pageSize=' + encodeURIComponent(String(this.pageSize)),
    ];
    this.loading = true;
    this.service.get('master/general.php?' + params.join('&')).subscribe(
      (response: any) => {
        this.applyListResponse(response);
        this.loading = false;
      },
      () => {
        this.results = [];
        this.total = 0;
        this.loading = false;
      }
    );
  }

  private applyListResponse(response: any): void {
    if (Array.isArray(response)) {
      const filtered = this.clientFilter(response);
      this.total = filtered.length;
      const start = (this.page - 1) * this.pageSize;
      this.results = filtered.slice(start, start + this.pageSize);
      return;
    }
    this.results = Array.isArray(response?.rows) ? response.rows : [];
    this.total = Number(response?.total) || this.results.length;
  }

  private clientFilter(rows: any[]): any[] {
    const q = (this.searchQuery || '').toLowerCase().trim();
    const type = this.material_type || 'ALL';
    return rows.filter((row) => {
      if (type !== 'ALL' && String(row.material_type || '') !== type) {
        return false;
      }
      if (!q) {
        return true;
      }
      return Object.entries(row).some(([key, value]) => {
        if (value == null) {
          return false;
        }
        if (key === 'entry_date') {
          const dateValue = value instanceof Date ? value : new Date(value as any);
          return !Number.isNaN(dateValue.getTime()) && dateValue.toISOString().slice(0, 10).includes(q);
        }
        return String(value).toLowerCase().includes(q);
      });
    });
  }

  view(data: any) {
    this.selectedResult = data || {};
    this.isview = true;
  }
}
