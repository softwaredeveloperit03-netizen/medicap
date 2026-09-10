import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-reports',
  templateUrl: './reports.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', '../batches/batches.component.css'],
})
export class ReportsComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  search = '';
  statusFilter = '';
  typeFilter = '';
  returnUrl = '/master/ebmr-bpr';
  pageTitle = 'Batch Records & Reports';
  pageSub = 'Generate and print eBMR/eBPR batch records, yield & audit reports';
  /** When true (Completed BMR page), filters stay locked to Released eBMR */
  completedMode = false;

  statuses = ['In Progress', 'On Hold', 'Correction Required', 'Submitted for Approval', 'Approved', 'Rejected', 'Released for Packing', 'Released', 'Cancelled'];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    if (this.route.snapshot.data?.['closeRoute']) {
      this.returnUrl = String(this.route.snapshot.data['closeRoute']);
    }
    if (this.route.snapshot.data?.['pageTitle']) {
      this.pageTitle = String(this.route.snapshot.data['pageTitle']);
      this.pageSub = 'Released / completed batch manufacturing records ready for print & archive';
    }
    if (this.route.snapshot.data?.['statusFilter']) {
      this.statusFilter = String(this.route.snapshot.data['statusFilter']);
    }
    if (this.route.snapshot.data?.['recordType']) {
      this.typeFilter = String(this.route.snapshot.data['recordType']);
    }
    this.completedMode =
      this.statusFilter === 'Released' &&
      !!(this.route.snapshot.data?.['pageTitle']) &&
      String(this.route.snapshot.data?.['pageTitle']).toLowerCase().indexOf('completed') >= 0;
    this.route.queryParams.subscribe((p) => {
      if (p['returnUrl']) {
        this.returnUrl = String(p['returnUrl']);
      }
      if (p['type']) {
        this.typeFilter = String(p['type']);
      } else if (this.route.snapshot.data?.['recordType']) {
        this.typeFilter = String(this.route.snapshot.data['recordType']);
      }
      if (p['status']) {
        this.statusFilter = String(p['status']);
      } else if (this.route.snapshot.data?.['statusFilter']) {
        this.statusFilter = String(this.route.snapshot.data['statusFilter']);
      }
      this.load();
    });
  }

  private reportBase(): string {
    return (this.route.snapshot.data?.['reportBase'] as string) || '/master/ebmr-bpr/report';
  }

  private reportsListUrl(): string {
    const isProd = !!(this.route.snapshot.data?.['reportBase'] as string);
    const isCompleted = (this.route.snapshot.data?.['statusFilter'] as string) === 'Released'
      && !!(this.route.snapshot.data?.['pageTitle']);
    const base = isProd
      ? (isCompleted ? '/fproduction/ebmr/completed' : '/fproduction/ebmr/reports')
      : '/master/ebmr-bpr/reports';
    let back = `${base}?returnUrl=${encodeURIComponent(this.returnUrl)}`;
    if (this.typeFilter && !isCompleted) {
      back += `&type=${encodeURIComponent(this.typeFilter)}`;
    }
    if (this.statusFilter && !isCompleted) {
      back += `&status=${encodeURIComponent(this.statusFilter)}`;
    }
    return back;
  }

  load(): void {
    if (this.completedMode) {
      this.statusFilter = 'Released';
      this.typeFilter = 'eBMR';
    }
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getBatches';
    if (this.typeFilter) {
      url += '&record_type=' + encodeURIComponent(this.typeFilter);
    }
    if (this.statusFilter) {
      url += '&status=' + encodeURIComponent(this.statusFilter);
    }
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : (r && Array.isArray(r.batches) ? r.batches : []);
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load batch records');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) {
      return this.rows;
    }
    return this.rows.filter((r) =>
      [r.batch_no, r.product_code, r.product_name, r.profile_code, r.plan_no, r.work_order_no]
        .join(' ')
        .toLowerCase()
        .includes(q)
    );
  }

  statusClass(s: string): string {
    const v = (s || '').toLowerCase();
    if (v === 'released' || v === 'approved') {
      return 'eb-badge-ok';
    }
    if (v === 'rejected' || v === 'correction required') {
      return 'eb-badge-crit';
    }
    if (v === 'submitted for approval') {
      return 'eb-badge-type';
    }
    return 'eb-badge-warn';
  }

  progressPct(r: any): number {
    if (!r.steps_total) {
      return 0;
    }
    return Math.round((r.steps_checked / r.steps_total) * 100);
  }

  batchSizeLabel(r: any): string {
    const size = [r.batch_size, r.batch_size_uom || r.pack_unit].filter(Boolean).join(' ').trim();
    return size || '—';
  }

  openReport(r: any): void {
    this.router.navigate([this.reportBase(), r.id], {
      queryParams: { returnUrl: this.reportsListUrl() },
    });
  }

  close(): void {
    this.router.navigateByUrl(this.returnUrl);
  }
}
