import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-audittrail',
  templateUrl: './audittrail.component.html',
  styleUrls: ['./audittrail.component.css']
})
export class AuditTrailComponent implements OnInit {
  from_date = '';
  to_date = '';
  searchQuery = '';
  loading = false;
  results: any[] = [];

  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 20;
  currentPage = 1;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    const now = new Date();
    const to = now.toISOString().slice(0, 10);
    const fromObj = new Date();
    fromObj.setDate(fromObj.getDate() - 30);
    const from = fromObj.toISOString().slice(0, 10);
    this.from_date = from;
    this.to_date = to;
    this.getAuditTrail();
  }

  getAuditTrail(): void {
    this.loading = true;
    this.service
      .get(
        'store/raw.php?type=getReceivingAuditTrail&from_date=' +
          encodeURIComponent(this.from_date) +
          '&to_date=' +
          encodeURIComponent(this.to_date)
      )
      .subscribe(
        (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
          this.currentPage = 1;
        },
        () => {
          this.results = [];
          this.loading = false;
        }
      );
  }

  onPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 20;
    this.currentPage = 1;
  }

  getSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  get filteredRows(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results || [];
    }

    const q = this.searchQuery.toLowerCase().trim();
    return (this.results || []).filter((row: any) => {
      return Object.values(row || {}).some((v) => {
        if (v === null || v === undefined) {
          return false;
        }
        return String(v).toLowerCase().includes(q);
      });
    });
  }
}
