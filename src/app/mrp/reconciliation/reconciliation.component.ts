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

type ReconTab = 'report' | 'map' | 'forecast' | 'confirmed' | 'double' | 'month' | 'log';

@Component({
  selector: 'app-reconciliation',
  templateUrl: './reconciliation.component.html',
  styleUrls: ['./reconciliation.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ReconciliationComponent implements OnInit, OnDestroy {
  private readonly destroy$ = new Subject<void>();
  private readonly search$ = new Subject<string>();

  activeTab: ReconTab = 'report';
  loading = false;
  searchText = '';
  yearFilter = '';
  monthFilter = '';
  orderFilter = '';

  rows: any[] = [];
  rowsBackup: any[] = [];
  visibleRows: any[] = [];
  summary: any = {};

  readonly tabs: { id: ReconTab; label: string; hint: string }[] = [
    {
      id: 'report',
      label: 'Status Report',
      hint: 'What was forecasted vs current situation as of today',
    },
    {
      id: 'map',
      label: 'Forecast vs Confirmed Map',
      hint: 'Which monthly forecast split maps to which work order',
    },
    {
      id: 'forecast',
      label: 'Forecast Actions',
      hint: 'MRP indents raised from requirement analysis by month',
    },
    {
      id: 'confirmed',
      label: 'Net Confirmed Shortage',
      hint: 'WO shortage after eating forecast indent first',
    },
    {
      id: 'double',
      label: 'Double Indent Alert',
      hint: 'Same material with both forecast and WO indents — missing link',
    },
    {
      id: 'month',
      label: 'Month-wise Coverage',
      hint: 'Forecast planned vs confirmed WO quantity by month',
    },
    {
      id: 'log',
      label: 'Forecast vs Actual Log',
      hint: 'Material-level audit: forecast indent, WO shortage, confirmed indent, and physical issue',
    },
  ];

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
    this.loadTab();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  setTab(tab: ReconTab): void {
    this.activeTab = tab;
    this.loadTab();
  }

  loadTab(): void {
    this.loading = true;
    this.cdr.markForCheck();
    let url = `marketing/po.php?type=getReconciliationHub&tab=${this.activeTab}`;
    if (this.yearFilter?.trim()) {
      url += `&year=${encodeURIComponent(this.yearFilter.trim())}`;
    }
    if (this.monthFilter?.trim()) {
      url += `&month=${encodeURIComponent(this.monthFilter.trim())}`;
    }
    if (this.orderFilter?.trim()) {
      url += `&order_no=${encodeURIComponent(this.orderFilter.trim())}`;
    }

    this.service.get(url).subscribe({
      next: (data: any) => {
        this.rows = Array.isArray(data?.rows) ? data.rows : [];
        this.summary = data?.summary ?? {};
        this.rowsBackup = [...this.rows];
        this.applyFilterInternal(this.searchText);
        this.loading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.rows = [];
        this.rowsBackup = [];
        this.visibleRows = [];
        this.summary = {};
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }

  applyFilter(): void {
    this.search$.next(this.searchText ?? '');
  }

  onFilterChange(): void {
    this.loadTab();
  }

  private applyFilterInternal(q: string): void {
    const needle = (q ?? '').trim().toLowerCase();
    if (!needle) {
      this.visibleRows = [...this.rowsBackup];
      return;
    }
    this.visibleRows = this.rowsBackup.filter((row) =>
      JSON.stringify(row).toLowerCase().includes(needle)
    );
  }

  statusClass(status: string): string {
    const s = (status ?? '').toLowerCase();
    if (s.includes('full match') || s.includes('fully confirmed') || s.includes('covered')) {
      return 'recon-badge recon-badge-ok';
    }
    if (s.includes('partial') || s.includes('double') || s.includes('risk')) {
      return 'recon-badge recon-badge-warn';
    }
    if (s.includes('unmatched') || s.includes('no forecast') || s.includes('raise')) {
      return 'recon-badge recon-badge-danger';
    }
    return 'recon-badge';
  }

  num(val: any): number {
    return Number(val || 0);
  }

  get activeTabHint(): string {
    const t = this.tabs.find((x) => x.id === this.activeTab);
    return t?.hint ?? '';
  }

  get activeTabLabel(): string {
    const t = this.tabs.find((x) => x.id === this.activeTab);
    return t?.label ?? '';
  }

  get summaryChips(): { label: string; value: string | number; tone?: 'ok' | 'warn' | 'danger' }[] {
    const s = this.summary || {};
    const chips: { label: string; value: string | number; tone?: 'ok' | 'warn' | 'danger' }[] = [];

    if (s.report_as_of != null) {
      chips.unshift({ label: 'As of', value: s.report_as_of });
    }
    if (s.awaiting_confirmed != null) {
      chips.push({ label: 'Awaiting confirmed order', value: s.awaiting_confirmed, tone: s.awaiting_confirmed > 0 ? 'warn' : 'ok' });
    }
    if (s.forecast_only != null) {
      chips.push({ label: 'Forecast only (no WO)', value: s.forecast_only, tone: s.forecast_only > 0 ? 'warn' : 'ok' });
    }
    if (s.wo_received != null) {
      chips.push({ label: 'WO received', value: s.wo_received });
    }
    if (s.shortage_pending != null) {
      chips.push({ label: 'Shortage pending', value: s.shortage_pending, tone: s.shortage_pending > 0 ? 'danger' : 'ok' });
    }
    if (s.in_production != null) {
      chips.push({ label: 'In production', value: s.in_production, tone: 'ok' });
    }
    if (s.total != null) {
      chips.push({ label: 'Total rows', value: s.total });
    }
    if (s.full_match != null) {
      chips.push({ label: 'Full match', value: s.full_match, tone: 'ok' });
    }
    if (s.unmatched != null) {
      chips.push({ label: 'Unmatched', value: s.unmatched, tone: s.unmatched > 0 ? 'warn' : 'ok' });
    }
    if (s.net_shortage_count != null) {
      chips.push({ label: 'Net shortage lines', value: s.net_shortage_count, tone: s.net_shortage_count > 0 ? 'danger' : 'ok' });
    }
    if (s.covered_by_forecast != null) {
      chips.push({ label: 'Covered by forecast', value: s.covered_by_forecast, tone: 'ok' });
    }
    if (s.with_forecast_indent != null) {
      chips.push({ label: 'Forecast indents', value: s.with_forecast_indent });
    }
    if (s.with_net_shortage != null) {
      chips.push({ label: 'Net shortage (log)', value: s.with_net_shortage, tone: s.with_net_shortage > 0 ? 'danger' : 'ok' });
    }
    if (s.with_actual_issue != null) {
      chips.push({ label: 'With physical issue', value: s.with_actual_issue });
    }
    if (s.double_pipeline != null) {
      chips.push({ label: 'Double pipeline', value: s.double_pipeline, tone: s.double_pipeline > 0 ? 'warn' : 'ok' });
    }

    return chips;
  }

  lifecycleClass(stage: string): string {
    const s = (stage ?? '').toLowerCase();
    if (s.includes('production') || s.includes('procured') || s.includes('full match')) {
      return 'recon-badge recon-badge-ok';
    }
    if (s.includes('awaiting') || s.includes('shortage') || s.includes('partial')) {
      return 'recon-badge recon-badge-warn';
    }
    if (s.includes('no forecast') || s.includes('no indent') || s.includes('forecast only')) {
      return 'recon-badge recon-badge-danger';
    }
    return 'recon-badge';
  }
}
