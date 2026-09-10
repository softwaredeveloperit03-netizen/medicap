import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  OnDestroy,
  OnInit,
} from '@angular/core';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-planning-status-log',
  templateUrl: './status-log.component.html',
  styleUrls: ['./status-log.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlanningStatusLogComponent implements OnInit, OnDestroy {
  private readonly destroy$ = new Subject<void>();
  private readonly search$ = new Subject<string>();

  searchText = '';
  stageFilter: 'all' | 'shortage' | 'indent' | 'plan' = 'all';
  loading = false;
  rows: any[] = [];
  rowsBackup: any[] = [];
  visibleRows: any[] = [];

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(250), takeUntil(this.destroy$))
      .subscribe((q) => {
        this.applyFilterInternal(q);
        this.cdr.markForCheck();
      });
    this.loadLog();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadLog(): void {
    this.loading = true;
    this.cdr.markForCheck();
    const stageParam =
      this.stageFilter !== 'all' ? `&stage=${this.stageFilter}` : '';
    this.service
      .get(`marketing/po.php?type=getPlanningStatusLog${stageParam}`)
      .subscribe({
        next: (data: any) => {
          this.rows = Array.isArray(data) ? data : [];
          this.rowsBackup = [...this.rows];
          this.applyFilterInternal(this.searchText);
          this.loading = false;
          this.cdr.markForCheck();
        },
        error: () => {
          this.rows = [];
          this.rowsBackup = [];
          this.visibleRows = [];
          this.loading = false;
          this.cdr.markForCheck();
        },
      });
  }

  onStageChange(stage: 'all' | 'shortage' | 'indent' | 'plan'): void {
    this.stageFilter = stage;
    this.loadLog();
  }

  applyFilter(): void {
    this.search$.next(this.searchText ?? '');
  }

  private applyFilterInternal(q: string): void {
    const needle = (q ?? '').trim().toLowerCase();
    if (!needle) {
      this.visibleRows = [...this.rowsBackup];
      return;
    }
    this.visibleRows = this.rowsBackup.filter((r) =>
      [
        r.forecast_no,
        r.fo_code,
        r.client_name,
        r.client_code,
        r.product_name,
        r.product_code,
        r.bulk_code,
        r.workorder_no,
        r.material_code,
        r.material_name,
        r.indent_display_no,
        r.purchase_po_no,
        r.fo_receive_status,
        r.wo_status,
        r.analysis_status,
        r.shortage_status,
        r.indent_workflow_status,
        r.purchase_po_status,
        r.plan_status,
        r.stock_book_status,
        r.planning_indent_remark,
      ]
        .map((v) => String(v ?? '').toLowerCase())
        .some((v) => v.includes(needle))
    );
  }

  trackByRow(_i: number, row: any): string {
    return `${row.deduction_id ?? ''}-${row.workorder_no ?? ''}-${row.material_code ?? ''}`;
  }

  getForecastNo(row: any): string {
    return row?.forecast_no || row?.fo_code || '—';
  }

  formatDate(val: any): string {
    if (!val) {
      return '—';
    }
    const d = new Date(val);
    return isNaN(d.getTime()) ? String(val) : d.toLocaleString('en-GB');
  }

  badgeClass(kind: string, value: string): string {
    const v = (value ?? '').toLowerCase();
    if (kind === 'shortage') {
      if (v.includes('no shortage')) return 'psl-badge-ok';
      if (v.includes('pending')) return 'psl-badge-warn';
      return 'psl-badge-danger';
    }
    if (kind === 'indent') {
      if (v === 'raised' || v.includes('indent sent')) return 'psl-badge-info';
      if (v === 'hold') return 'psl-badge-warn';
      if (v === 'returned' || v === 'cancelled') return 'psl-badge-muted';
      if (v === 'not raised') return 'psl-badge-neutral';
      return 'psl-badge-info';
    }
    if (kind === 'po') {
      if (v.includes('approved')) return 'psl-badge-ok';
      if (v.includes('pending')) return 'psl-badge-warn';
      if (v.includes('ready')) return 'psl-badge-info';
      return 'psl-badge-neutral';
    }
    if (kind === 'plan') {
      if (v.includes('can plan')) return 'psl-badge-ok';
      if (v.includes('line booking')) return 'psl-badge-info';
      if (v.includes('stp')) return 'psl-badge-info';
      if (v.includes('verified')) return 'psl-badge-warn';
      return 'psl-badge-neutral';
    }
    if (kind === 'analysis') {
      return v.includes('sent') ? 'psl-badge-info' : 'psl-badge-neutral';
    }
    if (kind === 'wo') {
      if (v.includes('verified') || v.includes('plan')) return 'psl-badge-ok';
      if (v.includes('shortage') || v.includes('hold')) return 'psl-badge-warn';
      return 'psl-badge-neutral';
    }
    return 'psl-badge-neutral';
  }
}
