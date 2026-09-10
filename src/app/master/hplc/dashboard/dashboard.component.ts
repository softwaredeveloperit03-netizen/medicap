import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { STANDARD_HPLC_COLUMN_TEMPLATES } from '../shared/standard-hplc-columns.constants';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  rows: any[] = [];
  filteredRows: any[] = [];
  searchQuery = '';
  loading = false;
  seedingStandards = false;

  currentPage = 1;
  pageSize = 10;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getHPLCLog();
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.filteredRows.length / this.pageSize) || 1);
  }

  get pagedRows(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredRows.slice(start, start + this.pageSize);
  }

  get lastIndexOnPage(): number {
    return Math.min(this.currentPage * this.pageSize, this.filteredRows.length);
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  onPageSizeChange(value: number | string) {
    this.pageSize = typeof value === 'number' ? value : parseInt(String(value), 10) || 10;
    this.currentPage = 1;
  }

  goFirst(): void {
    this.currentPage = 1;
  }

  goPrev(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goNext(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  goLast(): void {
    this.currentPage = this.totalPages;
  }

  getHPLCLog() {
    this.loading = true;
    this.service.get('qc/hplc.php?type=getHPLCLog').subscribe(
      (response: any) => {
        this.rows = Array.isArray(response) ? response : [];
        this.applyFilter();
        this.loading = false;
      },
      () => {
        this.rows = [];
        this.filteredRows = [];
        this.loading = false;
      }
    );
  }

  applyFilter() {
    const q = (this.searchQuery || '').toLowerCase().trim();
    this.filteredRows = this.rows.filter((row: any) => {
      return (
        !q ||
        String(row.column_no || '')
          .toLowerCase()
          .includes(q) ||
        String(row.technique || '')
          .toLowerCase()
          .includes(q) ||
        String(row.column_name || '')
          .toLowerCase()
          .includes(q) ||
        String(row.usp_l_code || '')
          .toLowerCase()
          .includes(q) ||
        String(row.pharmacopoeia_reference || '')
          .toLowerCase()
          .includes(q) ||
        String(row.manufacturer || '')
          .toLowerCase()
          .includes(q) ||
        String(row.status || '')
          .toLowerCase()
          .includes(q) ||
        String(row.entry_by || '')
          .toLowerCase()
          .includes(q) ||
        String(row.entry_date || '')
          .toLowerCase()
          .includes(q)
      );
    });
    this.currentPage = 1;
  }

  seedStandardHplcColumns(): void {
    if (this.seedingStandards) {
      return;
    }
    const masterUserName =
      localStorage.getItem('username') ||
      localStorage.getItem('user') ||
      localStorage.getItem('firstname') ||
      'Master User';
    this.seedingStandards = true;
    const payload = {
      masterUserName,
      columns: STANDARD_HPLC_COLUMN_TEMPLATES,
    };
    this.service.post('qc/hplc.php?type=seedStandardHplcColumns', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        this.seedingStandards = false;
        if (response?.status === 'success') {
          const added = response.added ?? 0;
          const skipped = response.skipped ?? 0;
          alertify.success(`HPLC/GC columns seeded (${added} added, ${skipped} already exist).`);
          this.getHPLCLog();
        } else {
          alertify.error(response?.message || 'Could not seed HPLC/GC columns.');
        }
      },
      error: () => {
        this.seedingStandards = false;
        alertify.error('Could not seed HPLC/GC columns. Check your connection / database.');
      },
    });
  }
}
