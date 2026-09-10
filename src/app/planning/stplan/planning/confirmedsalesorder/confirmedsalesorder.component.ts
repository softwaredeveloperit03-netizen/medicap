import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-confirmedsalesorder',
  templateUrl: './confirmedsalesorder.component.html',
  styleUrls: ['./confirmedsalesorder.component.css']
})
export class ConfirmedsalesorderComponent implements OnInit {
  orders: any[] = [];
  ordersBackup: any[] = [];
  loading = false;
  searchText = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getConfirmedSalesOrders();
  }

  getConfirmedSalesOrders(): void {
    this.loading = true;
    this.service.get('marketing/po.php?type=getConfirmedSalesOrders').subscribe(
      (response: any) => {
        this.ordersBackup = Array.isArray(response) ? response : [];
        this.orders = [...this.ordersBackup];
        this.applyFilter();
        this.loading = false;
      },
      () => {
        this.ordersBackup = [];
        this.orders = [];
        this.loading = false;
      }
    );
  }

  applyFilter(): void {
    const query = (this.searchText || '').toLowerCase();
    this.orders = this.ordersBackup.filter((o) =>
      JSON.stringify(o).toLowerCase().includes(query)
    );
  }

  /** Sales order quantity for the confirmed order line. */
  salesQty(row: any): number {
    return Number(row?.salesOrderQty ?? row?.planQty ?? row?.plan_qty ?? 0) || 0;
  }

  /** Quantity already under forecast planning for the same group + product + month. */
  planningQty(row: any): number {
    const covered = Math.min(Number(row?.forecastQty ?? 0) || 0, this.salesQty(row));
    return covered > 0 ? covered : 0;
  }

  /** Balance to produce = sales order qty above the covered forecast qty. */
  balanceQty(row: any): number {
    const balance = this.salesQty(row) - this.planningQty(row);
    return balance > 0 ? balance : 0;
  }

  /** True when at least part of the sales order is covered by an existing forecast. */
  isUnderForecast(row: any): boolean {
    return this.planningQty(row) > 0;
  }

  formatPlanMonth(value: any): string {
    if (!value) {
      return '-';
    }
    if (typeof value === 'string' && /^\d{2}-\d{4}$/.test(value)) {
      return value;
    }
    const parsed = new Date(value);
    if (!isNaN(parsed.getTime())) {
      const month = String(parsed.getMonth() + 1).padStart(2, '0');
      return `${month}-${parsed.getFullYear()}`;
    }
    return String(value);
  }

  /** Mark the covered (under-forecast) portion of the sales order as proceeded for billing. */
  proceedForBilling(row: any): void {
    if (!this.isUnderForecast(row)) {
      alert('This sales order is not under any forecast for the month. Please send it to Receive PO/FO.');
      return;
    }
    if (!confirm('Proceed the forecast-covered quantity of this sales order for billing?')) {
      return;
    }
    const payload = {
      order_material_id: row?.order_material_id ?? row?.id,
      order_no: row?.order_no,
      forecast_no: row?.forecastNo,
      billed_qty: this.planningQty(row),
    };
    this.service
      .post('marketing/po.php?type=confirmSalesOrderBilling', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alert('Sales order proceeded for billing successfully.');
          this.getConfirmedSalesOrders();
        } else {
          alert('An error has occurred, please try again');
        }
      });
  }

  /** Send the balance quantity (sales qty over forecast) to Receive PO/FO for fresh planning. */
  sendBalanceToReceive(row: any): void {
    const balance = this.balanceQty(row);
    if (balance <= 0) {
      alert('No balance quantity to send.');
      return;
    }
    if (!confirm(`Send balance quantity ${balance} to Receive PO/FO for planning?`)) {
      return;
    }
    const payload = {
      order_material_id: row?.order_material_id ?? row?.id,
      order_no: row?.order_no,
      balance_qty: balance,
    };
    this.service
      .post('marketing/po.php?type=sendSalesOrderBalanceToReceive', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alert('Balance quantity sent to Receive PO/FO successfully.');
          this.getConfirmedSalesOrders();
        } else {
          alert('An error has occurred, please try again');
        }
      });
  }
}
