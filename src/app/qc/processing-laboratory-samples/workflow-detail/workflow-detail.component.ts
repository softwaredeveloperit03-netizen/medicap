import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  PLS_FORM_ID, PLS_SECTION_A_ROWS, PLS_INSPECTION_ROWS, PLS_LAB_NUMBER_SCHEMES,
  emptySectionA, emptySectionB, emptySectionC, emptySectionD, emptyRetestColumn, PLS_API, parsePlsResponse
} from '../pls.constants';
declare let alertify: any;

@Component({
  selector: 'app-pls-workflow-detail',
  templateUrl: './workflow-detail.component.html',
  styleUrls: ['./workflow-detail.component.css']
})
export class WorkflowDetailComponent implements OnInit {
  readonly formId = PLS_FORM_ID;
  readonly sectionARows = PLS_SECTION_A_ROWS;
  readonly inspectionRows = PLS_INSPECTION_ROWS;
  readonly labSchemes = PLS_LAB_NUMBER_SCHEMES;

  step = '';
  mode = '';
  id = '';
  loading = true;
  processing = false;
  sample: any = null;
  workflowLog: any[] = [];
  remark = '';

  sectionA: Record<string, any> = emptySectionA();
  sectionB: Record<string, any> = emptySectionB();
  sectionC: Record<string, any> = emptySectionC();
  sectionD: Record<string, any>[] = emptySectionD();
  isRetest = 'No';

  labNumber = '';
  labNumberScheme = 'qc';
  controlledSubstance = 'No';

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
      this.loadSample();
    }
  }

  loadSample() {
    this.loading = true;
    this.service.get(PLS_API + 'type=getSampleById&id=' + this.id).subscribe((res: any) => {
      this.sample = res.sample;
      this.workflowLog = res.workflow_log || [];
      if (this.sample) {
        if (this.sample.section_a && Object.keys(this.sample.section_a).length) {
          this.sectionA = { ...emptySectionA(), ...this.sample.section_a };
        }
        if (this.sample.section_b && Object.keys(this.sample.section_b).length) {
          this.sectionB = { ...emptySectionB(), ...this.sample.section_b };
          if (this.sample.section_b.inspections) {
            this.sectionB.inspections = { ...emptySectionB().inspections, ...this.sample.section_b.inspections };
          }
        }
        if (this.sample.section_c && Object.keys(this.sample.section_c).length) {
          this.sectionC = { ...emptySectionC(), ...this.sample.section_c };
        }
        if (Array.isArray(this.sample.section_d) && this.sample.section_d.length) {
          this.sectionD = this.sample.section_d;
        }
        this.isRetest = this.sample.is_retest || 'No';
        this.labNumber = this.sample.lab_number || '';
        this.labNumberScheme = this.sample.lab_number_scheme || 'qc';
        this.controlledSubstance = this.sample.controlled_substance || 'No';
        if (this.step === 'lab-receive' && !this.labNumber) {
          this.getNextLabNumber();
        }
        if (this.step === 'section-b' && !this.sectionB.sampled_by) {
          this.sectionB.sampled_by = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
          this.sectionB.sampled_date = new Date().toISOString().substring(0, 10);
        }
        if (this.step === 'section-c' && !this.sectionC.qc_released_by) {
          this.sectionC.qc_released_by = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
          this.sectionC.qc_released_date = new Date().toISOString().substring(0, 10);
        }
      }
      this.loading = false;
    }, () => { this.loading = false; });
  }

  getNextLabNumber() {
    this.service.get(
      PLS_API + 'type=getNextLabNumber'
      + '&scheme=' + encodeURIComponent(this.labNumberScheme)
      + '&controlled=' + encodeURIComponent(this.controlledSubstance)
    ).subscribe((res: any) => {
      this.labNumber = res.lab_number || '';
    });
  }

  isViewOnly(): boolean {
    return this.mode === 'view' || !this.step || this.step === 'view';
  }

  backRoute(): string {
    if (this.isViewOnly()) return '/qc/processing-laboratory-samples/log';
    return '/qc/processing-laboratory-samples/' + this.step;
  }

  downloadPdf() {
    if (!this.sample?.id) {
      return;
    }
    this.service.open('qc/processing_laboratory_samples_pdf.php?id=' + this.sample.id);
  }

  runAction(type: string, payload: any, successMsg: string) {
    this.processing = true;
    this.service.postTextResponse(PLS_API + 'type=' + type, JSON.stringify(payload))
      .subscribe((raw: string) => {
        this.processing = false;
        const res = parsePlsResponse(raw);
        if (res && res.status === 'success') {
          alertify.success(successMsg);
          this.router.navigate([this.backRoute()]);
        } else {
          alertify.error(res?.message || res?.status || 'Action failed');
        }
      }, (err) => {
        this.processing = false;
        alertify.error(err?.error?.message || err?.message || 'Action failed');
      });
  }

  submitSectionA() {
    this.runAction('submitSectionA', { id: this.id, section_a: this.sectionA, remark: this.remark }, 'Section A submitted.');
  }

  submitSectionB() {
    this.runAction('submitSectionB', { id: this.id, section_b: this.sectionB, remark: this.remark }, 'Section B submitted.');
  }

  labReceive() {
    if (!this.labNumber) {
      alertify.error('Lab number is required.');
      return;
    }
    this.runAction('labReceive', {
      id: this.id,
      lab_number: this.labNumber,
      lab_number_scheme: this.labNumberScheme,
      controlled_substance: this.controlledSubstance,
      remark: this.remark
    }, 'Sample received and logged.');
  }

  submitSectionC() {
    this.runAction('submitSectionC', {
      id: this.id,
      section_c: this.sectionC,
      section_d: this.isRetest === 'Yes' ? this.sectionD : [],
      is_retest: this.isRetest,
      remark: this.remark
    }, 'Section C completed. Record closed.');
  }

  addRetestColumn() {
    if (this.sectionD.length >= 6) return;
    this.sectionD.push(emptyRetestColumn());
  }

  removeRetestColumn(idx: number) {
    if (this.sectionD.length <= 1) return;
    this.sectionD.splice(idx, 1);
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
