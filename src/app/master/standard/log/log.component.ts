import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-standard-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  rows: any[] = [];
  filteredRows: any[] = [];
  pagedRows: any[] = [];
  searchQuery = '';
  statusFilter = 'All';
  pageSize = 10;
  currentPage = 1;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getRows();
  }

  getRows() {
    this.service.get('qc/standard.php?type=getStandards').subscribe((response: any) => {
      this.rows = Array.isArray(response) ? response : [];
      this.applyFilter();
    });
  }

  applyFilter() {
    const q = (this.searchQuery || '').toLowerCase().trim();
    this.filteredRows = this.rows.filter((r: any) => {
      const status = (r.status || '').toLowerCase();
      const okStatus = this.statusFilter === 'All' || status === this.statusFilter.toLowerCase();
      const okSearch =
        !q ||
        (r.standard_no || '').toLowerCase().includes(q) ||
        (r.standard_name || '').toLowerCase().includes(q) ||
        (r.material_name || '').toLowerCase().includes(q) ||
        (r.standard_category || r.standard || '').toLowerCase().includes(q) ||
        (r.material_type || '').toLowerCase().includes(q) ||
        (r.analyte_marker || '').toLowerCase().includes(q) ||
        (r.pharmacopeia_reference || '').toLowerCase().includes(q);
      return okStatus && okSearch;
    });
    this.currentPage = 1;
    this.applyPagination();
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil((this.filteredRows?.length || 0) / this.pageSize));
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }

  onPageNoChange(page: any) {
    const p = Number(page) || 1;
    this.currentPage = Math.min(Math.max(1, p), this.totalPages);
    this.applyPagination();
  }

  onPageSizeChange(size: any) {
    this.pageSize = Number(size) || 10;
    this.currentPage = 1;
    this.applyPagination();
  }

  applyPagination() {
    const start = (this.currentPage - 1) * this.pageSize;
    this.pagedRows = this.filteredRows.slice(start, start + this.pageSize);
  }

  download() {
    this.service.open('qc/standard.php?type=downloadStandards&status=' + this.statusFilter);
  }
}
