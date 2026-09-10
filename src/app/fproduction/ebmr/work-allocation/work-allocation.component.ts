import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

type WaTab = 'allocation' | 'change' | 'log';

@Component({
  selector: 'app-fprod-ebmr-work-allocation',
  templateUrl: './work-allocation.component.html',
  styleUrls: ['../../../master/ebmr-bpr/ebmr-bpr.theme.css', './work-allocation.component.css'],
})
export class FprodEbmrWorkAllocationComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  search = '';
  statusFilter = '';
  activeTab: WaTab = 'allocation';

  detailOpen = false;
  /** When true, save requires change reason (Change Allocation tab / re-edit). */
  changeMode = false;
  changeReason = '';
  saving = false;
  selected: any = null;
  stages: any[] = [];
  personnel: any[] = [];

  historyRows: any[] = [];
  historyLoading = false;
  historySearch = '';
  historyDetail: any = null;

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.loadPersonnel();
    this.load();
  }

  setTab(tab: WaTab): void {
    this.activeTab = tab;
    this.closeDetail();
    this.historyDetail = null;
    if (tab === 'log') {
      this.loadHistory();
    } else {
      this.load();
    }
  }

  loadPersonnel(): void {
    this.service.get('master/ebmr_bpr.php?type=getProductionPersonnel&department=Production').subscribe({
      next: (r: any) => (this.personnel = Array.isArray(r) ? r : []),
      error: () => (this.personnel = []),
    });
  }

  load(): void {
    this.loading = true;
    this.service.get('master/ebmr_bpr.php?type=getBatchWorkAllocLog').subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load work allocation list');
      },
    });
  }

  loadHistory(): void {
    this.historyLoading = true;
    this.service.get('master/ebmr_bpr.php?type=getBatchWorkAllocHistory').subscribe({
      next: (r: any) => {
        this.historyRows = Array.isArray(r) ? r : [];
        this.historyLoading = false;
      },
      error: () => {
        this.historyLoading = false;
        alertify.error('Failed to load allocation log');
      },
    });
  }

  get filtered(): any[] {
    let list = this.rows;
    if (this.activeTab === 'allocation') {
      list = list.filter((r) => (r.alloc_status || 'Pending') !== 'Allocated');
    } else if (this.activeTab === 'change') {
      list = list.filter((r) => (r.alloc_status || '') === 'Allocated');
    }
    if (this.statusFilter) {
      list = list.filter((r) => (r.alloc_status || 'Pending') === this.statusFilter);
    }
    const q = this.search.trim().toLowerCase();
    if (!q) return list;
    return list.filter((r) =>
      [r.batch_number, r.work_order_no, r.plan_no, r.product_code, r.product_name, r.profile_code]
        .join(' ')
        .toLowerCase()
        .includes(q)
    );
  }

  get filteredHistory(): any[] {
    const q = this.historySearch.trim().toLowerCase();
    if (!q) return this.historyRows;
    return this.historyRows.filter((r) =>
      [r.batch_number, r.work_order_id, r.action, r.changed_by, r.changed_by_name, r.change_reason]
        .join(' ')
        .toLowerCase()
        .includes(q)
    );
  }

  statusClass(s: string): string {
    const v = (s || 'Pending').toLowerCase();
    if (v === 'allocated') return 'eb-badge-ok';
    if (v === 'pending') return 'eb-badge-warn';
    return 'eb-badge-muted';
  }

  actionClass(a: string): string {
    return (a || '').toLowerCase() === 'change' ? 'eb-badge-type' : 'eb-badge-ok';
  }

  openAllocate(row: any, asChange = false): void {
    const woId = row.work_order_id;
    if (!woId) return;
    this.changeMode = asChange || (row.alloc_status || '') === 'Allocated';
    this.changeReason = '';
    this.loading = true;
    const afterInit = () => {
      this.service.get('master/ebmr_bpr.php?type=getBatchWorkAllocDetail&work_order_id=' + woId).subscribe({
        next: (res: any) => {
          this.loading = false;
          if (!res || res.status !== 'success') {
            alertify.error((res && res.message) || 'Could not load allocation detail');
            return;
          }
          this.selected = { ...row, ...(res.work_order || {}), header: res.header };
          this.stages = (res.stages || []).map((s: any) => ({ ...s }));
          if (!this.stages.length) {
            alertify.warning('No stages found. Ensure an approved eBMR profile is mapped to this product.');
          }
          this.detailOpen = true;
        },
        error: () => {
          this.loading = false;
          alertify.error('Failed to load allocation detail');
        },
      });
    };

    if (!row.header_id) {
      this.service.post('master/ebmr_bpr.php?type=initBatchWorkAlloc', JSON.stringify({ work_order_id: woId })).subscribe({
        next: (r: any) => {
          if (r && r.status === 'success') {
            afterInit();
          } else {
            this.loading = false;
            alertify.error((r && r.message) || 'Could not initialize work allocation');
          }
        },
        error: () => {
          this.loading = false;
          alertify.error('Could not initialize work allocation');
        },
      });
    } else {
      afterInit();
    }
  }

  empName(empId: string): string {
    const p = this.personnel.find((x) => String(x.emp_id) === String(empId));
    return p ? p.display_name || (p.firstname + ' ' + (p.lastname || '')).trim() : '';
  }

  onEmpPick(
    stage: any,
    field: 'operator' | 'office' | 'alt_operator' | 'alt_officer' | 'reviewer' | 'approver',
    empId: string
  ): void {
    const name = this.empName(empId);
    if (field === 'operator') {
      stage.operator_emp = empId;
      stage.operator_name = name;
    } else if (field === 'office') {
      stage.office_emp = empId;
      stage.office_name = name;
    } else if (field === 'alt_operator') {
      stage.alt_operator_emp = empId;
      stage.alt_operator_name = name;
    } else if (field === 'alt_officer') {
      stage.alt_officer_emp = empId;
      stage.alt_officer_name = name;
    } else if (field === 'reviewer') {
      stage.reviewer_emp = empId;
      stage.reviewer_name = name;
    } else if (field === 'approver') {
      stage.approver_emp = empId;
      stage.approver_name = name;
    }
  }

  saveAllocate(): void {
    if (!this.selected?.work_order_id) return;
    if (!this.stages.length) {
      alertify.error('No stages to allocate');
      return;
    }
    const missing = this.stages.some((s) => !s.operator_emp || !s.office_emp);
    if (missing) {
      alertify.error('Assign Operator and Officer for every stage');
      return;
    }
    if (this.changeMode && !this.changeReason.trim()) {
      alertify.error('Enter a reason for change of allocation');
      return;
    }
    const action = this.changeMode ? 'Change' : 'Allocate';
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'production:work_allocation',
        recordRef: this.selected.work_order_id,
        detail: (this.changeMode ? 'Change work allocation — ' : 'Batch work allocation — ') + (this.selected.batch_number || ''),
        requireReason: this.changeMode,
        reasonLabel: 'Change reason',
        confirmLabel: this.changeMode ? 'Sign & Change Allocation' : 'Sign & Allocate',
      })
      .then((sig) => {
        if (!sig) return;
        this.saving = true;
        const reason = this.changeMode ? this.changeReason.trim() || sig.reason || '' : '';
        this.service
          .post(
            'master/ebmr_bpr.php?type=saveBatchWorkAlloc',
            JSON.stringify({
              work_order_id: this.selected.work_order_id,
              stages: this.stages,
              action,
              change_reason: reason,
            })
          )
          .subscribe({
            next: (r: any) => {
              this.saving = false;
              if (r && r.status === 'success') {
                alertify.success(this.changeMode ? 'Allocation changed and logged' : 'Work allocation completed');
                this.detailOpen = false;
                this.load();
                if (this.activeTab === 'log') this.loadHistory();
              } else {
                alertify.error((r && r.message) || 'Save failed');
              }
            },
            error: () => {
              this.saving = false;
              alertify.error('Save failed');
            },
          });
      });
  }

  openHistoryDetail(row: any): void {
    this.historyDetail = row;
  }

  closeHistoryDetail(): void {
    this.historyDetail = null;
  }

  stageSummary(stages: any[] | undefined): string {
    if (!Array.isArray(stages) || !stages.length) return '—';
    return stages
      .map((s) => {
        const op = s.operator_name || s.operator_emp || '—';
        const of = s.office_name || s.office_emp || '—';
        return `${s.stage_seq}. ${s.stage_name}: Op ${op} / Off ${of}`;
      })
      .join(' · ');
  }

  goExecute(row: any): void {
    if (row.ebmr_batch_id) {
      this.router.navigate(['/fproduction/ebmr/execution', row.ebmr_batch_id], {
        queryParams: { mode: 'open', returnUrl: '/fproduction/ebmr/under-production' },
      });
      return;
    }
    this.router.navigate(['/fproduction/ebmr/under-production'], {
      queryParams: { work_order_id: row.work_order_id, returnUrl: '/fproduction/ebmr' },
    });
  }

  closeDetail(): void {
    this.detailOpen = false;
    this.selected = null;
    this.stages = [];
    this.changeReason = '';
    this.changeMode = false;
  }

  close(): void {
    this.router.navigate(['/fproduction/ebmr'], { queryParams: { returnUrl: '/fproduction' } });
  }
}
