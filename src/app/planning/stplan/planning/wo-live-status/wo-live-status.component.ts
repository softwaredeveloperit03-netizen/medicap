import { Component, HostListener, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-wo-live-status',
  templateUrl: './wo-live-status.component.html',
  styleUrls: ['./wo-live-status.component.css'],
})
export class WoLiveStatusComponent implements OnInit, OnDestroy {
  loading = false;
  promoting = false;
  searchText = '';
  workOrders: any[] = [];
  workOrdersBackup: any[] = [];
  summary: any = {};
  autoRefresh = true;
  lastRefreshed = '';
  selectedWo: any = null;
  isView = false;
  loadError = '';

  private refreshTimer: any = null;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadWorkOrders();
    this.refreshTimer = setInterval(() => {
      if (this.autoRefresh && !this.promoting && !this.isView) {
        this.loadWorkOrders(true);
      }
    }, 15000);
  }

  ngOnDestroy(): void {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }
  }

  @HostListener('window:focus')
  onWindowFocus(): void {
    if (!this.promoting && !this.isView) {
      this.loadWorkOrders(true);
    }
  }

  loadWorkOrders(silent = false): void {
    if (!silent) {
      this.loading = true;
    }
    this.loadError = '';
    this.service.get('marketing/po.php?type=getCannotPlanWOLiveStatus').subscribe({
      next: (response: any) => {
        if (Array.isArray(response)) {
          this.workOrders = response;
          this.summary = { total: response.length };
        } else {
          this.workOrders = response?.work_orders || [];
          this.summary = response?.summary || {};
        }
        this.workOrdersBackup = [...this.workOrders];
        this.lastRefreshed = this.summary?.checked_at || new Date().toISOString();
        this.applyFilter();
        this.loading = false;
      },
      error: (err) => {
        this.loading = false;
        this.loadError = 'Could not load live status. Ensure updated po.php is deployed on server.';
        console.error(err);
        alertify.error(this.loadError);
      },
    });
  }

  applyFilter(): void {
    const query = this.searchText.toLowerCase().trim();
    if (!query) {
      this.workOrders = [...this.workOrdersBackup];
      return;
    }
    this.workOrders = this.workOrdersBackup.filter((wo) =>
      JSON.stringify(wo).toLowerCase().includes(query)
    );
  }

  trackByIndex(index: number): number {
    return index;
  }

  formatPlanMonth(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const s = String(value).trim();
    const isoMonth = s.match(/^(\d{4})-(\d{2})(?:-\d{2})?/);
    if (isoMonth) {
      const d = new Date(Number(isoMonth[1]), Number(isoMonth[2]) - 1, 1);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
      }
    }
    return s;
  }

  getLiveStatusLabel(wo: any): string {
    return this.isLiveCanPlan(wo) ? 'Can Plan' : 'Cannot Plan';
  }

  isLiveCanPlan(wo: any): boolean {
    return wo?.live_can_plan === true || wo?.live_can_plan === 1 || wo?.live_can_plan === '1';
  }

  canPromote(wo: any): boolean {
    if (!this.isLiveCanPlan(wo)) {
      return false;
    }
    const status = (wo?.stored_status || wo?.status || '').toUpperCase().trim();
    if (status === 'CAN_PLAN' || status === 'CAN_PLAN_MC_QTY_USED') {
      return false;
    }
    return true;
  }

  formatStoredStatus(wo: any): string {
    const status = (wo?.stored_status || wo?.status || 'CANNOT_PLAN').toString();
    if (status.toUpperCase() === 'CANNOT_PLAN') {
      return 'Cannot Plan';
    }
    if (status.toUpperCase() === 'CAN_PLAN') {
      return 'Can Plan';
    }
    if (status.toUpperCase() === 'CAN_PLAN_MC_QTY_USED') {
      return 'Can Plan MC Used';
    }
    return status;
  }

  promoteToCanPlan(wo: any): void {
    if (!this.canPromote(wo)) {
      alertify.error('Stock is still short. Cannot promote until material is received and stock is updated.');
      return;
    }
    if (!confirm(`Promote work order ${wo.workorder_no} to Can Plan and send for production planning?`)) {
      return;
    }
    this.promoting = true;
    this.service
      .post('marketing/po.php?type=promoteCannotPlanToCanPlan', JSON.stringify({ workorder_no: wo.workorder_no }))
      .subscribe({
        next: (response: any) => {
          this.promoting = false;
          if (response.status === 'success') {
            alertify.success(response.message || 'Work order promoted to Can Plan');
            this.loadWorkOrders();
            if (confirm('Open Can Plan Work Order screen for next steps?')) {
              this.router.navigate(['/planning/WoPlanningHub/can-plan']);
            }
          } else {
            alertify.error(response.message || 'Could not promote work order');
          }
        },
        error: () => {
          this.promoting = false;
          alertify.error('Error promoting work order');
        },
      });
  }

  promoteAllReady(): void {
    const ready = this.workOrdersBackup.filter((wo) => this.canPromote(wo));
    if (!ready.length) {
      alertify.error('No work orders are ready to promote (live stock must be sufficient).');
      return;
    }
    if (!confirm(`Promote ${ready.length} work order(s) to Can Plan?`)) {
      return;
    }
    this.promoting = true;
    this.service
      .post(
        'marketing/po.php?type=promoteCannotPlanToCanPlan',
        JSON.stringify({ workorder_nos: ready.map((wo) => wo.workorder_no) })
      )
      .subscribe({
        next: (response: any) => {
          this.promoting = false;
          if (response.status === 'success') {
            alertify.success(response.message || 'Work orders promoted');
            this.loadWorkOrders();
          } else {
            alertify.error(response.message || 'Promotion failed');
          }
        },
        error: () => {
          this.promoting = false;
          alertify.error('Error promoting work orders');
        },
      });
  }

  viewDetails(wo: any): void {
    this.selectedWo = wo;
    this.isView = true;
  }
}
