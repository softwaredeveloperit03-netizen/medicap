import { Component, Input } from '@angular/core';

@Component({
  selector: 'mrp-stock-qty-cols',
  templateUrl: './mrp-stock-qty-cols.component.html',
  styleUrls: ['./mrp-stock-qty-cols.component.css'],
})
export class MrpStockQtyColsComponent {
  @Input() item: any;
  @Input() mode: 'header' | 'row' = 'row';
  @Input() headerStyle = '';

  num(val: any): string {
    const n = Number(val || 0);
    return n === 0 ? '0' : String(n);
  }

  get grossQty(): string {
    if (!this.item) {
      return '—';
    }
    const g = this.item.gross_balance_qty;
    return g !== undefined && g !== null && g !== '' ? String(g) : String(this.item.balance_qty ?? '—');
  }

  get bookedQty(): string {
    return this.num(this.item?.booked_qty);
  }

  get holdQty(): string {
    return this.num(this.item?.hold_qty);
  }

  get netQty(): string {
    return this.item?.net_available_qty ?? this.item?.balance_qty ?? '—';
  }

  get pipelineQty(): string {
    return this.num(this.item?.pipeline_supply_qty);
  }

  get effectiveQty(): string {
    return this.item?.balance_qty ?? '—';
  }

  get netClass(): string {
    return Number((this.item?.net_available_qty ?? this.item?.balance_qty) || 0) <= 0 ? 'light-red' : 'light-green';
  }

  get effectiveClass(): string {
    return Number(this.item?.balance_qty || 0) <= 0 ? 'light-red' : 'light-green';
  }

  get hasDeductions(): boolean {
    return Number(this.item?.booked_qty || 0) > 0 || Number(this.item?.hold_qty || 0) > 0;
  }

  get hasPipeline(): boolean {
    return Number(this.item?.pipeline_supply_qty || 0) > 0;
  }
}
