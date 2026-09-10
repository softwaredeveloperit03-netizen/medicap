import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  OnDestroy,
  OnInit,
} from '@angular/core';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-wowise-cancellation-log',
  templateUrl: './wowise-cancellation-log.component.html',
  styleUrls: ['./wowise-cancellation-log.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class WowiseCancellationLogComponent implements OnInit, OnDestroy {
  private destroy$ = new Subject<void>();

  loading = false;
  searchText = '';
  rows: any[] = [];
  private backup: any[] = [];

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.loadLog();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadLog(): void {
    this.loading = true;
    this.cdr.markForCheck();
    this.service
      .get('marketing/po.php?type=getPlanningCancellationLog')
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          this.backup = Array.isArray(response) ? response : [];
          this.applyFilter();
          this.loading = false;
          this.cdr.markForCheck();
        },
        error: () => {
          this.backup = [];
          this.rows = [];
          this.loading = false;
          this.cdr.markForCheck();
        },
      });
  }

  applyFilter(): void {
    const query = (this.searchText || '').toLowerCase().trim();
    if (!query) {
      this.rows = [...this.backup];
    } else {
      this.rows = this.backup.filter((row) =>
        JSON.stringify(row || {}).toLowerCase().includes(query)
      );
    }
    this.cdr.markForCheck();
  }

  trackByRow(_index: number, row: any): string {
    return String(row?.id ?? _index);
  }
}
