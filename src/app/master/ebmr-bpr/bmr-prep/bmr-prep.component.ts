import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import { bmrForLabel, genericBmrRowHint, GENERIC_BMR_DEFINITION } from '../shared/bmr-for-labels';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-bmr-prep',
  templateUrl: './bmr-prep.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './bmr-prep.component.css'],
})
export class BmrPrepComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  dosageForms: string[] = [];
  dosageFilter = '';
  search = '';

  allAccess = true;
  userDept = '';

  /** Per-config BMR For selection (before / during draft) */
  bmrForByConfig: { [configId: number]: 'Generic' | 'Product' } = {};
  productCodeByConfig: { [configId: number]: string } = {};
  productsByConfig: { [configId: number]: any[] } = {};
  productPreview: { [configId: number]: any } = {};

  readonly bmrForLabel = bmrForLabel;
  readonly genericBmrRowHint = genericBmrRowHint;
  readonly genericBmrDefinition = GENERIC_BMR_DEFINITION;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private esign: EsignService
  ) {}

  private builderBase(): string {
    return (this.route.snapshot.data?.['builderBase'] as string) || '/master/ebmr-bpr/builder';
  }

  ngOnInit(): void {
    this.userDept = localStorage.getItem('department') || localStorage.getItem('emp_dept') || '';
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => (this.dosageForms = Array.isArray(r) ? r : []));
    this.load();
  }

  load(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getPrepConfigs';
    if (this.dosageFilter) url += '&dosage_form=' + encodeURIComponent(this.dosageFilter);
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.rows = Array.isArray(r) ? r : [];
        this.syncRowSelections();
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load configurations');
      },
    });
  }

  syncRowSelections(): void {
    for (const r of this.rows) {
      if (!this.bmrForByConfig[r.id]) {
        this.bmrForByConfig[r.id] = r.bmr_for === 'Product' ? 'Product' : 'Generic';
      }
      if (r.draft_product_code && !this.productCodeByConfig[r.id]) {
        this.productCodeByConfig[r.id] = r.draft_product_code;
      }
      if (this.bmrForByConfig[r.id] === 'Product') {
        this.ensureProductsLoaded(r);
        if (this.productCodeByConfig[r.id]) {
          this.loadProductPreview(r);
        }
      }
    }
  }

  get filtered(): any[] {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.rows;
    return this.rows.filter((r) => [r.bmr_no, r.dosage_form, r.process_type, r.title].join(' ').toLowerCase().includes(q));
  }

  canPrepare(m: any): boolean {
    return this.allAccess || (!!m && this.userDept === m?.prepared?.department);
  }
  canReview(m: any): boolean {
    return this.allAccess || (!!m && Array.isArray(m?.reviewers) && m.reviewers.some((x: any) => x.department === this.userDept));
  }
  canProd(): boolean {
    return this.allAccess || this.userDept === 'Production';
  }
  canQA(m: any): boolean {
    return this.allAccess || (!!m && this.userDept === m?.approver?.department);
  }

  status(r: any): string {
    return r.profile_status || 'Not started';
  }
  isStarted(r: any): boolean {
    return !!r.profile_id;
  }

  isReviewCellEmpty(r: any): boolean {
    const s = this.status(r);
    if (s === 'Approved') return false;
    if (this.canReview(r.matrix) && s === 'Under Review') return false;
    if (this.canProd() && s === 'Reviewed') return false;
    if (this.canQA(r.matrix) && s === 'Production Approved') return false;
    if (this.isStarted(r) && (s === 'Under Review' || s === 'Reviewed' || s === 'Production Approved')) return false;
    return true;
  }

  statusClass(s: string): string {
    switch ((s || '').toLowerCase()) {
      case 'approved':
        return 'eb-badge-ok';
      case 'production approved':
        return 'eb-badge-type';
      case 'reviewed':
        return 'eb-badge-type';
      case 'under review':
        return 'eb-badge-warn';
      case 'rejected':
        return 'eb-badge-crit';
      case 'draft':
        return 'eb-badge-muted';
      default:
        return 'eb-badge-muted';
    }
  }

  getBmrFor(r: any): 'Generic' | 'Product' {
    return this.bmrForByConfig[r.id] || 'Generic';
  }

  onBmrForChange(r: any, value: 'Generic' | 'Product'): void {
    this.bmrForByConfig[r.id] = value;
    if (value === 'Product') {
      this.ensureProductsLoaded(r);
    } else {
      this.productCodeByConfig[r.id] = '';
      delete this.productPreview[r.id];
    }
  }

  onProductChange(r: any, code: string): void {
    this.productCodeByConfig[r.id] = code;
    if (code) {
      this.loadProductPreview(r);
    } else {
      delete this.productPreview[r.id];
    }
  }

  ensureProductsLoaded(r: any): void {
    if (!r?.id || this.productsByConfig[r.id]) return;
    const df = encodeURIComponent(r.dosage_form || '');
    this.service.get('master/ebmr_bpr.php?type=getProductsForBinding&dosage_form=' + df).subscribe({
      next: (list: any) => {
        this.productsByConfig[r.id] = Array.isArray(list) ? list : [];
      },
      error: () => {
        this.productsByConfig[r.id] = [];
      },
    });
  }

  loadProductPreview(r: any): void {
    const code = this.productCodeByConfig[r.id];
    if (!code) return;
    this.service.get('master/ebmr_bpr.php?type=getProductForBmrPrep&product_code=' + encodeURIComponent(code)).subscribe({
      next: (res: any) => {
        if (res?.status === 'success') {
          this.productPreview[r.id] = res.product;
        }
      },
    });
  }

  openBuilder(r: any): void {
    const bmrFor = this.getBmrFor(r);
    const productCode = this.productCodeByConfig[r.id] || '';

    if (bmrFor === 'Product' && !productCode && !r.profile_id) {
      alertify.error('Select a product for Product-specific BMR.');
      return;
    }

    const navigate = (pid: number) => this.router.navigate([this.builderBase(), pid]);

    if (r.profile_id) {
      navigate(r.profile_id);
      return;
    }

    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:bmr_prep',
        recordRef: r.bmr_no,
        detail: 'Start BMR master preparation — ' + r.bmr_no + ' (' + bmrFor + ')',
      })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=proceedBmrPrep',
            JSON.stringify({ config_id: r.id, bmr_for: bmrFor, product_code: bmrFor === 'Product' ? productCode : '' })
          )
          .subscribe((res: any) => {
            if (res && res.status === 'success') {
              navigate(res.profile_id);
              this.load();
            } else {
              alertify.error((res && res.message) || 'Could not open BMR');
            }
          });
      });
  }

  workflow(r: any, action: string): void {
    if (!r.profile_id) {
      alertify.error('Start the draft first');
      return;
    }
    const map: any = {
      submit_review: { meaning: 'Prepared By', label: 'Submit for Review', reason: false },
      review: { meaning: 'Reviewed By', label: 'Checking & Review', reason: true },
      prod_approve: { meaning: 'Approved By', label: 'Production Approval', reason: true },
      qa_approve: { meaning: 'Approved By', label: 'Quality Assurance Approval', reason: true },
      reject: { meaning: 'Reviewed By', label: 'Reject / Send back', reason: true },
    };
    const cfg = map[action];
    this.esign
      .request({
        meaning: cfg.meaning,
        module: 'master:bmr_prep',
        recordRef: r.bmr_no,
        detail: cfg.label + ' — ' + r.bmr_no,
        title: cfg.label + ' — ' + r.bmr_no,
        requireReason: cfg.reason,
        reasonLabel: action === 'reject' ? 'Reason / send-back note' : 'Remark',
        confirmLabel: 'Sign & ' + cfg.label,
      })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post('master/ebmr_bpr.php?type=profileWorkflow', JSON.stringify({ profile_id: r.profile_id, action, remark: sig.reason || '' }))
          .subscribe((res: any) => {
            if (res && res.status === 'success') {
              alertify.success(cfg.label + ' done — ' + res.new_status);
              this.load();
            } else {
              alertify.error((res && res.message) || 'Action failed');
            }
          });
      });
  }

  close(): void {
    const closeRoute = this.route.snapshot.data?.['closeRoute'] || '/master/ebmr-bpr';
    this.router.navigate([closeRoute]);
  }
}
