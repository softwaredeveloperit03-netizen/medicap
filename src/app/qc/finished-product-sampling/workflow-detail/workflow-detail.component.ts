import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  FPS_FORM_TITLE, FPS_API, emptyResultsData, mergeTestRows, parseFpsResponse
} from '../fps.constants';
declare let alertify: any;

@Component({
  selector: 'app-fps-workflow-detail',
  templateUrl: './workflow-detail.component.html',
  styleUrls: ['./workflow-detail.component.css']
})
export class WorkflowDetailComponent implements OnInit {
  readonly formTitle = FPS_FORM_TITLE;

  step = '';
  mode = '';
  id = '';
  loading = true;
  processing = false;
  record: any = null;
  workflowLog: any[] = [];
  testRows: any[] = [];

  remark = '';
  labNumber = '';
  receiveRemark = '';
  atrReference = '';
  testSpecRef = '';
  resultsData = emptyResultsData();
  coaRemark = '';

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
    this.service.get(FPS_API + 'type=getRecordById&id=' + this.id).subscribe((res: any) => {
      this.record = res.record;
      this.workflowLog = res.workflow_log || [];
      if (this.record) {
        this.testRows = mergeTestRows(this.record.test_rows);
        this.labNumber = this.record.lab_number || '';
        this.atrReference = this.record.atr_reference || '';
        this.testSpecRef = this.record.test_spec_ref || '';
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
    this.service.get(FPS_API + 'type=getNextLabNumber').subscribe((res: any) => {
      this.labNumber = res.lab_number || '';
    });
  }

  isViewOnly(): boolean {
    return this.mode === 'view' || !this.step || this.step === 'view';
  }

  backRoute(): string {
    if (this.isViewOnly()) return '/qc/finished-product-sampling/log';
    return '/qc/finished-product-sampling/' + this.step;
  }

  downloadPdf() {
    if (!this.record?.id) {
      return;
    }
    this.service.open('qc/finished_product_sampling_pdf.php?id=' + this.record.id);
  }

  editableRequired(): boolean {
    return this.step === 'qc-manager' && !this.isViewOnly();
  }

  editableSampled(): boolean {
    return this.step === 'production' && !this.isViewOnly();
  }

  runAction(type: string, payload: any, successMsg: string) {
    this.processing = true;
    this.service.postTextResponse(FPS_API + 'type=' + type, JSON.stringify(payload))
      .subscribe((raw: string) => {
        this.processing = false;
        const res = parseFpsResponse(raw);
        if (res?.status === 'success') {
          alertify.success(res.message || successMsg);
          this.router.navigate([this.backRoute()]);
        } else {
          alertify.error(res?.message || res?.status || 'Action failed');
        }
      }, () => { this.processing = false; alertify.error('Action failed'); });
  }

  approveQcManager() {
    this.runAction('approveQcManager', {
      id: parseInt(this.id, 10),
      test_rows: this.testRows,
      remark: this.remark
    }, 'QC Manager approval recorded.');
  }

  approveQa() {
    this.runAction('approveQa', {
      id: parseInt(this.id, 10),
      remark: this.remark
    }, 'QA approval recorded.');
  }

  submitProduction() {
    this.runAction('submitProduction', {
      id: parseInt(this.id, 10),
      test_rows: this.testRows,
      atr_reference: this.atrReference,
      remark: this.remark
    }, 'Production sampling submitted to QC Lab.');
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
    }, 'Sample received and logged in QC Sample Receiving Log.');
  }

  submitResults() {
    this.runAction('submitResults', {
      id: parseInt(this.id, 10),
      test_spec_ref: this.testSpecRef,
      results_data: this.resultsData,
      remark: this.resultsData.results_summary
    }, 'Test results submitted for C of A approval.');
  }

  approveCoa() {
    this.runAction('approveCoa', {
      id: parseInt(this.id, 10),
      coa_remark: this.coaRemark
    }, 'Approved — Test Specification Form is now Certificate of Analysis.');
  }
}
