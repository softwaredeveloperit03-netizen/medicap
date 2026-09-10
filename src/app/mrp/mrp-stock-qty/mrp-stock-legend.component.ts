import { Component } from '@angular/core';

@Component({
  selector: 'mrp-stock-legend',
  template: `
    <div class="mrp-stock-legend">
      <strong>Net balance</strong> = gross stock − booked (WO + MRP) − on hold.
      <strong>Pipeline</strong> = open forecast indent (no PO yet) + in-transit PO (not WO-linked).
      <strong>Effective balance</strong> = net + pipeline; shortage uses effective balance.
      <a routerLink="/mrp/Reconciliation">Open Forecast Reconciliation</a> — start with <strong>Status Report</strong> for forecast vs today; use <strong>Forecast vs Actual Log</strong> for material audit.
    </div>
  `,
  styleUrls: ['./mrp-stock-qty-cols.component.css'],
})
export class MrpStockLegendComponent {}
