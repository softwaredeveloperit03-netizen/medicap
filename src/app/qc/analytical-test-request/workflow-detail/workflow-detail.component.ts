import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  ATR_BINDER_OPTIONS, ATR_DEPT_FORWARD_OPTIONS
} from '../atr.constants';
declare let alertify: any;

@Component({
  selector: 'app-atr-workflow-detail',
  templateUrl: './workflow-detail.component.html',
  styleUrls: ['./workflow-detail.component.css']
})
export class WorkflowDetailComponent implements OnInit {
  readonly binderOptions = ATR_BINDER_OPTIONS;
  readonly deptOptions = ATR_DEPT_FORWARD_OPTIONS;

  step = '';
  mode = '';
  id = '';
  loading = true;
  processing = false;
  req: any = null;
  workflowLog: any[] = [];

  labSampleNumber = '';
  mfgDate = '';
  remark = '';
  altApproved = true;

  analystRemark = '';
  alternativeMethod = 'No';
  alternativeJustification = '';
  binderForward: string[] = [];
  binderForwardOther = '';
  deptForward: string[] = [];
  deptForwardOther = '';

  constructor(
    public service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    this.route.data.subscribe(data => {
      this.step = data['step'] || data['mode'] || 'view';
      this.mode = data['mode'] || '';
    });
    this.id = this.route.snapshot.paramMap.get('id') || '';
    if (this.id) {
      this.loadRequest();
    }
  }

  loadRequest() {
    this.loading = true;
    this.service.get('qc/analytical_test_request.php?type=getRequestById&id=' + this.id).subscribe((res: any) => {
      this.req = res.request;
      this.workflowLog = res.workflow_log || [];
      if (this.req) {
        this.mfgDate = this.req.mfg_date ? String(this.req.mfg_date).substring(0, 10) : '';
        this.labSampleNumber = this.req.lab_sample_number || '';
        this.analystRemark = this.req.analyst_remark || '';
        this.alternativeMethod = this.req.alternative_method || 'No';
        this.alternativeJustification = this.req.alternative_justification || '';
        this.binderForward = Array.isArray(this.req.binder_forward) ? [...this.req.binder_forward] : [];
        this.binderForwardOther = this.req.binder_forward_other || '';
        this.deptForward = Array.isArray(this.req.dept_forward) ? [...this.req.dept_forward] : [];
        this.deptForwardOther = this.req.dept_forward_other || '';
      }
      this.loading = false;
      if (this.step === 'lab-receive' && !this.labSampleNumber) {
        this.getNextLabSampleNo();
      }
    }, () => { this.loading = false; });
  }

  getNextLabSampleNo() {
    this.service.get('qc/analytical_test_request.php?type=getNextLabSampleNumber').subscribe((res: any) => {
      this.labSampleNumber = res.lab_sample_number || '';
    });
  }

  isViewOnly(): boolean {
    return this.mode === 'view' || !this.step || this.step === 'view';
  }

  backRoute(): string {
    if (this.isViewOnly()) return '/qc/analytical-test-request/log';
    return '/qc/analytical-test-request/' + this.step;
  }

  downloadPdf() {
    if (!this.req?.id) {
      return;
    }
    this.service.open('qc/analytical_test_request_pdf.php?id=' + this.req.id);
  }

  toggleArr(arr: string[], val: string) {
    const i = arr.indexOf(val);
    if (i >= 0) arr.splice(i, 1); else arr.push(val);
  }

  inArr(arr: string[], val: string): boolean {
    return arr.indexOf(val) >= 0;
  }

  runAction(type: string, payload: any, successMsg: string) {
    this.processing = true;
    this.service.postJson('qc/analytical_test_request.php?type=' + type, JSON.stringify(payload))
      .subscribe((res: any) => {
        this.processing = false;
        if (res && res.status === 'success') {
          alertify.success(successMsg);
          this.router.navigate([this.backRoute()]);
        } else {
          alertify.error(res?.message || res?.status || 'Action failed');
        }
      }, () => { this.processing = false; alertify.error('Action failed'); });
  }

  qaVerify() {
    this.runAction('qaVerify', { id: this.id, mfg_date: this.mfgDate, remark: this.remark }, 'QA verification completed.');
  }

  productionVerify() {
    this.runAction('productionVerify', { id: this.id, remark: this.remark }, 'Production verification completed.');
  }

  labReceive() {
    if (!this.labSampleNumber) {
      alertify.error('Lab Sample Number is required.');
      return;
    }
    this.runAction('labReceive', { id: this.id, lab_sample_number: this.labSampleNumber }, 'Sample received and logged.');
  }

  submitAnalyst() {
    this.runAction('submitAnalyst', {
      id: this.id,
      analyst_tests: {},
      analyst_remark: this.analystRemark,
      alternative_method: this.alternativeMethod,
      alternative_justification: this.alternativeJustification,
      binder_forward: this.binderForward,
      binder_forward_other: this.binderForwardOther,
      dept_forward: this.deptForward,
      dept_forward_other: this.deptForwardOther
    }, 'Analyst section submitted.');
  }

  approveAltMethod() {
    this.runAction('approveAltMethod', { id: this.id, approved: this.altApproved ? 'Yes' : 'No', remark: this.remark },
      this.altApproved ? 'Alternative method approved.' : 'Returned to analyst.');
  }

  labManagerApprove() {
    this.runAction('labManagerApprove', { id: this.id, remark: this.remark }, 'Lab Manager approval completed.');
  }

  qaDisposition() {
    this.runAction('qaDisposition', { id: this.id, remark: this.remark }, 'QA disposition completed. Request closed.');
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
