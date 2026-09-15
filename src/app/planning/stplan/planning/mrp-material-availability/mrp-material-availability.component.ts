import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  Output,
  SimpleChanges,
} from '@angular/core';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

export interface MrpMaterialAvailabilityDetail {
  status?: string;
  message?: string;
  material_code?: string;
  material_name?: string;
  material_type?: string;
  mother_code?: string;
  uom?: string;
  total_stock_qty?: number;
  available_store_qty?: number;
  reserved_qty?: number;
  under_test_qty?: number;
  open_po_qty?: number;
  transit_po_qty?: number;
  open_indent_qty?: number;
  expected_receipt_qty?: number;
  effective_planning_qty?: number;
  released_booked_qty?: number;
  required_qty?: number | null;
  net_shortage_qty?: number | null;
  commitments?: any[];
  calculation_basis?: { [key: string]: string };
}

@Component({
  selector: 'app-mrp-material-availability',
  templateUrl: './mrp-material-availability.component.html',
  styleUrls: ['./mrp-material-availability.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MrpMaterialAvailabilityComponent implements OnChanges, OnDestroy {
  private readonly destroy$ = new Subject<void>();

  /** When true, modal is open. Parent binds with two-way or openChange. */
  @Input() open = false;
  @Output() openChange = new EventEmitter<boolean>();

  /** Material code to load (required). */
  @Input() materialCode = '';
  /** Optional display fallbacks while loading. */
  @Input() materialName = '';
  @Input() materialType = '';
  @Input() uom = '';
  /** Optional required qty override (else API sums active WO commitments). */
  @Input() requiredQty: number | null = null;

  loading = false;
  errorMsg = '';
  detail: MrpMaterialAvailabilityDetail | null = null;
  showBasis = false;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['open'] && this.open && this.materialCode) {
      this.load();
    }
    if (
      this.open &&
      (changes['materialCode'] || changes['requiredQty']) &&
      this.materialCode
    ) {
      this.load();
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  close(): void {
    this.onOpenChange(false);
  }

  onOpenChange(value: boolean): void {
    this.open = !!value;
    this.openChange.emit(this.open);
    this.cdr.markForCheck();
  }

  toggleBasis(): void {
    this.showBasis = !this.showBasis;
    this.cdr.markForCheck();
  }

  num(v: any): string {
    const n = Number(v ?? 0);
    if (!isFinite(n)) {
      return '0';
    }
    return n === 0 ? '0' : String(Number(n.toFixed(4)));
  }

  private load(): void {
    const code = (this.materialCode || '').trim();
    if (!code) {
      this.errorMsg = 'Material code is required.';
      this.detail = null;
      this.cdr.markForCheck();
      return;
    }
    this.loading = true;
    this.errorMsg = '';
    this.detail = null;
    this.cdr.markForCheck();

    let url =
      'marketing/po.php?type=getMrpMaterialAvailability&material_code=' +
      encodeURIComponent(code);
    if (this.requiredQty != null && this.requiredQty !== undefined) {
      url += '&required_qty=' + encodeURIComponent(String(this.requiredQty));
    }

    this.service
      .get(url)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (resp: any) => {
          this.loading = false;
          if (!resp || resp.status === 'error') {
            this.errorMsg = resp?.message || 'Failed to load material availability.';
            this.detail = null;
          } else {
            this.detail = resp as MrpMaterialAvailabilityDetail;
          }
          this.cdr.markForCheck();
        },
        error: () => {
          this.loading = false;
          this.errorMsg = 'Failed to load material availability.';
          this.detail = null;
          this.cdr.markForCheck();
        },
      });
  }
}
