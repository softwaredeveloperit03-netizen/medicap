import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { of, forkJoin } from 'rxjs';
import { catchError, filter, map, switchMap } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { QaFormMetaService } from 'src/app/qa/shared/qa-form-meta.service';
import { MethodWorkflowService } from 'src/app/qc/moa/methods/shared/method-workflow.service';
declare let alertify: any;

type MethodStatusKey = 'pending' | 'added' | 'completed';
type DetailMode = 'view' | 'proceed' | 'edit';
type MoaListView = 'pending' | 'log' | 'checking' | 'approval' | 'correction';
/** Test Specific = Test Master data; Specification Specific = Zuma spec MOA */
type MoaScope = 'test' | 'specification';
const MOA_SCOPE_KEY = 'moa_stp_scope';

@Component({
  selector: 'app-moa-stp-table',
  templateUrl: './table.component.html',
  styleUrls: [
    './table.component.css',
    '../../../qc/specifications/shared/spec-workflow.css',
    '../../../qc/moa/methods/shared/methods-shared.css',
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TableComponent implements OnInit {
  isView = false;
  detailMode: DetailMode = 'proceed';
  isLoadingList = false;
  isLoadingTests = false;
  isLoadingMethodView = false;
  specifications: any[] = [];

  selectedSpec: Record<string, unknown> = {};
  selectedActionRow: Record<string, unknown> | null = null;
  specTests: any[] = [];
  revision_history: any[] = [];

  // ---- MOA common (document-level) sections ----
  // Shared across every test method of the specification (General Instructions,
  // Purpose, Standard/Test Solutions, Scope, Associate/Reference Documents,
  // Definitions). Edited from this page, stored per specification_no + plant_id.
  showCommonPanel = false;
  commonSections: any = {};
  commonControl: any = {};
  methodDocuments: any[] = [];
  selectedCommonDoc: any = null;
  commonTerm = '';
  commonDef = '';
  commonShow: Record<string, boolean> = {
    gi: false, purpose: false, std: false, test: false, scope: false, assoc: false, ref: false, def: false,
  };
  commonEdit: Record<string, boolean> = {
    purpose: false, std: false, test: false, scope: false,
  };

  specSearch = '';
  specPageSize = 10;
  specCurrentPage = 1;
  /** pending = Proceed / Pending specs; log = completed MOA log; checking/approval/correction = method workflow */
  listView: MoaListView = 'pending';
  /** Scope tabs only — rest of UI stays Zuma-referenced */
  moaScope: MoaScope = 'specification';
  filteredSpecifications: any[] = [];
  paginatedSpecifications: any[] = [];

  /** Test Specific — Test Master rows */
  testMasterRows: any[] = [];
  filteredTestMaster: any[] = [];
  paginatedTestMaster: any[] = [];

  workflowRows: any[] = [];
  filteredWorkflow: any[] = [];
  isLoadingWorkflow = false;

  /** Checking / Approval — complete method document review */
  isSpecWorkflowView = false;
  workflowRow: Record<string, unknown> | null = null;
  workflowRemark = '';
  workflowSubmitting = false;
  workflowReviewStage: 'checking' | 'approval' | 'correction' | 'log' = 'checking';
  testLineRemark = '';
  testLineRejectId: number | null = null;

  testSearch = '';
  testPageSize = 10;
  testCurrentPage = 1;
  filteredTests: any[] = [];
  paginatedTests: any[] = [];

  isMethodView = false;
  selectedMethodTest: Record<string, unknown> | null = null;

  // ---- Complete Method of Analysis document view ----
  isCompleteView = false;
  isLoadingComplete = false;
  completeSubmitting = false;
  completeReadOnly = false;
  completeDocLoadFailed = false;
  completeDoc: any = { specification: {}, common: {}, tests: [] };

  selectedrivision: any = {};
  doc_name = '';
  method_no = '';
  isRivisionrequest = false;
  revision_type = '';

  showRevisionModal = false;
  revisionForm: Record<string, string> = {
    revision_reason: '',
    proposed_changes: '',
    effective_date: '',
  };

  showCcModal = false;
  isCcSaving = false;
  ccInit: any = {};
  ccAffDocs: any[] = [];
  exisDoc: File | null = null;
  changeDetDoc: File | null = null;
  justChangeDoc: File | null = null;
  activeRevisionRequestId = 0;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef,
    private wf: MethodWorkflowService,
    private metaService: QaFormMetaService
  ) {}

  ngOnInit(): void {
    const path = (this.route.snapshot.routeConfig?.path || '').toLowerCase();
    const q = String(this.route.snapshot.queryParamMap.get('view') || '').toLowerCase();
    const fromPath: Record<string, MoaListView> = {
      'new-moa-stp': 'pending',
      checking: 'checking',
      approval: 'approval',
      log: 'log',
      correction: 'correction',
    };
    const initial = (['pending', 'log', 'checking', 'approval', 'correction'].includes(q)
      ? (q as MoaListView)
      : fromPath[path]) || 'pending';

    // Prefer URL ?scope= over session; keep in sync when query changes.
    const scopeQ0 = String(this.route.snapshot.queryParamMap.get('scope') || '').toLowerCase();
    const savedScope0 = (sessionStorage.getItem(MOA_SCOPE_KEY) || '').toLowerCase();
    this.moaScope =
      scopeQ0 === 'test' || scopeQ0 === 'specification'
        ? (scopeQ0 as MoaScope)
        : savedScope0 === 'test'
        ? 'test'
        : 'specification';
    sessionStorage.setItem(MOA_SCOPE_KEY, this.moaScope);

    this.route.queryParamMap.subscribe((params) => {
      const scopeQ = String(params.get('scope') || '').toLowerCase();
      if (scopeQ !== 'test' && scopeQ !== 'specification') {
        return;
      }
      if (this.moaScope === scopeQ) {
        return;
      }
      this.moaScope = scopeQ as MoaScope;
      sessionStorage.setItem(MOA_SCOPE_KEY, this.moaScope);
      this.reloadListForScope();
      this.cdr.markForCheck();
    });

    this.router.events.pipe(filter((e) => e instanceof NavigationEnd)).subscribe(() => {
      const specNo = String(this.selectedSpec?.['specification_no'] || '').trim();
      if (this.isView && specNo) {
        this.loadSpecTests(specNo);
      }
    });

    setTimeout(() => {
      this.listView = 'pending';
      if (initial === 'pending' || initial === 'log') {
        this.listView = initial;
        this.reloadListForScope();
      } else {
        this.setListView(initial);
      }
    });
  }

  setMoaScope(scope: MoaScope): void {
    this.moaScope = scope;
    sessionStorage.setItem(MOA_SCOPE_KEY, scope);
    // Keep URL in sync so refresh/bookmark keeps the tab
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { scope },
      queryParamsHandling: 'merge',
      replaceUrl: true,
    });
    this.isView = false;
    this.isSpecWorkflowView = false;
    this.isCompleteView = false;
    this.isMethodView = false;
    this.specSearch = '';
    this.specCurrentPage = 1;
    this.reloadListForScope();
    this.cdr.detectChanges();
  }

  reloadListForScope(): void {
    if (this.moaScope === 'test') {
      // Test Specific: every workflow tab reads from Test Master
      this.loadTestMasterList();
      return;
    }
    if (this.listView === 'checking' || this.listView === 'approval' || this.listView === 'correction') {
      this.reloadWorkflowQueue();
      return;
    }
    this.getPendingRawMOA();
  }

  getPendingRawMOA(): void {
    this.isLoadingList = true;
    this.cdr.markForCheck();
    // Broad list first (NULL ismoa / mixed statuses), then classic Zuma endpoint
    this.service.getJsonArray('qc/method.php?type=getSpecsForMoa').subscribe({
      next: (rows) => {
        if (rows.length) {
          this.applySpecRows(rows);
          return;
        }
        this.loadPendingSpecFallback();
      },
      error: () => this.loadPendingSpecFallback(),
    });
  }

  /** Classic Zuma list, then the specification logs. */
  private loadPendingSpecFallback(): void {
    this.service
      .getJsonArray('qc/method.php?type=getPendingSpecForMoa&matType=All&listOnly=1&includeCompleted=1')
      .subscribe({
        next: (rows) => {
          if (rows.length) {
            this.applySpecRows(rows);
            return;
          }
          // A query that fails server-side answers 200 with an empty list, so an
          // empty result has to fall through to the logs the same way an error does.
          this.loadSpecsFromCombinedLogs();
        },
        error: () => this.loadSpecsFromCombinedLogs(),
      });
  }

  private applySpecRows(rows: any[]): void {
    this.specifications = Array.isArray(rows) ? rows : [];
    this.specCurrentPage = 1;
    this.refreshSpecTable();
    this.isLoadingList = false;
    this.cdr.detectChanges();
    if (!this.specifications.length) {
      alertify.warning('No specifications found for this plant. Approve a specification first.');
    }
  }

  private loadSpecsFromCombinedLogs(): void {
    const types = ['Raw Material', 'Packing Material', 'Finish Product', 'Inprocess', 'Water Specification'];
    let pending = types.length;
    const merged: any[] = [];
    const seen = new Set<string>();
    types.forEach((logType) => {
      this.service
        .getJsonArray(
          'qc/specification/raw.php?type=getCombinedSpecificationLogs&log_type=' +
            encodeURIComponent(logType)
        )
        .subscribe({
          next: (rows) => {
            (rows || []).forEach((r) => {
              const key = String(r?.specification_no || '') + '|' + String(r?.id || '');
              if (!key || seen.has(key)) {
                return;
              }
              seen.add(key);
              if (!r.ismoa) {
                r.ismoa = 'pending';
              }
              if (r.test_count == null) {
                r.test_count = Array.isArray(r.spectTests) ? r.spectTests.length : 0;
              }
              if (r.completed_method_count == null) {
                r.completed_method_count = 0;
              }
              merged.push(r);
            });
            pending -= 1;
            if (pending <= 0) {
              this.applySpecRows(merged);
            }
          },
          error: () => {
            pending -= 1;
            if (pending <= 0) {
              this.applySpecRows(merged);
              if (!merged.length) {
                alertify.error('Could not load MOA specifications.');
              }
            }
          },
        });
    });
  }

  loadTestMasterList(): void {
    this.isLoadingList = true;
    this.isLoadingWorkflow = false;
    this.cdr.markForCheck();
    // Dedicated MOA list (plant + fallback). getJsonArray tolerates PHP noise.
    this.service.getJsonArray('master/test.php?type=getTestsForMoa').subscribe({
      next: (rows) => {
        const list = Array.isArray(rows) ? rows : [];
        if (list.length) {
          this.applyTestMasterRows(list);
          return;
        }
        // Secondary: standard Test Master log
        this.service.getJsonArray('master/test.php?type=getTestsLog').subscribe({
          next: (fallback) => this.applyTestMasterRows(Array.isArray(fallback) ? fallback : []),
          error: () => {
            this.applyTestMasterRows([]);
            alertify.error('Could not load Test Master for Test Specific MOA.');
          },
        });
      },
      error: () => {
        this.service.getJsonArray('master/test.php?type=getTestsLog').subscribe({
          next: (fallback) => this.applyTestMasterRows(Array.isArray(fallback) ? fallback : []),
          error: () => {
            this.applyTestMasterRows([]);
            alertify.error('Could not load Test Master for Test Specific MOA.');
          },
        });
      },
    });
  }

  private applyTestMasterRows(rows: any[]): void {
    this.testMasterRows = rows;
    this.specCurrentPage = 1;
    this.refreshTestMasterTable();
    this.isLoadingList = false;
    this.cdr.detectChanges();
    if (!rows.length) {
      alertify.warning('No tests found for this plant. Add tests in Test Master first.');
    }
  }

  isTestMethodCompleted(row: Record<string, unknown> | null): boolean {
    if (!row) {
      return false;
    }
    const m = String(row['method'] || '').toLowerCase();
    return m === 'approve' || m === 'approved';
  }

  testMethodStatusLabel(row: Record<string, unknown>): string {
    const m = String(row['method'] || '').trim();
    if (!m) {
      return 'Pending';
    }
    const low = m.toLowerCase();
    if (low === 'approve' || low === 'approved') {
      return 'Approved';
    }
    if (low === 'for checking') {
      return 'For Checking';
    }
    if (low === 'for approval') {
      return 'For Approval';
    }
    if (low === 'correction' || low === 'rejected' || low === 'reject') {
      return 'Correction';
    }
    return m;
  }

  canAddTestMethod(row: Record<string, unknown>): boolean {
    const m = String(row['method'] || '').toLowerCase();
    return !m || m === 'pending' || m === 'active' || m === 'correction' || m === 'rejected' || m === 'reject';
  }

  refreshTestMasterTable(): void {
    let base = this.testMasterRows.slice();
    if (this.listView === 'log') {
      base = base.filter((r) => this.isTestMethodCompleted(r));
    } else if (this.listView === 'pending') {
      base = base.filter((r) => !this.isTestMethodCompleted(r));
    } else if (this.listView === 'checking') {
      base = base.filter((r) => String(r['method'] || '').toLowerCase() === 'for checking');
    } else if (this.listView === 'approval') {
      base = base.filter((r) => String(r['method'] || '').toLowerCase() === 'for approval');
    } else if (this.listView === 'correction') {
      base = base.filter((r) => {
        const m = String(r['method'] || '').toLowerCase();
        return m === 'correction' || m === 'rejected' || m === 'reject';
      });
    }
    const q = this.specSearch.trim().toLowerCase();
    this.filteredTestMaster = !q
      ? base
      : base.filter((row) => this.rowMatchesSearch(row, q));
    if (this.specCurrentPage > this.testMasterTotalPages) {
      this.specCurrentPage = 1;
    }
    this.sliceTestMasterPage();
  }

  get testMasterTotalPages(): number {
    return Math.max(1, Math.ceil(this.filteredTestMaster.length / this.specPageSize) || 1);
  }

  sliceTestMasterPage(): void {
    const start = (this.specCurrentPage - 1) * this.specPageSize;
    this.paginatedTestMaster = this.filteredTestMaster.slice(start, start + this.specPageSize);
  }

  onTestMasterSearchChange(): void {
    this.specCurrentPage = 1;
    this.refreshTestMasterTable();
    this.cdr.markForCheck();
  }

  testMasterWorkflowAction(row: Record<string, unknown>, action: 'forward' | 'approve' | 'reject' | 'resend'): void {
    const id = row['id'];
    if (!id) {
      return;
    }
    let remark = '';
    if (action === 'reject') {
      remark = prompt('Reason for rejection (required):') || '';
      if (!remark.trim()) {
        alertify.error('Rejection remark is required');
        return;
      }
    }
    this.service
      .post(
        'qc/method.php?type=updateTestMasterMethodWorkflow',
        JSON.stringify({ id, action, remark: remark.trim() })
      )
      .subscribe({
        next: (res: any) => {
          if (res?.status === 'success') {
            alertify.success(
              action === 'forward'
                ? 'Forwarded to approval'
                : action === 'approve'
                ? 'Method approved'
                : action === 'resend'
                ? 'Re-sent for checking'
                : 'Sent to correction'
            );
            this.loadTestMasterList();
          } else {
            alertify.error(res?.message || 'Action failed');
          }
        },
        error: () => alertify.error('Action failed'),
      });
  }

  isSpecCompleted(row: Record<string, unknown> | null): boolean {
    if (!row) {
      return false;
    }
    const ismoa = String(row['ismoa'] || '').toLowerCase();
    if (ismoa === 'approve' || ismoa === 'approved') {
      return true;
    }
    const moaWf = String(row['moa_wf_status'] || '').toLowerCase();
    if (moaWf === 'approved') {
      return true;
    }
    const done = Number(row['completed_method_count'] || 0);
    const prepared = Number(row['prepared_method_count'] || 0);
    const total = Number(row['test_count'] || 0);
    const target = prepared > 0 ? prepared : total;
    return target > 0 && done >= target;
  }

  canProceed(row: Record<string, unknown>): boolean {
    return !this.isSpecCompleted(row);
  }

  viewSpecification(row: Record<string, unknown>, mode: DetailMode = 'view'): void {
    if (mode === 'proceed' && !this.canProceed(row)) {
      alertify.error('Proceed is disabled — method is already completed.');
      return;
    }
    const index = this.specifications.findIndex(
      (item) => item['specification_no'] === row['specification_no'] && item['id'] === row['id']
    );
    this.selectedSpec = index >= 0 ? this.specifications[index] : row;
    this.detailMode = mode;
    this.isView = true;
    this.testSearch = '';
    this.testCurrentPage = 1;
    this.specTests = [];
    this.refreshTestTable();
    this.GetRevisonData();
    this.loadSpecTests(String(this.selectedSpec['specification_no'] || ''));
    this.loadMoaCommon(String(this.selectedSpec['specification_no'] || ''));
    this.loadMethodDocuments();
    this.cdr.markForCheck();
  }

  proceedSpecification(row: Record<string, unknown>): void {
    this.viewSpecification(row, 'proceed');
  }

  editSpecification(row: Record<string, unknown>): void {
    if (this.isSpecCompleted(row)) {
      alertify.error('Completed methods cannot be edited directly. Use Revision / Change Control.');
      return;
    }
    this.viewSpecification(row, 'edit');
  }

  get isViewOnly(): boolean {
    return this.detailMode === 'view' || this.isSpecCompleted(this.selectedSpec);
  }

  loadSpecTests(specificationNo: string): void {
    const specNo = String(specificationNo || '').trim();
    if (!specNo) {
      this.specTests = [];
      this.refreshTestTable();
      return;
    }
    this.isLoadingTests = true;
    const testsUrl =
      'qc/method.php?type=getMoaSpecTests&specification_no=' +
      encodeURIComponent(specNo) +
      '&include_method_data=1';
    const draftUrl =
      'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' +
      encodeURIComponent(specNo);

    this.service
      .getJsonArray(testsUrl)
      .pipe(
        catchError(() => of([] as any[])),
        switchMap((rows) => {
          if (Array.isArray(rows) && rows.length) {
            return of(rows);
          }
          // Same endpoint as Specification Logs → View Specification (already works on live).
          return this.service.get(draftUrl).pipe(
            map((res: any) => {
              if (res && typeof res === 'object' && !Array.isArray(res)) {
                this.mergeSpecHeaderFromDraft(res);
                if (Array.isArray(res.spectTests) && res.spectTests.length) {
                  return res.spectTests;
                }
                if (Array.isArray(res.tests) && res.tests.length) {
                  return res.tests;
                }
              }
              return [] as any[];
            }),
            catchError(() => of([] as any[]))
          );
        })
      )
      .subscribe({
        next: (response) => {
          let tests = Array.isArray(response) ? response : [];
          if (!tests.length) {
            const nested = this.selectedSpec['spectTests'] || this.selectedSpec['tests'];
            if (Array.isArray(nested) && nested.length) {
              tests = nested;
            }
          }
          this.specTests = tests;
          this.testCurrentPage = 1;
          this.refreshTestTable();
          this.isLoadingTests = false;
          this.cdr.markForCheck();
        },
        error: () => {
          this.specTests = [];
          this.refreshTestTable();
          this.isLoadingTests = false;
          this.cdr.markForCheck();
          alertify.error('Could not load tests for this specification.');
        },
      });
  }

  private mergeSpecHeaderFromDraft(res: Record<string, unknown>): void {
    if (!this.selectedSpec || typeof this.selectedSpec !== 'object') {
      return;
    }
    const fill = (key: string, altKeys: string[] = []) => {
      const cur = this.selectedSpec[key];
      if (cur != null && String(cur).trim() !== '' && String(cur) !== '-') {
        return;
      }
      for (const k of [key, ...altKeys]) {
        const v = res[k];
        if (v != null && String(v).trim() !== '' && String(v) !== '-') {
          this.selectedSpec[key] = v;
          return;
        }
      }
    };
    fill('material_name', ['item_name', 'product_name']);
    fill('material_code');
    fill('spec_type');
    fill('version_no');
    fill('supersede_no');
    fill('storage_condition');
    fill('gradeName', ['grade']);
  }

  GetRevisonData(): void {
    const specNo = this.selectedSpec['specification_no'];
    if (!specNo) {
      this.revision_history = [];
      return;
    }
    this.service.get('qc/method.php?type=GetRevisonData&specification_no=' + specNo).subscribe((response) => {
      this.revision_history = Array.isArray(response) ? response : [];
      this.cdr.markForCheck();
    });
  }

  refreshSpecTable(): void {
    let base = this.specifications;
    if (this.listView === 'log') {
      base = base.filter((row) => this.isSpecCompleted(row));
    } else if (this.listView === 'pending') {
      base = base.filter((row) => !this.isSpecCompleted(row));
    }
    const q = this.specSearch.trim().toLowerCase();
    this.filteredSpecifications = !q ? base : base.filter((row) => this.rowMatchesSearch(row, q));
    if (this.specCurrentPage > this.specTotalPages) {
      this.specCurrentPage = 1;
    }
    this.sliceSpecPage();
  }

  setListView(view: MoaListView): void {
    if (this.listView === view) {
      return;
    }
    this.listView = view;
    this.specSearch = '';
    this.specCurrentPage = 1;
    this.isSpecWorkflowView = false;
    this.workflowRow = null;
    if (this.moaScope === 'test') {
      // Test Specific: all tabs use Test Master data (filtered by method status)
      if (!this.testMasterRows.length) {
        this.loadTestMasterList();
      } else {
        this.refreshTestMasterTable();
      }
      this.cdr.markForCheck();
      return;
    }
    if (view === 'checking') {
      this.loadCheckingQueue();
    } else if (view === 'approval') {
      this.loadApprovalQueue();
    } else if (view === 'correction') {
      this.loadCorrectionQueue();
    } else {
      if (!this.specifications.length) {
        this.getPendingRawMOA();
      } else {
        this.refreshSpecTable();
      }
    }
    this.cdr.markForCheck();
  }

  get listViewTitle(): string {
    const scopeLabel = this.moaScope === 'test' ? 'Test Specific' : 'Specification Specific';
    switch (this.listView) {
      case 'log':
        return `MOA Master — ${scopeLabel} — Log`;
      case 'checking':
        return `MOA Master — ${scopeLabel} — Checking`;
      case 'approval':
        return `MOA Master — ${scopeLabel} — Approval`;
      case 'correction':
        return `MOA Master — ${scopeLabel} — Correction`;
      default:
        return `MOA Master — ${scopeLabel} — Proceed / Pending`;
    }
  }

  loadCheckingQueue(): void {
    if (this.moaScope === 'test') {
      this.loadTestMasterList();
      return;
    }
    this.isLoadingWorkflow = true;
    this.workflowRows = [];
    this.filteredWorkflow = [];
    this.wf.getReviewQueue().subscribe({
      next: (rows) => {
        this.workflowRows = Array.isArray(rows) ? rows : [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowRows = [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
        alertify.error('Could not load checking queue.');
      },
    });
  }

  loadApprovalQueue(): void {
    if (this.moaScope === 'test') {
      this.loadTestMasterList();
      return;
    }
    this.isLoadingWorkflow = true;
    this.workflowRows = [];
    this.filteredWorkflow = [];
    this.wf.getApprovalQueue().subscribe({
      next: (rows) => {
        this.workflowRows = Array.isArray(rows) ? rows : [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowRows = [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
        alertify.error('Could not load approval queue.');
      },
    });
  }

  loadCorrectionQueue(): void {
    if (this.moaScope === 'test') {
      this.loadTestMasterList();
      return;
    }
    this.isLoadingWorkflow = true;
    this.workflowRows = [];
    this.filteredWorkflow = [];
    this.wf.getCorrectionQueue().subscribe({
      next: (rows) => {
        this.workflowRows = Array.isArray(rows) ? rows : [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowRows = [];
        this.refreshWorkflowTable();
        this.isLoadingWorkflow = false;
        this.cdr.markForCheck();
        alertify.error('Could not load correction queue.');
      },
    });
  }

  openSpecWorkflow(row: Record<string, unknown>, stage: 'checking' | 'approval' | 'correction'): void {
    this.workflowRow = row;
    this.workflowReviewStage = stage;
    this.workflowRemark = '';
    this.testLineRemark = '';
    this.testLineRejectId = null;
    this.isCompleteView = false;
    this.isSpecWorkflowView = true;
    this.loadCompleteMethod(String(row['specification_no'] || ''), true);
    this.cdr.markForCheck();
  }

  closeSpecWorkflow(): void {
    const wasLog = this.workflowReviewStage === 'log';
    this.isSpecWorkflowView = false;
    this.workflowRow = null;
    this.workflowRemark = '';
    this.completeDocLoadFailed = false;
    this.completeDoc = { specification: {}, common: {}, tests: [] };
    if (wasLog || this.listView === 'log') {
      this.refreshSpecTable();
    } else {
      this.reloadWorkflowQueue();
    }
    this.cdr.markForCheck();
  }

  reloadWorkflowQueue(): void {
    if (this.listView === 'checking') {
      this.loadCheckingQueue();
    } else if (this.listView === 'approval') {
      this.loadApprovalQueue();
    } else if (this.listView === 'correction') {
      this.loadCorrectionQueue();
    }
  }

  submitSpecForward(): void {
    const specNo = String(this.workflowRow?.['specification_no'] || '');
    if (!specNo) {
      return;
    }
    this.workflowSubmitting = true;
    this.wf.specReviewAction(specNo, 'forward', this.workflowRemark.trim()).subscribe({
      next: (r: any) => {
        this.workflowSubmitting = false;
        if (r?.status === 'success') {
          alertify.success('Complete method forwarded for approval');
          this.closeSpecWorkflow();
        } else {
          alertify.error(r?.message || 'Could not forward complete method');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowSubmitting = false;
        alertify.error('Could not forward complete method');
        this.cdr.markForCheck();
      },
    });
  }

  submitSpecApprove(): void {
    const specNo = String(this.workflowRow?.['specification_no'] || '');
    if (!specNo) {
      return;
    }
    this.workflowSubmitting = true;
    this.wf.specApprovalAction(specNo, 'approve', this.workflowRemark.trim()).subscribe({
      next: (r: any) => {
        this.workflowSubmitting = false;
        if (r?.status === 'success') {
          alertify.success('Complete method approved');
          this.closeSpecWorkflow();
        } else {
          alertify.error(r?.message || 'Approval failed');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowSubmitting = false;
        alertify.error('Approval failed');
        this.cdr.markForCheck();
      },
    });
  }

  submitSpecReject(): void {
    const specNo = String(this.workflowRow?.['specification_no'] || '');
    const remark = this.workflowRemark.trim();
    if (!specNo) {
      return;
    }
    if (!remark) {
      alertify.error('Rejection remark is required for the complete method');
      return;
    }
    this.workflowSubmitting = true;
    const action = this.workflowReviewStage === 'approval' ? 'reject' : 'reject';
    const obs =
      this.workflowReviewStage === 'approval'
        ? this.wf.specApprovalAction(specNo, 'reject', remark)
        : this.wf.specReviewAction(specNo, 'reject', remark);
    obs.subscribe({
      next: (r: any) => {
        this.workflowSubmitting = false;
        if (r?.status === 'success') {
          alertify.success('Complete method sent to Correction');
          this.closeSpecWorkflow();
        } else {
          alertify.error(r?.message || 'Reject failed');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.workflowSubmitting = false;
        alertify.error('Reject failed');
        this.cdr.markForCheck();
      },
    });
  }

  acceptTestLine(test: Record<string, unknown>): void {
    if (!test?.['id']) {
      return;
    }
    const stage = this.workflowReviewStage === 'approval' ? 'approval' : 'checking';
    this.wf.testLineAction(test['id'] as number, stage, 'accept').subscribe((r: any) => {
      if (r?.status === 'success') {
        alertify.success('Test method marked accepted');
        this.refreshCompleteMethod();
      } else {
        alertify.error('Could not update test method');
      }
    });
  }

  promptRejectTestLine(test: Record<string, unknown>): void {
    this.testLineRejectId = Number(test['id']);
    this.testLineRemark = '';
    this.cdr.markForCheck();
  }

  cancelRejectTestLine(): void {
    this.testLineRejectId = null;
    this.testLineRemark = '';
    this.cdr.markForCheck();
  }

  confirmRejectTestLine(): void {
    if (!this.testLineRejectId) {
      return;
    }
    const remark = this.testLineRemark.trim();
    if (!remark) {
      alertify.error('Remark required to reject this test method');
      return;
    }
    const stage = this.workflowReviewStage === 'approval' ? 'approval' : 'checking';
    this.wf.testLineAction(this.testLineRejectId, stage, 'reject', remark).subscribe((r: any) => {
      if (r?.status === 'success') {
        alertify.success('Test method sent to correction');
        this.testLineRejectId = null;
        this.testLineRemark = '';
        this.refreshCompleteMethod();
      } else {
        alertify.error(r?.message || 'Reject failed');
      }
    });
  }

  private refreshCompleteMethod(): void {
    const specNo = String(this.workflowRow?.['specification_no'] || this.completeDoc?.specification?.specification_no || '');
    if (specNo) {
      this.loadCompleteMethod(specNo, this.completeReadOnly);
    }
  }

  testLineStatus(test: Record<string, unknown>): string {
    const stage = this.workflowReviewStage === 'approval' ? 'method_line_approve' : 'method_line_check';
    return String(test[stage] || 'pending').toLowerCase();
  }

  canActOnTestLine(test: Record<string, unknown>): boolean {
    const wf = String(test['method_wf_status'] || '').toLowerCase();
    if (this.workflowReviewStage === 'checking') {
      return wf === 'pending_review';
    }
    if (this.workflowReviewStage === 'approval') {
      return wf === 'pending_approval';
    }
    return false;
  }

  get canFinalSpecAction(): boolean {
    if (!this.completeTests.length) {
      return this.completeDocLoadFailed && !!String(this.workflowRow?.['specification_no'] || '').trim();
    }
    const wfKey = this.workflowReviewStage === 'approval' ? 'pending_approval' : 'pending_review';
    const lineKey = this.workflowReviewStage === 'approval' ? 'method_line_approve' : 'method_line_check';
    const inQueue = this.completeTests.filter((t) => String(t['method_wf_status'] || '').toLowerCase() === wfKey);
    if (!inQueue.length) {
      // If no tests carry the wf status (old DB / column missing), allow forward as long as there are tests.
      return this.completeTests.length > 0;
    }
    return inQueue.every((t) => {
      const v = String(t[lineKey] || '').toLowerCase();
      // null / empty means column doesn't exist on live DB — treat as accepted.
      return v === 'accepted' || v === '' || v === 'null';
    });
  }

  openWorkflowReview(row: Record<string, unknown>, stage: 'checking' | 'approval'): void {
    this.openSpecWorkflow(row, stage);
  }

  closeWorkflowReview(): void {
    this.closeSpecWorkflow();
  }

  openCompleteMethodFromWorkflow(): void {
    /* complete method already shown in spec workflow view */
  }

  submitWorkflowForward(): void {
    this.submitSpecForward();
  }

  submitWorkflowApprove(): void {
    this.submitSpecApprove();
  }

  submitWorkflowReject(): void {
    this.submitSpecReject();
  }

  resendForChecking(row: Record<string, unknown>): void {
    this.resendSpecForChecking(row);
  }

  resendSpecForChecking(row: Record<string, unknown>): void {
    const specNo = String(row['specification_no'] || '');
    if (!specNo) {
      return;
    }
    this.wf.submitSpecForReview(specNo).subscribe((r: any) => {
      if (r?.status === 'success') {
        alertify.success('Complete method re-sent for Checking (' + (r.moved ?? 0) + ' test(s))');
        this.loadCorrectionQueue();
      } else {
        alertify.error('Could not re-send for checking');
      }
      this.cdr.markForCheck();
    });
  }

  forwardForApproval(row: Record<string, unknown>): void {
    this.openWorkflowReview(row, 'checking');
  }

  approveMethod(row: Record<string, unknown>): void {
    this.openWorkflowReview(row, 'approval');
  }

  rejectWorkflow(row: Record<string, unknown>, stage: 'checking' | 'approval'): void {
    this.openWorkflowReview(row, stage);
  }

  refreshWorkflowTable(): void {
    const q = this.specSearch.trim().toLowerCase();
    this.filteredWorkflow = !q ? this.workflowRows : this.workflowRows.filter((row) => this.rowMatchesSearch(row, q));
  }

  editCorrectionSpec(row: Record<string, unknown>): void {
    const specNo = String(row['specification_no'] || '');
    const match = this.specifications.find((s) => s['specification_no'] === specNo);
    if (match) {
      this.viewSpecification(match, 'edit');
    } else {
      this.viewSpecification(row, 'edit');
    }
  }

  viewWorkflowMethod(row: Record<string, unknown>): void {
    const stage = this.listView === 'approval' ? 'approval' : this.listView === 'correction' ? 'correction' : 'checking';
    this.openSpecWorkflow(row, stage);
  }

  canSendForChecking(comp: Record<string, unknown>): boolean {
    if (this.isViewOnly) {
      return false;
    }
    if (this.methodStatusKey(comp) !== 'added') {
      return false;
    }
    const wf = String(comp['method_wf_status'] || '').toLowerCase();
    return wf !== 'pending_review' && wf !== 'pending_approval' && wf !== 'approved';
  }

  sendForChecking(comp: Record<string, unknown>): void {
    if (!comp?.['id']) {
      alertify.error('Method id missing.');
      return;
    }
    this.wf.submitForReview(comp['id'] as number).subscribe((r: any) => {
      if (r?.status === 'success') {
        alertify.success('Method sent for Checking & Approval');
        this.loadSpecTests(String(this.selectedSpec['specification_no'] || ''));
      } else {
        alertify.error('Could not send method for checking');
      }
    });
  }

  refreshTestTable(): void {
    const q = this.testSearch.trim().toLowerCase();
    this.filteredTests = !q ? this.specTests : this.specTests.filter((row) => this.rowMatchesSearch(row, q));
    this.sliceTestPage();
  }

  sliceSpecPage(): void {
    const start = (this.specCurrentPage - 1) * this.specPageSize;
    this.paginatedSpecifications = this.filteredSpecifications.slice(start, start + this.specPageSize);
  }

  sliceTestPage(): void {
    const start = (this.testCurrentPage - 1) * this.testPageSize;
    this.paginatedTests = this.filteredTests.slice(start, start + this.testPageSize);
  }

  get specTotalPages(): number {
    return Math.max(1, Math.ceil(this.filteredSpecifications.length / this.specPageSize));
  }

  get testTotalPages(): number {
    return Math.max(1, Math.ceil(this.filteredTests.length / this.testPageSize));
  }

  onSpecSearchChange(): void {
    this.specCurrentPage = 1;
    if (this.moaScope === 'test') {
      this.refreshTestMasterTable();
    } else if (this.listView === 'checking' || this.listView === 'approval' || this.listView === 'correction') {
      this.refreshWorkflowTable();
    } else {
      this.refreshSpecTable();
    }
    this.cdr.markForCheck();
  }

  onTestSearchChange(): void {
    this.testCurrentPage = 1;
    this.refreshTestTable();
    this.cdr.markForCheck();
  }

  onSpecPageSizeChange(size: number): void {
    this.specPageSize = size;
    this.specCurrentPage = 1;
    if (this.moaScope === 'test') {
      this.refreshTestMasterTable();
    } else {
      this.refreshSpecTable();
    }
    this.cdr.markForCheck();
  }

  onTestPageSizeChange(size: number): void {
    this.testPageSize = size;
    this.testCurrentPage = 1;
    this.refreshTestTable();
    this.cdr.markForCheck();
  }

  specGoToPage(page: number): void {
    if (this.moaScope === 'test') {
      this.specCurrentPage = Math.min(Math.max(1, page), this.testMasterTotalPages);
      this.sliceTestMasterPage();
    } else {
      this.specCurrentPage = Math.min(Math.max(1, page), this.specTotalPages);
      this.sliceSpecPage();
    }
    this.cdr.markForCheck();
  }

  testGoToPage(page: number): void {
    this.testCurrentPage = Math.min(Math.max(1, page), this.testTotalPages);
    this.sliceTestPage();
    this.cdr.markForCheck();
  }

  methodStatusKey(comp: Record<string, unknown> | null): MethodStatusKey {
    if (!comp) {
      return 'pending';
    }
    const method = String(comp['method'] || '')
      .trim()
      .toLowerCase();
    if (method === 'approve' || method === 'approved') {
      return 'completed';
    }
    // Spec tests are saved with method=NULL until a method is added. Treat empty /
    // pending as "needs Add Method" — otherwise the UI shows Method Prepared + Edit
    // with no Add Method button.
    if (!method || method === 'pending' || method === 'null' || method === 'undefined') {
      return 'pending';
    }
    return 'added';
  }

  methodStatusLabel(comp: Record<string, unknown>): string {
    const key = this.methodStatusKey(comp);
    if (key === 'pending') {
      return 'Pending Method';
    }
    if (key === 'completed') {
      return 'Completed';
    }
    const wf = String(comp['method_wf_status'] || '').toLowerCase();
    if (wf === 'pending_review') {
      return 'Pending Checking';
    }
    if (wf === 'pending_approval') {
      return 'Pending Approval';
    }
    if (wf === 'correction' || wf === 'rejected') {
      return 'Correction — Re-submit';
    }
    return 'Method Prepared';
  }

  methodStatusClass(comp: Record<string, unknown>): string {
    return 'moa-status-badge moa-status-badge--' + this.methodStatusKey(comp);
  }

  canAddMethod(comp: Record<string, unknown>): boolean {
    return !this.isViewOnly && this.methodStatusKey(comp) === 'pending';
  }

  canViewMethod(comp: Record<string, unknown>): boolean {
    if (this.methodStatusKey(comp) !== 'pending') {
      return true;
    }
    return this.hasMethodContent(this.getMethodData(comp));
  }

  canRequestRevision(row: Record<string, unknown>): boolean {
    const status = this.methodStatusKey(row);
    if (status === 'pending') {
      return false;
    }
    const log = String(row['method_log_status'] || 'active').toLowerCase();
    return log === 'active' || log === 'inactive' || log === 'obsolete' || status === 'completed';
  }

  canEditAfterQa(row: Record<string, unknown>): boolean {
    return String(row['revision_request_status'] || '') === 'qa_approved';
  }

  addmethod(id: string | number): void {
    if (this.moaScope === 'test') {
      this.router.navigate(['/qc/moa/methods/new/' + id], { queryParams: { moaMode: 'test' } });
      return;
    }
    this.router.navigate(['/qc/moa/methods/new/' + id]);
  }

  viewMethod(id: string | number): void {
    if (this.moaScope === 'test') {
      this.router.navigate(['/qc/moa/methods/new/' + id], { queryParams: { moaMode: 'test' } });
      return;
    }
    this.router.navigate(['/qc/moa/methods/news/' + id]);
  }

  addTestMasterMethod(row: Record<string, unknown>): void {
    this.addmethod(row['id'] as string | number);
  }

  viewTestMasterMethod(row: Record<string, unknown>): void {
    this.viewMethod(row['id'] as string | number);
  }

  openMethodView(comp: Record<string, unknown>): void {
    this.selectedMethodTest = { ...comp };
    this.isMethodView = true;
    this.isLoadingMethodView = true;
    const testId = comp['id'];
    if (!testId) {
      this.isLoadingMethodView = false;
      return;
    }
    this.service.get('qc/method.php?type=getMoaMethodForSpecTest&spec_test_id=' + encodeURIComponent(String(testId))).subscribe({
      next: (row: any) => {
        const md = this.normalizeMethodData(row?.method_data || row?.methods || comp['method_data']);
        if (this.hasMethodContent(md)) {
          this.selectedMethodTest = { ...comp, ...(row && typeof row === 'object' ? row : {}), method_data: md };
          this.isLoadingMethodView = false;
          this.cdr.markForCheck();
          return;
        }
        this.service.get('qc/method.php?type=get_test_data&id=' + encodeURIComponent(String(testId))).subscribe({
          next: (full: any) => {
            const fullMd = this.normalizeMethodData(full?.methods || full?.method_data || md);
            this.selectedMethodTest = {
              ...comp,
              ...(row && typeof row === 'object' ? row : {}),
              ...(full && typeof full === 'object' ? full : {}),
              method_data: fullMd,
            };
            this.isLoadingMethodView = false;
            this.cdr.markForCheck();
          },
          error: () => {
            this.selectedMethodTest = { ...comp, ...(row && typeof row === 'object' ? row : {}), method_data: md };
            this.isLoadingMethodView = false;
            this.cdr.markForCheck();
          },
        });
      },
      error: () => {
        // Editor path — recovers method body when getMoaMethodForSpecTest is empty/outdated.
        this.service.get('qc/method.php?type=get_test_data&id=' + encodeURIComponent(String(testId))).subscribe({
          next: (row: any) => {
            const md = this.normalizeMethodData(row?.methods || row?.method_data);
            if (row && typeof row === 'object') {
              this.selectedMethodTest = { ...comp, ...row, method_data: md };
            }
            this.isLoadingMethodView = false;
            this.cdr.markForCheck();
          },
          error: () => {
            this.isLoadingMethodView = false;
            this.cdr.markForCheck();
          },
        });
      },
    });
  }

  getMethodData(comp: Record<string, unknown> | null): Record<string, unknown> {
    return this.normalizeMethodData(comp?.['method_data'] ?? comp?.['methods']);
  }

  closeMethodView(): void {
    this.isMethodView = false;
    this.selectedMethodTest = null;
  }

  methodDetailsList(comp: Record<string, unknown> | null): any[] {
    const details = comp?.['method_details'];
    return Array.isArray(details) ? details : [];
  }

  openRevisionForSpec(row: Record<string, unknown>): void {
    this.selectedActionRow = row;
    this.service
      .get('qc/method.php?type=getMoaSpecTests&specification_no=' + encodeURIComponent(String(row['specification_no'] || '')))
      .subscribe({
        next: (response) => {
          const rows = Array.isArray(response) ? response : [];
          const completed = rows.find((r) => this.methodStatusKey(r) === 'completed') || rows.find((r) => this.canRequestRevision(r));
          if (!completed) {
            alertify.error('No completed method found for revision on this specification.');
            return;
          }
          this.openRevisionRequest(completed);
        },
        error: () => alertify.error('Could not load methods for revision.'),
      });
  }

  openRevisionRequest(row: Record<string, unknown>): void {
    this.selectedActionRow = row;
    this.revisionForm = {
      revision_reason: '',
      proposed_changes: '',
      effective_date: '',
    };
    this.showRevisionModal = true;
    this.cdr.markForCheck();
  }

  submitRevisionRequest(): void {
    if (!this.revisionForm.revision_reason.trim() || !this.revisionForm.proposed_changes.trim()) {
      alertify.error('Revision reason and proposed changes are required');
      return;
    }
    const row = this.selectedActionRow || {};
    const payload = {
      spec_test_id: row['id'],
      specification_no: row['specification_no'],
      document_no: row['specification_no'],
      document_name: row['test'] || this.selectedSpec['material_name'],
      test_name: row['test'] || '',
      subtest: row['subtest'] || '',
      ...this.revisionForm,
    };
    this.wf.submitRevisionRequest(payload).subscribe((r) => {
      if (r?.status === 'success') {
        alertify.success('Revision request sent to QA');
        this.showRevisionModal = false;
        if (this.isView) {
          this.loadSpecTests(String(this.selectedSpec['specification_no'] || ''));
        }
        this.cdr.markForCheck();
      } else {
        alertify.error('Failed to submit revision request');
      }
    });
  }

  editMethod(row: Record<string, unknown>): void {
    if (this.methodStatusKey(row) === 'completed' && !this.canEditAfterQa(row)) {
      alertify.error('Completed methods cannot be edited directly. Submit Revision first.');
      return;
    }
    this.router.navigate(['/qc/moa/methods/new/' + row['id']]);
  }

  openChangeControlForSpec(row: Record<string, unknown>): void {
    this.selectedActionRow = row;
    this.service
      .get('qc/method.php?type=getMoaSpecTests&specification_no=' + encodeURIComponent(String(row['specification_no'] || '')))
      .subscribe({
        next: (response) => {
          const rows = Array.isArray(response) ? response : [];
          const target =
            rows.find((r) => this.canEditAfterQa(r)) ||
            rows.find((r) => this.methodStatusKey(r) === 'completed') ||
            rows[0];
          if (!target) {
            alertify.error('No method found to initiate Change Control.');
            return;
          }
          this.openChangeControl(target);
        },
        error: () => alertify.error('Could not load methods for Change Control.'),
      });
  }

  openChangeControl(row: Record<string, unknown>): void {
    this.selectedActionRow = row;
    this.activeRevisionRequestId = Number(row['revision_request_id'] || 0);
    const today = new Date().toISOString().slice(0, 10);
    this.ccInit = {
      emp_name: localStorage.getItem('username') || localStorage.getItem('emp_id') || '',
      department_name: localStorage.getItem('department') || 'Quality Control',
      dateOfIssuance: today,
      section: 'Test Method / MOA',
      nameOfProductDoc:
        String(row['test'] || this.selectedSpec['material_name'] || 'MOA') +
        (row['subtest'] ? ' — ' + row['subtest'] : ''),
      batchNoDocNo: row['specification_no'] || this.selectedSpec['specification_no'],
      changeReqFor: 'Specification / Analytical Procedure',
      existingProcedure: row['description'] || 'Current approved test method',
      changedDetails: row['proposed_changes'] || this.revisionForm.proposed_changes || '',
      justificationOfChange: row['revision_reason'] || 'Method revision as per QA approved request',
      tentativeDateClosing: '',
      remark: 'MOA method revision — linked from Raw MOA New',
    };
    this.ccAffDocs = [
      {
        docNo: String(row['specification_no'] || this.selectedSpec['specification_no'] || ''),
        docTitle: String(row['test'] || this.selectedSpec['material_name'] || 'MOA'),
        effectiveDate: today,
        typeOfImpact: 'Specification / Analytical Procedure',
        tcdImplemantation: today,
      },
    ];
    this.showCcModal = true;
    this.cdr.markForCheck();
  }

  removeCcAffDoc(i: number): void {
    this.ccAffDocs.splice(i, 1);
  }

  onCcFile(event: Event, kind: 'exis' | 'change' | 'just'): void {
    const file = (event.target as HTMLInputElement).files?.[0] || null;
    if (kind === 'exis') this.exisDoc = file;
    if (kind === 'change') this.changeDetDoc = file;
    if (kind === 'just') this.justChangeDoc = file;
  }

  submitChangeControl(): void {
    if (!this.ccInit.dateOfIssuance || !this.ccInit.section?.trim()) {
      alertify.error('Date of Issuance and Section are required');
      return;
    }
    if (!this.ccInit.existingProcedure?.trim() || !this.ccInit.changedDetails?.trim() || !this.ccInit.justificationOfChange?.trim()) {
      alertify.error('Existing procedure, change details, and justification are required');
      return;
    }
    if (!this.ccInit.tentativeDateClosing || !this.ccInit.remark?.trim()) {
      alertify.error('Tentative closing date and remark are required');
      return;
    }
    if (!this.ccAffDocs.length) {
      alertify.error('Add at least one affected document');
      return;
    }
    const formData = new FormData();
    formData.append('department_name', this.ccInit.department_name);
    formData.append('dateOfIssuance', this.ccInit.dateOfIssuance);
    formData.append('section', this.ccInit.section);
    formData.append('nameOfProductDoc', this.ccInit.nameOfProductDoc);
    formData.append('batchNoDocNo', this.ccInit.batchNoDocNo);
    formData.append('changeReqFor', this.ccInit.changeReqFor);
    formData.append('existingProcedure', this.ccInit.existingProcedure);
    formData.append('changedDetails', this.ccInit.changedDetails);
    formData.append('justificationOfChange', this.ccInit.justificationOfChange);
    formData.append('tentativeDateClosing', this.ccInit.tentativeDateClosing);
    formData.append('remark', this.ccInit.remark);
    formData.append('changeAffDoc', JSON.stringify(this.ccAffDocs));
    if (this.exisDoc) formData.append('exisDoc', this.exisDoc, this.exisDoc.name);
    if (this.changeDetDoc) formData.append('changeDetDoc', this.changeDetDoc, this.changeDetDoc.name);
    if (this.justChangeDoc) formData.append('justChangeDoc', this.justChangeDoc, this.justChangeDoc.name);

    this.isCcSaving = true;
    this.metaService.submitChangeControlInitiation(formData).subscribe(
      (res: any) => {
        this.isCcSaving = false;
        if (res?.status === 'success') {
          const editLink = '/qc/moa/methods/new/' + (this.selectedActionRow?.['id'] || '');
          if (this.activeRevisionRequestId) {
            this.wf.linkChangeControl(this.activeRevisionRequestId, res.ctrl_no || '', res.id || 0, editLink).subscribe();
          }
          this.showCcModal = false;
          alertify.success(
            res.ctrl_no
              ? 'Change Control ' + res.ctrl_no + ' initiated — proceed in QA → QMS → Change Control'
              : 'Change Control submitted'
          );
          this.cdr.markForCheck();
        } else {
          alertify.error(res?.status || 'Change Control failed');
        }
      },
      () => {
        this.isCcSaving = false;
        alertify.error('Change Control submission failed');
        this.cdr.markForCheck();
      }
    );
  }

  Rivision(index: number, test: string): void {
    this.isRivisionrequest = true;
    this.selectedrivision = this.specifications[index];
    this.doc_name = test;
    this.method_no = String(this.selectedrivision['specification_no'] || '');
  }

  addrevision_history(data: { value: Record<string, unknown> }): void {
    const temp = { ...data.value };
    temp['specification_no'] = this.selectedSpec['specification_no'];

    this.service
      .post(
        'qc/method.php?type=addrevision_history_STP&method_no=' + (this.selectedrivision?.['test_method_no'] || ''),
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('save');
          this.GetRevisonData();
        } else {
          alertify.error('An error Occured, Please try again!');
        }
      });
  }

  save(data: { value: Record<string, unknown> }): void {
    const temp = data.value;
    this.service
      .post('master/test.php?type=save_Request&method_no=' + this.selectedrivision['test_method_no'], JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('save');
          this.isRivisionrequest = false;
        } else {
          alertify.error('An error Occured, Please try again!');
        }
      });
  }

  download(): void {
    this.downloadPdf(this.selectedSpec);
  }

  downloadPdf(row: Record<string, unknown>): void {
    if (!row?.['id']) {
      alertify.error('Specification id missing for PDF.');
      return;
    }
    this.service.open('qc/method.php?type=stppdf&id=' + row['id']);
  }

  downloadMethodPdf(row: Record<string, unknown>): void {
    if (!row?.['id']) {
      alertify.error('Method id missing for PDF.');
      return;
    }
    this.service.open('pdf1/moa.php?type=testmethod&id=' + row['id']);
  }

  closeDetail(): void {
    this.isView = false;
    this.detailMode = 'proceed';
    this.specTests = [];
    this.showCommonPanel = false;
    this.cdr.markForCheck();
    this.getPendingRawMOA();
  }

  // ---- MOA common (document-level) sections ---------------------------------
  private normalizeCommon(data: any): any {
    const c = data && typeof data === 'object' ? { ...data } : {};
    c['Genral_Instruction'] = Array.isArray(c['Genral_Instruction']) ? c['Genral_Instruction'] : [];
    c['Associative_Document'] = Array.isArray(c['Associative_Document']) ? c['Associative_Document'] : [];
    c['Refrenced_Document'] = Array.isArray(c['Refrenced_Document']) ? c['Refrenced_Document'] : [];
    c['defination'] = Array.isArray(c['defination']) ? c['defination'] : [];
    ['purpose', 'standard_Solutions', 'Test_Solutions', 'Scope'].forEach((k) => {
      if (c[k] == null) { c[k] = 'NA'; }
    });
    return c;
  }

  loadMoaCommon(specificationNo: string): void {
    this.commonEdit = { purpose: false, std: false, test: false, scope: false };
    if (!specificationNo) {
      this.commonSections = this.normalizeCommon({});
      this.commonControl = { ...this.commonSections };
      return;
    }
    this.service
      .get('qc/method.php?type=getMoaCommon&specification_no=' + encodeURIComponent(specificationNo))
      .subscribe({
        next: (response) => {
          this.commonSections = this.normalizeCommon(response);
          this.commonControl = { ...this.commonSections };
          this.cdr.markForCheck();
        },
        error: () => {
          this.commonSections = this.normalizeCommon({});
          this.commonControl = { ...this.commonSections };
          this.cdr.markForCheck();
        },
      });
  }

  loadMethodDocuments(): void {
    if (this.methodDocuments.length) {
      return;
    }
    this.service
      .get('master/checklist.php?type=getMethodDocuments&doc_type=AssociateDoc')
      .subscribe((response) => {
        this.methodDocuments = Array.isArray(response) ? response : [];
        this.cdr.markForCheck();
      });
  }

  toggleCommonPanel(): void {
    this.showCommonPanel = !this.showCommonPanel;
    this.cdr.markForCheck();
  }

  toggleCommonSection(key: string): void {
    this.commonShow[key] = !this.commonShow[key];
    this.cdr.markForCheck();
  }

  onCommonDocSelect(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    if (!value || !this.methodDocuments?.length) {
      this.selectedCommonDoc = null;
      return;
    }
    this.selectedCommonDoc = this.methodDocuments.find((d: any) => String(d.doc_no) === value) || null;
  }

  addCommonGenIns(form: any): void {
    const v = form?.value || {};
    if (!v.parameter && !v.discrption && !v.spec_limit) {
      alertify.error('Enter at least one field.');
      return;
    }
    this.commonSections['Genral_Instruction'].push({ ...v });
    form.reset();
  }

  delCommonGenIns(i: number): void {
    this.commonSections['Genral_Instruction'].splice(i, 1);
  }

  addCommonDoc(target: 'Associative_Document' | 'Refrenced_Document'): void {
    if (!this.selectedCommonDoc?.doc_no) {
      alertify.error('Please select a document first.');
      return;
    }
    this.commonSections[target].push({
      doc_no: this.selectedCommonDoc['doc_no'],
      doc_name: this.selectedCommonDoc['doc_name'],
    });
    this.selectedCommonDoc = null;
  }

  delCommonDoc(target: 'Associative_Document' | 'Refrenced_Document', i: number): void {
    this.commonSections[target].splice(i, 1);
  }

  addCommonDefinition(): void {
    const term = (this.commonTerm || '').trim();
    const def = (this.commonDef || '').trim();
    if (!term || !def) {
      alertify.error('Please enter both term and definition.');
      return;
    }
    this.commonSections['defination'].push({ term, defination: def });
    this.commonTerm = '';
    this.commonDef = '';
  }

  delCommonDefinition(i: number): void {
    this.commonSections['defination'].splice(i, 1);
  }

  editCommonText(key: 'purpose' | 'std' | 'test' | 'scope'): void {
    this.commonEdit[key] = true;
  }

  saveCommon(check: string): void {
    if (this.isViewOnly) {
      alertify.error('This specification is completed / read-only.');
      return;
    }
    const specNo = String(this.selectedSpec['specification_no'] || '');
    if (!specNo) {
      alertify.error('Specification number missing.');
      return;
    }
    const temp: any = { specification_no: specNo, check };
    if (check === 'General Instructions') temp['Genral_Instruction'] = this.commonSections['Genral_Instruction'];
    else if (check === 'Purpose') temp['purpose'] = this.commonSections['purpose'];
    else if (check === 'standard_Solutions') temp['standard_Solutions'] = this.commonSections['standard_Solutions'];
    else if (check === 'Test_Solutions') temp['Test_Solutions'] = this.commonSections['Test_Solutions'];
    else if (check === 'Scope') temp['Scope'] = this.commonSections['Scope'];
    else if (check === 'Associate Documents') temp['Associative_Document'] = this.commonSections['Associative_Document'];
    else if (check === 'Reference Documents') temp['Refrenced_Document'] = this.commonSections['Refrenced_Document'];
    else if (check === 'Definition') temp['defination'] = this.commonSections['defination'];

    this.service.post('qc/method.php?type=saveMoaCommon', JSON.stringify(temp)).subscribe({
      next: (res: any) => {
        if (this.service.isApiSuccess(res)) {
          alertify.success(check + ' saved.');
          this.loadMoaCommon(specNo);
        } else {
          alertify.error('Could not save this section.');
        }
      },
      error: () => alertify.error('Network error while saving.'),
    });
  }

  // ---- Complete Method of Analysis document --------------------------------
  /** Open the assembled complete-method document for the specification in the detail view. */
  openCompleteMethod(): void {
    const specNo = String(this.selectedSpec['specification_no'] || '');
    this.loadCompleteMethod(specNo, this.isViewOnly);
  }

  /** Open the complete-method document (read-only) from Log Table — full page like Checking. */
  openCompleteMethodFor(row: Record<string, unknown>): void {
    const specNo = String(row?.['specification_no'] || '');
    if (!specNo) {
      alertify.error('Specification number missing.');
      return;
    }
    this.workflowRow = { ...row };
    this.workflowReviewStage = 'log';
    this.workflowRemark = '';
    this.testLineRemark = '';
    this.testLineRejectId = null;
    this.isCompleteView = false;
    this.isSpecWorkflowView = true;
    this.loadCompleteMethod(specNo, true);
    this.cdr.markForCheck();
  }

  private loadCompleteMethod(specNo: string, readOnly: boolean): void {
    if (!specNo) {
      alertify.error('Specification number missing.');
      return;
    }
    this.completeReadOnly = readOnly;
    if (!this.isSpecWorkflowView) {
      this.isCompleteView = true;
    }
    this.isLoadingComplete = true;
    this.completeDocLoadFailed = false;
    this.completeDoc = { specification: {}, common: {}, tests: [] };
    this.cdr.markForCheck();
    this.wf
      .getCompleteMethod(specNo)
      .pipe(
        catchError(() => of(null)),
        switchMap((res: any) => this.enrichCompleteMethodTests(specNo, res))
      )
      .subscribe({
        next: (res: any) => {
          this.completeDoc = res && typeof res === 'object' ? res : { specification: {}, common: {}, tests: [] };
          if (!Array.isArray(this.completeDoc.tests)) {
            this.completeDoc.tests = [];
          } else {
            this.completeDoc.tests = this.completeDoc.tests.map((t) => this.withNormalizedMethodData(t));
          }
          if (this.workflowRow) {
            this.completeDoc.specification = {
              ...(this.completeDoc.specification || {}),
              ...this.workflowRow,
              specification_no: specNo,
            };
          }
          this.completeDocLoadFailed = this.completeDoc.tests.length === 0;
          this.isLoadingComplete = false;
          this.cdr.markForCheck();
        },
        error: () => {
          this.isLoadingComplete = false;
          this.completeDocLoadFailed = true;
          this.completeDoc = {
            specification: { ...(this.workflowRow || {}), specification_no: specNo },
            common: {},
            tests: Array.isArray(this.specTests) ? this.specTests : [],
          };
          this.completeDocLoadFailed = this.completeDoc.tests.length === 0;
          this.cdr.markForCheck();
        },
      });
  }

  private enrichCompleteMethodTests(specNo: string, res: any) {
    const base = res && typeof res === 'object' ? res : { specification: {}, common: {}, tests: [] };
    const tests = Array.isArray(base.tests) ? base.tests.map((t) => this.withNormalizedMethodData(t)) : [];
    if (!tests.length) {
      return this.loadCompleteTestsFallback(specNo, base);
    }

    const needsEnrich = tests.some((t) => !this.hasMethodContent(t?.method_data));
    if (!needsEnrich) {
      return of({ ...base, tests });
    }

    const url =
      'qc/method.php?type=getMoaSpecTests&specification_no=' +
      encodeURIComponent(specNo) +
      '&include_method_data=1';
    return this.service.getJsonArray(url).pipe(
      switchMap((rows) => {
        const byId = new Map<string, any>((rows || []).map((r: any) => [String(r.id), r]));
        let merged = tests.map((t) => {
          const fb = byId.get(String(t.id));
          const fbMd = this.normalizeMethodData(fb?.method_data);
          if (this.hasMethodContent(fbMd)) {
            return { ...t, method_data: fbMd };
          }
          if (this.hasMethodContent(t?.method_data)) {
            return this.withNormalizedMethodData(t);
          }
          return t;
        });

        const stillMissing = merged.filter((t) => !this.hasMethodContent(t?.method_data));
        if (!stillMissing.length) {
          return of({ ...base, tests: merged });
        }

        // Prefer get_test_data (same path as the method editor) then getMoaMethodForSpecTest.
        const fetches = stillMissing.map((t) =>
          this.service.get('qc/method.php?type=get_test_data&id=' + encodeURIComponent(String(t.id))).pipe(
            map((row: any) => ({
              id: String(t.id),
              method_data: this.normalizeMethodData(row?.methods || row?.method_data),
            })),
            catchError(() =>
              this.service
                .get('qc/method.php?type=getMoaMethodForSpecTest&spec_test_id=' + encodeURIComponent(String(t.id)))
                .pipe(
                  map((row: any) => ({
                    id: String(t.id),
                    method_data: this.normalizeMethodData(row?.method_data || row?.methods),
                  })),
                  catchError(() => of({ id: String(t.id), method_data: null }))
                )
            )
          )
        );

        return forkJoin(fetches).pipe(
          map((pairs: Array<{ id: string; method_data: Record<string, unknown> | null }>) => {
            const mdById = new Map(pairs.map((p) => [p.id, p.method_data]));
            merged = merged.map((t) => {
              const md = mdById.get(String(t.id));
              if (md && this.hasMethodContent(md)) {
                return { ...t, method_data: md };
              }
              return this.withNormalizedMethodData(t);
            });
            return { ...base, tests: merged };
          })
        );
      }),
      catchError(() => of({ ...base, tests }))
    );
  }

  private loadCompleteTestsFallback(specNo: string, header: any) {
    const testsUrl =
      'qc/method.php?type=getMoaSpecTests&specification_no=' +
      encodeURIComponent(specNo) +
      '&include_method_data=1';
    const draftUrl =
      'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' +
      encodeURIComponent(specNo);
    const wrap = (tests: any[]) => ({
      specification: { ...(this.workflowRow || {}), ...(header?.specification || {}), specification_no: specNo },
      common: header?.common || {},
      tests: (Array.isArray(tests) ? tests : []).map((t) => this.withNormalizedMethodData(t)),
    });
    return this.service.getJsonArray(testsUrl).pipe(
      catchError(() => of([] as any[])),
      switchMap((rows) => {
        if (Array.isArray(rows) && rows.length) {
          return of(wrap(rows));
        }
        if (Array.isArray(this.specTests) && this.specTests.length) {
          return of(wrap(this.specTests));
        }
        return this.service.get(draftUrl).pipe(
          map((res: any) => {
            const tests = Array.isArray(res?.spectTests)
              ? res.spectTests
              : Array.isArray(res?.tests)
              ? res.tests
              : [];
            return wrap(tests);
          }),
          catchError(() => of(wrap([])))
        );
      })
    );
  }

  closeCompleteMethod(): void {
    this.isCompleteView = false;
    this.completeDocLoadFailed = false;
    this.completeDoc = { specification: {}, common: {}, tests: [] };
    this.cdr.markForCheck();
  }

  get completeTests(): any[] {
    return Array.isArray(this.completeDoc?.tests) ? this.completeDoc.tests : [];
  }

  /** Tests that have a method prepared (eligible to be sent for checking). */
  get preparedTestCount(): number {
    return this.completeTests.filter((t) => this.methodStatusKey(t) !== 'pending').length;
  }

  /** Tests still without a prepared method (block a complete submission). */
  get missingMethodCount(): number {
    return this.completeTests.filter((t) => this.methodStatusKey(t) === 'pending').length;
  }

  /** True when at least one prepared test is not yet in the workflow / approved. */
  get canSubmitComplete(): boolean {
    if (this.completeReadOnly) {
      return false;
    }
    return this.completeTests.some((t) => {
      const key = this.methodStatusKey(t);
      const wf = String(t['method_wf_status'] || '').toLowerCase();
      return key !== 'pending' && key !== 'completed' && wf !== 'pending_review' && wf !== 'pending_approval' && wf !== 'approved';
    });
  }

  hasText(value: unknown): boolean {
    if (value == null || Array.isArray(value)) {
      return false;
    }
    if (typeof value === 'object') {
      return false;
    }
    const s = String(value).trim();
    return s !== '' && s.toUpperCase() !== 'NA';
  }

  /** Coerce API method payloads (string JSON, [], Procedure vs procedure) into a stable object. */
  normalizeMethodData(raw: unknown): Record<string, unknown> {
    if (raw == null) {
      return {};
    }
    let md: any = raw;
    if (typeof md === 'string') {
      const trimmed = md.trim();
      if (!trimmed || trimmed === '[]' || trimmed === '{}') {
        return {};
      }
      try {
        md = JSON.parse(trimmed);
      } catch {
        return {};
      }
    }
    if (Array.isArray(md)) {
      return {};
    }
    if (!md || typeof md !== 'object') {
      return {};
    }
    const out: Record<string, unknown> = { ...md };
    const proc =
      this.hasText(out['procedure']) ? out['procedure'] : this.hasText(out['Procedure']) ? out['Procedure'] : '';
    out['procedure'] = proc;

    const arrKeys = [
      'testinginstruction',
      'equipment_instruments',
      'chemical_reagents',
      'glasswares',
      'balance',
      'volumetric_solutions',
      'phases',
      'Genral_Instruction',
      'Associative_Document',
      'Refrenced_Document',
      'defination',
    ];
    for (const k of arrKeys) {
      const v = out[k];
      if (typeof v === 'string') {
        try {
          const parsed = JSON.parse(v);
          out[k] = Array.isArray(parsed) ? parsed : [];
        } catch {
          out[k] = [];
        }
      } else if (!Array.isArray(v)) {
        out[k] = [];
      }
    }
    if (typeof out['hplc'] === 'string') {
      try {
        out['hplc'] = JSON.parse(out['hplc'] as string);
      } catch {
        out['hplc'] = {};
      }
    }
    return out;
  }

  private withNormalizedMethodData(test: any): any {
    if (!test || typeof test !== 'object') {
      return test;
    }
    const md = this.normalizeMethodData(test.method_data ?? test.methods);
    return { ...test, method_data: md };
  }

  methodProcedure(md: Record<string, unknown> | null | undefined): string {
    if (!md) {
      return '';
    }
    const v = md['procedure'] ?? md['Procedure'];
    return this.hasText(v) ? String(v) : '';
  }

  hasMethodContent(md: Record<string, unknown> | null | undefined): boolean {
    const normalized = this.normalizeMethodData(md);
    if (!normalized || !Object.keys(normalized).length) {
      return false;
    }
    const textKeys = [
      'procedure',
      'Procedure',
      'Safety',
      'chromatographic_conditions',
      'purpose',
      'Scope',
      'Test_Solutions',
      'standard_Solutions',
    ];
    for (const k of textKeys) {
      if (this.hasText(normalized[k])) {
        return true;
      }
    }
    const arrKeys = [
      'testinginstruction',
      'equipment_instruments',
      'chemical_reagents',
      'glasswares',
      'balance',
      'volumetric_solutions',
      'phases',
      'Genral_Instruction',
      'Associative_Document',
      'Refrenced_Document',
      'defination',
    ];
    for (const k of arrKeys) {
      const v = normalized[k];
      if (Array.isArray(v) && v.length) {
        return true;
      }
    }
    const hplc = normalized['hplc'];
    if (hplc && typeof hplc === 'object' && !Array.isArray(hplc)) {
      for (const k of ['instrumentParameterList', 'refractiveIndexList', 'methodParameterList', 'retentionTimeList']) {
        const list = (hplc as Record<string, unknown>)[k];
        if (Array.isArray(list) && list.length) {
          return true;
        }
      }
    }
    return false;
  }

  canShowMethodData(comp: Record<string, unknown>): boolean {
    const md = this.normalizeMethodData(comp?.['method_data'] ?? comp?.['methods']);
    if (this.hasMethodContent(md)) {
      return true;
    }
    return this.methodStatusKey(comp) !== 'pending';
  }

  /** Send the entire assembled method (all prepared tests) for Checking & Approval. */
  submitCompleteForChecking(): void {
    const specNo = String(this.completeDoc?.specification?.['specification_no'] || this.selectedSpec['specification_no'] || '');
    if (!specNo) {
      alertify.error('Specification number missing.');
      return;
    }
    if (this.missingMethodCount > 0) {
      alertify.error(
        this.missingMethodCount + ' test(s) still have no method prepared. Prepare all methods before sending the complete document.'
      );
      return;
    }
    if (!this.canSubmitComplete) {
      alertify.error('No prepared method is available to send, or it is already in the workflow.');
      return;
    }
    this.completeSubmitting = true;
    this.wf.submitSpecForReview(specNo).subscribe({
      next: (r: any) => {
        this.completeSubmitting = false;
        if (r?.status === 'success') {
          alertify.success('Complete method sent for Checking & Approval (' + (r.moved ?? 0) + ' test method(s)).');
          this.isCompleteView = false;
          this.loadSpecTests(specNo);
          this.cdr.markForCheck();
        } else {
          alertify.error('Could not send the complete method for checking.');
        }
        this.cdr.markForCheck();
      },
      error: () => {
        this.completeSubmitting = false;
        alertify.error('Network error while sending the complete method.');
        this.cdr.markForCheck();
      },
    });
  }

  private rowMatchesSearch(row: Record<string, unknown>, q: string): boolean {
    return Object.values(row).some((value) => {
      if (value == null || typeof value === 'object') {
        return false;
      }
      return String(value).toLowerCase().includes(q);
    });
  }
}
