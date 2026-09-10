import { Component, OnDestroy, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rmpmindent',
  templateUrl: './rmpmindent.component.html',
  styleUrls: ['./rmpmindent.component.css'],
})
export class RmpmindentComponent implements OnInit, OnDestroy {
  loading = false;
  Results: any[] = [];

  searchText = '';
  currentPage = 1;
  pageSize = 25;
  totalRecords = 0;

  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLog();
  }

  ngOnDestroy(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
  }

  onSearchChange(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    this.searchDebounceTimer = setTimeout(() => {
      this.currentPage = 1;
      this.loadLog();
    }, 350);
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadLog();
  }

  onPageSizeChange(size: number): void {
    this.pageSize = size;
    this.currentPage = 1;
    this.loadLog();
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  private buildLogUrl(): string {
    let url =
      'purchase/indent.php?type=getPlanningConfirmIndentLog&page=' +
      this.currentPage +
      '&limit=' +
      this.pageSize;
    const q = (this.searchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  loadLog(): void {
    this.loading = true;
    this.service.get(this.buildLogUrl()).subscribe({
      next: (response: any) => {
        if (response?.status === 'success') {
          this.Results = Array.isArray(response.data) ? response.data : [];
          this.totalRecords = Number(response.total ?? 0);
        } else if (Array.isArray(response)) {
          this.Results = response;
          this.totalRecords = response.length;
        } else {
          this.Results = [];
          this.totalRecords = 0;
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error fetching indent log:', err);
        this.Results = [];
        this.totalRecords = 0;
        this.loading = false;
      },
    });
  }

  statusClass(status: string): string {
    const s = (status || '').toLowerCase();
    if (
      s.includes('received') ||
      s.includes('grn approved') ||
      s.includes('po approved')
    ) {
      return 'label-success';
    }
    if (
      s.includes('po created') ||
      s.includes('ready for po') ||
      s.includes('sent') ||
      s.includes('confirm')
    ) {
      return 'label-info';
    }
    if (
      s.includes('hold') ||
      s.includes('reject') ||
      s.includes('cancel') ||
      s.includes('return')
    ) {
      return 'label-danger';
    }
    if (s.includes('pending') || s.includes('raised') || s.includes('grn')) {
      return 'label-warning';
    }
    return 'label-info';
  }

  formatSentInfo(wo: any): string {
    const name = String(wo?.sent_by_name || wo?.sent_by || '').trim();
    const on = wo?.sent_on ? String(wo.sent_on) : '';
    if (name && on) {
      return name;
    }
    return name || wo?.entry_by || '—';
  }
}
