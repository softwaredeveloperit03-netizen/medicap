import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface WorkOrder {
  workorder_no: string;
  order_no?: string;
  status?: string;
  [key: string]: any;
}

interface MonthGroup {
  key: string;
  label: string;
  workOrders: WorkOrder[];
}

interface ForecastGroup {
  order_no: string;
  groupKey?: string;
  collapsed: boolean;
  workOrders: WorkOrder[];
  monthGroups: MonthGroup[];
  planMonth?: string;
  billing_type: string;
  mainGroupName?: string;
  productSummary: string;
  bg_color?: string;
  toBePlanCount: number;
  toBeNotPlanCount: number;
  totalCount: number;
  hasCombiChild?: boolean;
}

@Component({
  selector: 'app-generatewolog',
  templateUrl: './generatewolog.component.html',
  styleUrls: ['./generatewolog.component.css']
})
export class GeneratewologComponent implements OnInit {
  loading = false;
  searchText = '';
  Worders: WorkOrder[] = [];
  pendingpoBackup: WorkOrder[] = [];
  forecastGroups: ForecastGroup[] = [];
  private collapsedForecastKeys = new Set<string>();

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingWOs();
  }

  applyFilter() {
    const query = (this.searchText || '').toLowerCase();
    this.Worders = this.pendingpoBackup.filter((wo) =>
      JSON.stringify(wo).toLowerCase().includes(query)
    );
    this.buildForecastGroups();
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
    const isoMatch = String(value).match(/^(\d{4})-(\d{2})/);
    if (isoMatch) {
      return `${isoMatch[2]}-${isoMatch[1]}`;
    }
    return String(value);
  }

  planMonthLabel(value: any): string {
    const formatted = this.formatPlanMonth(value);
    const m = formatted.match(/^(\d{2})-(\d{4})$/);
    if (m) {
      const names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const idx = parseInt(m[1], 10) - 1;
      if (idx >= 0 && idx < 12) {
        return `${names[idx]} ${m[2]}`;
      }
    }
    return formatted;
  }

  formatBillingType(wo: any): string {
    const raw = wo?.billing_type ?? wo?.billingType ?? '';
    if (raw && String(raw).trim() && raw !== 'null') {
      return String(raw).trim();
    }
    const orderNo = String(wo?.order_no || '').toUpperCase();
    if (orderNo.startsWith('FO')) {
      return 'Forecast';
    }
    if (orderNo.startsWith('PO')) {
      return 'PO';
    }
    return '-';
  }

  isCombiChild(row: any): boolean {
    return !!row?.is_combi_child;
  }

  processedByDisplay(wo: any): string {
    return (
      wo?.send_for_analysis_by_name ||
      wo?.snef_for_planning_by_name ||
      wo?.send_for_analysis_by ||
      wo?.snef_for_planning_by ||
      '-'
    ).toString().trim() || '-';
  }

  processedOnDisplay(wo: any): any {
    return wo?.send_for_analysis_date || wo?.snef_for_planning_on || null;
  }

  /** Status saved when user approved / sent from Generate WO (do not recalculate stock here). */
  resolvePlanningStatus(wo: any): string {
    const raw = String(wo?.status || '').trim();
    if (raw === 'CAN_PLAN' || raw === 'CANNOT_PLAN' || raw === 'CAN_PLAN_MC_QTY_USED') {
      return raw;
    }
    if (/cannot|short|not plan/i.test(raw)) {
      return 'CANNOT_PLAN';
    }
    if (/can.?plan|to be plan/i.test(raw)) {
      return 'CAN_PLAN';
    }
    return raw || '-';
  }

  getPlanningStatusLabel(status?: string): string {
    const key = this.resolvePlanningStatus({ status });
    const labels: Record<string, string> = {
      CAN_PLAN: 'To Be Plan',
      CANNOT_PLAN: 'To Be Not Plan',
      CAN_PLAN_MC_QTY_USED: 'To Be Plan (MC Used)',
    };
    return labels[key] || (key === '-' ? '-' : String(key).replace(/_/g, ' '));
  }

  getPlanningStatusBadgeClass(status?: string): string {
    const key = this.resolvePlanningStatus({ status });
    if (key === 'CANNOT_PLAN') {
      return 'badge-danger';
    }
    if (key === 'CAN_PLAN_MC_QTY_USED') {
      return 'badge-warning';
    }
    if (key === 'CAN_PLAN') {
      return 'badge-success';
    }
    return 'badge-secondary';
  }

  getPlanningStatusTooltip(status?: string): string {
    const key = this.resolvePlanningStatus({ status });
    if (key === 'CAN_PLAN_MC_QTY_USED') {
      return 'Marked to be plan — MC stock was used at approval';
    }
    if (key === 'CANNOT_PLAN') {
      return 'Marked to be not plan — sent for requirement analysis';
    }
    if (key === 'CAN_PLAN') {
      return 'Marked to be plan — approved from Generate WO';
    }
    return '';
  }

  getForwardActionLabel(wo: any): string {
    const key = this.resolvePlanningStatus(wo);
    if (key === 'CANNOT_PLAN') {
      return 'Sent for Requirement Analysis';
    }
    if (key === 'CAN_PLAN' || key === 'CAN_PLAN_MC_QTY_USED') {
      return 'Approved To Be Plan';
    }
    return wo?.status || '-';
  }

  buildForecastGroups(): void {
    const map = new Map<string, WorkOrder[]>();
    for (const wo of this.Worders || []) {
      const key = (
        wo['forecast_group_key'] ||
        wo['Fo_code'] ||
        wo['doc_no'] ||
        wo.order_no ||
        'UNKNOWN'
      ).toString();
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key)!.push(wo);
    }

    this.forecastGroups = Array.from(map.entries())
      .sort((a, b) => a[0].localeCompare(b[0], undefined, { numeric: true, sensitivity: 'base' }))
      .map(([order_no, workOrders]) => {
        const first = workOrders[0];
        const productCodes = [...new Set(
          workOrders.map((w) => (w['product_code'] || '').toString()).filter((c) => c && c !== '-')
        )];
        const childOrders = [...new Set(
          workOrders.map((w) => (w.order_no || '').toString()).filter(Boolean)
        )];
        return {
          order_no: childOrders.length === 1 ? childOrders[0] : order_no,
          groupKey: order_no,
          collapsed: this.collapsedForecastKeys.has(order_no),
          workOrders,
          planMonth: first['planMonth'],
          billing_type: this.formatBillingType(first),
          mainGroupName: first['mainGroupName'],
          productSummary: productCodes.join(', '),
          bg_color: first['bg_color'],
          monthGroups: this.buildMonthGroups(workOrders),
          toBePlanCount: workOrders.filter((w) => {
            const s = this.resolvePlanningStatus(w);
            return s === 'CAN_PLAN' || s === 'CAN_PLAN_MC_QTY_USED';
          }).length,
          toBeNotPlanCount: workOrders.filter((w) => this.resolvePlanningStatus(w) === 'CANNOT_PLAN').length,
          totalCount: workOrders.length,
          hasCombiChild: workOrders.some((w) => !!w['is_combi_child']),
        };
      });
  }

  private buildMonthGroups(workOrders: WorkOrder[]): MonthGroup[] {
    const map = new Map<string, WorkOrder[]>();
    for (const wo of workOrders || []) {
      const key = this.formatPlanMonth(wo['split_plan_month'] || wo['planMonth']);
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key)!.push(wo);
    }
    return Array.from(map.entries())
      .sort((a, b) => this.monthSortValue(a[0]) - this.monthSortValue(b[0]))
      .map(([key, wos]) => ({
        key,
        label: this.planMonthLabel(wos[0]['split_plan_month'] || wos[0]['planMonth']),
        workOrders: wos,
      }));
  }

  private monthSortValue(key: string): number {
    const m = (key || '').match(/^(\d{2})-(\d{4})$/);
    if (m) {
      return parseInt(m[2], 10) * 100 + parseInt(m[1], 10);
    }
    return Number.MAX_SAFE_INTEGER;
  }

  toggleForecastCollapse(group: ForecastGroup): void {
    group.collapsed = !group.collapsed;
    const key = group.groupKey || group.order_no;
    if (group.collapsed) {
      this.collapsedForecastKeys.add(key);
    } else {
      this.collapsedForecastKeys.delete(key);
    }
  }

  getPendingWOs() {
    this.loading = true;
    this.service.get('marketing/po.php?type=Get_Processed_Generated_wo_log')
      .subscribe((res: any) => {
        // Log shows status saved at Approve / Send for Analysis — do not re-run stock check.
        this.pendingpoBackup = Array.isArray(res) ? res : [];
        this.Worders = [...this.pendingpoBackup];
        this.buildForecastGroups();
        this.loading = false;
      }, () => {
        this.loading = false;
      });
  }
}
