import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

export type ExecOpenMode =
  | 'open'
  | 'correction'
  | 'checking'
  | 'review'
  | 'approval'
  | 'final'
  | 'html_record'
  | 'pdf';

@Component({
  selector: 'app-ebmr-batches',
  templateUrl: './batches.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './batches.component.css'],
})
export class BatchesComponent implements OnInit {
  recordType = 'eBMR';
  loading = false;
  rows: any[] = [];
  statusFilter = '';
  search = '';

  /** eBPR manual start only */
  modalOpen = false;
  profiles: any[] = [];
  boundProducts: any[] = [];
  workOrders: any[] = [];
  form: any = {};

  statuses = ['In Progress', 'On Hold', 'Submitted for Approval', 'Approved', 'Rejected', 'Released for Packing', 'Released', 'Cancelled'];

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute, private esign: EsignService) {}

  ngOnInit(): void {
    const routeData = this.route.snapshot.data || {};
    if (routeData['recordType']) {
      this.recordType = routeData['recordType'];
    }
    this.route.queryParams.subscribe((p) => {
      if (p['type']) {
        this.recordType = p['type'];
      } else if (routeData['recordType']) {
        this.recordType = routeData['recordType'];
      } else {
        this.recordType = 'eBMR';
      }
      this.load();
      if (p['work_order_id']) {
        this.handleWorkOrderEntry(+p['work_order_id']);
      }
    });
  }

  private executionBase(): string {
    return (this.route.snapshot.data?.['executionBase'] as string) || '/master/ebmr-bpr/execution';
  }

  private executionQueryParams(mode: ExecOpenMode): Record<string, string> {
    const qp: Record<string, string> = { mode };
    let returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (!returnUrl && this.route.snapshot.data?.['returnUrlDefault']) {
      returnUrl = String(this.route.snapshot.data['returnUrlDefault']);
    }
    if (!returnUrl && this.route.snapshot.data?.['productionExec']) {
      returnUrl = this.isEbmr ? '/fproduction/ebmr/under-production' : '/packing/bpr/start';
    }
    if (returnUrl) {
      qp.returnUrl = returnUrl;
    }
    return qp;
  }

  private goExecution(batchId: number | string, mode: ExecOpenMode, replaceUrl = false): void {
    const base = this.executionBase().replace(/\/$/, '');
    this.router.navigate([base, batchId], {
      queryParams: this.executionQueryParams(mode),
      replaceUrl,
    });
  }

  get isEbmr(): boolean {
    return this.recordType === 'eBMR';
  }

  load(): void {
    if (this.isEbmr) {
      this.loadEbmrLog();
      return;
    }
    this.loadBatchList();
  }

  /** All eBMR execution batches + QA-allotted WOs ready to open (no manual start popup). */
  loadEbmrLog(): void {
    this.loading = true;
    let batchUrl = 'master/ebmr_bpr.php?type=getBatches&record_type=eBMR';
    if (this.statusFilter) {
      batchUrl += '&status=' + encodeURIComponent(this.statusFilter);
    }
    this.service.get(batchUrl).subscribe({
      next: (batches: any) => {
        // Support array or {batches:[...]} / error payloads
        let batchRows: any[] = [];
        if (Array.isArray(batches)) {
          batchRows = batches;
        } else if (batches && Array.isArray(batches.batches)) {
          batchRows = batches.batches;
        } else if (batches && batches.status === 'error') {
          this.loading = false;
          this.rows = [];
          alertify.error(batches.message || 'Failed to load batches');
          return;
        }

        const seenWo = new Set<string>();
        batchRows.forEach((b) => {
          if (b.work_order_id) {
            seenWo.add(String(b.work_order_id));
          }
        });

        // Always show batches first; merge Ready WOs without blocking the list
        this.rows = batchRows.map((b) => ({ ...b, ebmr_batch_id: b.id }));
        this.loading = false;

        this.service.get('master/ebmr_bpr.php?type=getBatchWorkAllocLog').subscribe({
          next: (log: any) => {
            const pending = (Array.isArray(log) ? log : []).filter(
              (r) =>
                r.alloc_status === 'Allocated' &&
                !r.ebmr_batch_id &&
                !seenWo.has(String(r.work_order_id))
            );
            const pendingRows = pending.map((r) => ({
              ...r,
              batch_no: r.batch_number,
              work_order_id: r.work_order_id,
              status: 'Ready',
              steps_total: 0,
              steps_checked: 0,
            }));
            this.rows = [...batchRows.map((b) => ({ ...b, ebmr_batch_id: b.id })), ...pendingRows].sort(
              (a, b) => Number(b.id || b.work_order_id || 0) - Number(a.id || a.work_order_id || 0)
            );
          },
          error: () => {
            /* keep batchRows already shown */
          },
        });
      },
      error: () => {
        this.loading = false;
        this.rows = [];
        alertify.error('Failed to load batch log');
      },
    });
  }

  loadBatchList(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getBatches&record_type=' + this.recordType;
    if (this.statusFilter) url += '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load batches');
      },
    });
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) =>
      [r.batch_no, r.batch_number, r.product_code, r.product_name, r.profile_code, r.plan_no, r.work_order_no].join(' ').toLowerCase().includes(q)
    );
  }

  progressPct(r: any): number {
    if (!r.steps_total) return 0;
    return Math.round((r.steps_checked / r.steps_total) * 100);
  }

  statusClass(s: string): string {
    const v = (s || '').toLowerCase();
    if (v === 'approved' || v === 'released') return 'eb-badge-ok';
    if (v === 'rejected') return 'eb-badge-crit';
    if (v === 'submitted for approval') return 'eb-badge-type';
    return 'eb-badge-warn';
  }

  execStatusLabel(r: any): string {
    return r.status || r.ebmr_exec_status || (r.ebmr_batch_id ? 'In Progress' : 'Ready');
  }

  handleWorkOrderEntry(workOrderId: number): void {
    this.service.get('master/ebmr_bpr.php?type=getWorkOrderForEbmr&record_type=eBMR&work_order_id=' + workOrderId).subscribe((r: any) => {
      if (!r || r.status !== 'success' || !r.work_order) {
        alertify.error((r && r.message) || 'Work order not found');
        return;
      }
      const wo = r.work_order;
      if (wo.ebmr_batch_id && wo.ebmr_exec_status !== 'Released') {
        this.goExecution(wo.ebmr_batch_id, 'open', true);
        return;
      }
      this.autoStartFromWorkOrder(workOrderId, 'open', true);
    });
  }

  /** eBPR only — manual batch start */
  newBatch(): void {
    this.form = {
      profile_id: '',
      work_order_id: '',
      batch_no: '',
      product_code: '',
      product_name: '',
      batch_size: '',
      batch_size_uom: '',
    };
    this.boundProducts = [];
    this.service.get('master/ebmr_bpr.php?type=getProfiles&record_type=' + this.recordType).subscribe((r: any) => {
      this.profiles = (Array.isArray(r) ? r : []).filter((p) => p.status === 'Approved');
      if (!this.profiles.length) {
        alertify.message('No approved ' + this.recordType + ' profiles. Approve a profile in the builder first.');
      }
    });
    this.service.get('master/ebmr_bpr.php?type=getWorkOrdersForEbmr&record_type=' + this.recordType).subscribe((r: any) => {
      this.workOrders = Array.isArray(r) ? r : [];
    });
    this.modalOpen = true;
  }

  onWorkOrderChange(): void {
    const wo = this.workOrders.find((x) => String(x.id) === String(this.form.work_order_id));
    if (!wo) return;
    this.form.batch_no = wo.batch_number || '';
    this.form.product_code = wo.product_code || '';
    this.form.product_name = wo.product_name || '';
    this.form.batch_size = wo.batch_size || '';
    this.form.batch_size_uom = wo.pack_unit || '';
    if (wo.suggested_profile_id) {
      this.form.profile_id = String(wo.suggested_profile_id);
      this.onProfileChange();
    }
  }

  onProfileChange(): void {
    const p = this.profiles.find((x) => String(x.id) === String(this.form.profile_id));
    this.boundProducts = [];
    if (!p) return;
    this.service.get('master/ebmr_bpr.php?type=getProfile&id=' + p.id).subscribe((r: any) => {
      if (r && r.status === 'success') {
        this.boundProducts = r.profile.products || [];
        const h = r.profile.header || {};
        if (h.batch_size && !this.form.batch_size) this.form.batch_size = h.batch_size;
        if (h.batch_size_uom && !this.form.batch_size_uom) this.form.batch_size_uom = h.batch_size_uom;
      }
    });
  }

  pickProduct(): void {
    const p = this.boundProducts.find((x) => x.product_code === this.form.product_code);
    if (p) this.form.product_name = p.product_name;
  }

  startBatch(): void {
    if (!this.form.profile_id || !this.form.batch_no) {
      alertify.error('Select a profile and enter a batch number');
      return;
    }
    this.esign
      .request({ meaning: 'Prepared By', module: 'execution', detail: 'Start batch: ' + (this.form.batch_no || ''), confirmLabel: 'Sign & Start' })
      .then((sig) => {
        if (!sig) return;
        const payload = { ...this.form };
        if (!payload.work_order_id) delete payload.work_order_id;
        this.service.post('master/ebmr_bpr.php?type=startBatch', JSON.stringify(payload)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Batch started');
            this.modalOpen = false;
            this.goExecution(r.id, 'open');
          } else {
            alertify.error((r && r.message) || 'Failed to start batch');
          }
        });
      });
  }

  openBatch(r: any, mode: ExecOpenMode = 'open'): void {
    // Prefer ebmr_batch_id — alloc-log rows may have a different numeric `id`
    const batchId = r.ebmr_batch_id || (r.status && r.status !== 'Ready' ? r.id : null);
    if (batchId) {
      this.goExecution(batchId, mode);
      return;
    }
    if (this.isEbmr && r.work_order_id) {
      this.autoStartFromWorkOrder(r.work_order_id, mode);
      return;
    }
    if (r.id && !r.work_order_id) {
      this.goExecution(r.id, mode);
      return;
    }
    alertify.error('Batch not started yet');
  }

  autoStartFromWorkOrder(workOrderId: number, mode: ExecOpenMode, replaceUrl = false): void {
    this.service.get('master/ebmr_bpr.php?type=getWorkOrderForEbmr&record_type=eBMR&work_order_id=' + workOrderId).subscribe((r: any) => {
      if (!r || r.status !== 'success' || !r.work_order) {
        alertify.error((r && r.message) || 'Work order not found');
        return;
      }
      const wo = r.work_order;
      if (wo.ebmr_batch_id) {
        this.goExecution(wo.ebmr_batch_id, mode, replaceUrl);
        return;
      }
      const profileId = wo.suggested_profile_id;
      if (!profileId) {
        alertify.error('No approved eBMR profile mapped to this product. Map product in eBMR Master first.');
        return;
      }
      this.esign
        .request({
          meaning: 'Prepared By',
          module: 'execution',
          recordRef: String(workOrderId),
          detail: 'Open eBMR execution: ' + (wo.batch_number || ''),
          confirmLabel: 'Sign & Open',
        })
        .then((sig) => {
          if (!sig) return;
          const payload = {
            profile_id: profileId,
            work_order_id: workOrderId,
            batch_no: wo.batch_number,
            product_code: wo.product_code,
            product_name: wo.product_name,
            batch_size: wo.batch_size,
            batch_size_uom: wo.pack_unit,
          };
          this.service.post('master/ebmr_bpr.php?type=startBatch', JSON.stringify(payload)).subscribe((res: any) => {
            if (res && res.status === 'success') {
              alertify.success('eBMR opened for batch ' + (wo.batch_number || ''));
              this.load();
              this.goExecution(res.id, mode, replaceUrl);
            } else {
              alertify.error((res && res.message) || 'Failed to open eBMR');
            }
          });
        });
    });
  }

  close(): void {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) {
      this.router.navigateByUrl(returnUrl);
      return;
    }
    const closeRoute = this.route.snapshot.data?.['closeRoute'];
    if (closeRoute) {
      this.router.navigate([closeRoute]);
      return;
    }
    this.router.navigate([this.recordType === 'eBPR' ? '/fproduction/ebmr' : '/fproduction/ebmr']);
  }
}
