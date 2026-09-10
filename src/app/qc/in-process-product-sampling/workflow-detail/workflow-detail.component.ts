import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  IPPS_FORM_TITLE, IPPS_API, emptyTestingData, emptyResultsData, parseIppsResponse
} from '../ipps.constants';
declare let alertify: any;

@Component({
  selector: 'app-ipps-workflow-detail',
  templateUrl: './workflow-detail.component.html',
  styleUrls: ['./workflow-detail.component.css']
})
export class WorkflowDetailComponent implements OnInit {
  readonly formTitle = IPPS_FORM_TITLE;

  step = '';
  mode = '';
  id = '';
  loading = true;
  processing = false;
  record: any = null;
  workflowLog: any[] = [];

  labNumber = '';
  receiveRemark = '';
  testSpecRef = '';
  testingData = emptyTestingData();
  reviewRemark = '';
  resultsData = emptyResultsData();
  approvalRemark = '';

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
      this.loadRecord();
    }
  }

  loadRecord() {
    this.loading = true;
    this.service.get(IPPS_API + 'type=getRecordById&id=' + this.id).subscribe((res: any) => {
      this.record = res.record;
      this.workflowLog = res.workflow_log || [];
      if (this.record) {
        this.labNumber = this.record.lab_number || '';
        this.testSpecRef = this.record.test_spec_ref || '';
        if (this.record.testing_data && Object.keys(this.record.testing_data).length) {
          this.testingData = { ...emptyTestingData(), ...this.record.testing_data };
        }
        if (this.record.results_data && Object.keys(this.record.results_data).length) {
          this.resultsData = { ...emptyResultsData(), ...this.record.results_data };
        }
        if (this.step === 'qc-receive' && !this.labNumber) {
          this.getNextLabNumber();
        }
      }
      this.loading = false;
    }, () => { this.loading = false; });
  }

  getNextLabNumber() {
    this.service.get(IPPS_API + 'type=getNextLabNumber').subscribe((res: any) => {
      this.labNumber = res.lab_number || '';
    });
  }

  calcBlendWeight() {
    const withSample = parseFloat(this.testingData.blend_bottle_with_sample_g);
    const empty = parseFloat(this.testingData.blend_empty_bottle_g);
    if (!isNaN(withSample) && !isNaN(empty)) {
      this.testingData.blend_sample_weight_g = (withSample - empty).toFixed(4);
    }
  }

  isViewOnly(): boolean {
    return this.mode === 'view' || !this.step || this.step === 'view';
  }

  backRoute(): string {
    if (this.isViewOnly()) return '/qc/in-process-product-sampling/log';
    return '/qc/in-process-product-sampling/' + this.step;
  }

  downloadPdf() {
    if (!this.record?.id) {
      return;
    }
    this.service.open('qc/in_process_product_sampling_pdf.php?id=' + this.record.id);
  }

  runAction(type: string, payload: any, successMsg: string) {
    this.processing = true;
    this.service.postTextResponse(IPPS_API + 'type=' + type, JSON.stringify(payload))
      .subscribe((raw: string) => {
        this.processing = false;
        const res = parseIppsResponse(raw);
        if (res?.status === 'success') {
          alertify.success(successMsg);
          this.router.navigate([this.backRoute()]);
        } else {
          alertify.error(res?.message || res?.status || 'Action failed');
        }
      }, () => { this.processing = false; alertify.error('Action failed'); });
  }

  qcReceive() {
    if (!this.labNumber) {
      alertify.error('QC lab number is required.');
      return;
    }
    this.runAction('qcReceive', {
      id: parseInt(this.id, 10),
      lab_number: this.labNumber,
      receive_remark: this.receiveRemark
    }, 'Sample received and logged.');
  }

  submitTesting() {
    this.calcBlendWeight();
    this.runAction('submitTesting', {
      id: parseInt(this.id, 10),
      test_spec_ref: this.testSpecRef,
      testing_data: this.testingData,
      remark: this.testingData.test_method_notes
    }, 'Testing step completed.');
  }

  submitQcReview() {
    this.runAction('submitQcReview', {
      id: parseInt(this.id, 10),
      review_remark: this.reviewRemark
    }, 'QC review completed.');
  }

  submitAnalystEntry() {
    this.runAction('submitAnalystEntry', {
      id: parseInt(this.id, 10),
      results_data: this.resultsData,
      remark: this.resultsData.results_summary
    }, 'Results entered.');
  }

  submitQcApproval() {
    this.runAction('submitQcApproval', {
      id: parseInt(this.id, 10),
      approval_remark: this.approvalRemark
    }, 'QC management approval recorded. Record closed.');
  }
}
