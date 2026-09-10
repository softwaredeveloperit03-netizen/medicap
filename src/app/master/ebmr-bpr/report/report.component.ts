import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterFinalHtmlBmrComponent } from '../shared/master-final-html-bmr/master-final-html-bmr.component';
import { normalizeProductApprovalPage } from '../shared/product-approval.util';
import { normalizeSafetyPrecautions } from '../shared/safety-precautions.util';
import { normalizeGeneralInstructions } from '../shared/general-instructions.util';
import { normalizeDispensingPage } from '../shared/dispensing-store.util';

declare let alertify: any;

type ReportView = 'html' | 'pdf';

@Component({
  selector: 'app-ebmr-report',
  templateUrl: './report.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './report.component.css'],
})
export class ReportComponent implements OnInit {
  batchId = 0;
  loading = false;
  batch: any = null;
  viewMode: ReportView = 'html';
  @ViewChild(MasterFinalHtmlBmrComponent) htmlDoc?: MasterFinalHtmlBmrComponent;

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.batchId = Number(this.route.snapshot.paramMap.get('id') || 0);
    const mode = (this.route.snapshot.queryParamMap.get('mode') || 'html').toLowerCase();
    this.viewMode = mode === 'pdf' ? 'pdf' : 'html';
    this.load();
  }

  load(): void {
    if (!this.batchId) {
      alertify.error('Invalid batch');
      this.close();
      return;
    }
    this.loading = true;
    this.service.get('master/ebmr_bpr.php?type=getBatchReport&id=' + this.batchId).subscribe({
      next: (r: any) => {
        if (r && r.status === 'success' && r.batch) {
          this.batch = r.batch;
          this.batch.header = this.batch.header || {};
          this.batch.static = this.batch.static || {};
          this.batch.static.product_approval = normalizeProductApprovalPage(
            this.batch.static.product_approval,
            this.batch.header
          );
          this.batch.static.safety = normalizeSafetyPrecautions(this.batch.static.safety);
          this.batch.static.general_instructions = normalizeGeneralInstructions(
            this.batch.static.general_instructions
          );
          this.batch.static.dispensing = normalizeDispensingPage(this.batch.static.dispensing);
          (this.batch.steps || []).forEach((s: any) => {
            s.template = s.template || {};
            s.data = s.data || {};
            s.corrections = s.corrections || [];
          });
        } else {
          alertify.error((r && r.message) || 'Batch not found');
          this.close();
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load completed BMR');
      },
    });
  }

  get recordKind(): 'eBMR' | 'eBPR' {
    return this.batch?.record_type === 'eBPR' ? 'eBPR' : 'eBMR';
  }

  get recordDocLabel(): string {
    return this.recordKind === 'eBPR' ? 'Batch Packing Record' : 'Batch Manufacturing Record';
  }

  setView(mode: ReportView): void {
    this.viewMode = mode;
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { mode },
      queryParamsHandling: 'merge',
    });
  }

  printReport(): void {
    if (this.htmlDoc) {
      this.htmlDoc.printDoc();
      return;
    }
    this.setView('pdf');
  }

  close(): void {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) {
      this.router.navigateByUrl(returnUrl);
      return;
    }
    const closeRoute = this.route.snapshot.data?.['closeRoute'] as string;
    if (closeRoute) {
      this.router.navigate([closeRoute]);
      return;
    }
    this.router.navigate(['/fproduction/ebmr/completed']);
  }
}
