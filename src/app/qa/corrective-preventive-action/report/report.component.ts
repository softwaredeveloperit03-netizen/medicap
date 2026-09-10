import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  CAPA_CLASSIFICATIONS,
  CAPA_SOURCES,
  DEPT_CODES,
  EFFECTIVENESS_CHECK_TYPES,
  EFFECTIVE_DATE,
  FORM_B_NO,
  REVISION_NO,
  SOP_REF,
  defaultFromDate,
  defaultToDate,
  emptyCapaForm,
  getEmpDisplayName,
  stampNow,
  statusClass,
  statusLabel,
} from '../capa070.utils';

declare let alertify: any;

@Component({
  selector: 'app-capa070-report',
  templateUrl: './report.component.html',
  styleUrls: ['../capa070.shared.css'],
  providers: [DatePipe],
})
export class ReportComponent implements OnInit {
  formNo = FORM_B_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  capaSources = CAPA_SOURCES;
  capaClassifications = CAPA_CLASSIFICATIONS;
  effectivenessTypes = EFFECTIVENESS_CHECK_TYPES;
  deptCodes = DEPT_CODES;

  isNew = false;
  isLog = false;
  isView = false;
  isEdit = false;
  editId = 0;

  form: any = emptyCapaForm();
  prepareBy = '';
  prepareDate = '';

  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    const view = this.route.snapshot.data['view'];
    this.isNew = view === 'new';
    this.isLog = view === 'log';
    this.isView = view === 'view';
    this.isEdit = view === 'edit';

    if (this.isView || this.isEdit) {
      this.editId = +this.route.snapshot.paramMap.get('id') || 0;
      this.loadRecord();
      return;
    }
    if (this.isLog) {
      this.getLog();
      return;
    }

    this.form = emptyCapaForm();
    this.form.initiation_date = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.prepareBy = getEmpDisplayName();
    this.prepareDate = this.form.initiation_date;
  }

  loadRecord(): void {
    if (!this.editId) {
      alertify.error('Invalid CAPA reference');
      this.router.navigate(['/qa/corrective-preventive-action/report/log']);
      return;
    }
    this.service
      .get('qa/correctivePreventiveAction.php?type=getCapaById&id=' + this.editId)
      .subscribe((response: any) => {
        if (!response?.id) {
          alertify.error('Record not found');
          this.router.navigate(['/qa/corrective-preventive-action/report/log']);
          return;
        }
        this.selectedRecord = response;
        if (this.isEdit) {
          if (response.status !== 'rejected') {
            alertify.error('Only rejected CAPAs can be edited and resubmitted');
            this.router.navigate(['/qa/corrective-preventive-action/report/log']);
            return;
          }
          this.form = { ...emptyCapaForm(), ...response };
          this.form.corrective_effectiveness_types = this.parseTypes(response.corrective_effectiveness_types);
          this.form.preventative_effectiveness_types = this.parseTypes(response.preventative_effectiveness_types);
          this.prepareBy = response.prepare_by || getEmpDisplayName();
          this.prepareDate = response.prepare_date || this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
        }
      });
  }

  parseTypes(value: any): string[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (!value) {
      return [];
    }
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  isTypeChecked(list: string[], type: string): boolean {
    return Array.isArray(list) && list.indexOf(type) !== -1;
  }

  toggleEffType(list: string[], type: string, event: any): void {
    const checked = !!event?.target?.checked;
    const idx = list.indexOf(type);
    if (checked && idx === -1) {
      list.push(type);
    } else if (!checked && idx !== -1) {
      list.splice(idx, 1);
    }
  }

  stampPrepareBy(): void {
    this.prepareBy = stampNow(this.datePipe);
    this.prepareDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  getLog(): void {
    this.service
      .get(
        'qa/correctivePreventiveAction.php?type=getCapaReportLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  viewRecord(record: any): void {
    this.router.navigate(['/qa/corrective-preventive-action/report/view', record.id]);
  }

  editRejected(record: any): void {
    this.router.navigate(['/qa/corrective-preventive-action/report/edit', record.id]);
  }

  canEdit(record: any): boolean {
    return record?.status === 'rejected';
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  getStatusLabel(status: string): string {
    return statusLabel(status);
  }

  save(form: NgForm): void {
    if (form.invalid || !this.form.capa_document_title?.trim() || !this.form.description_of_event?.trim()) {
      alertify.error('Please fill all required fields (Title and Description of Event)');
      return;
    }

    const payload: any = {
      ...this.form,
      prepare_by: this.prepareBy,
      prepare_date: this.prepareDate,
    };
    if (this.isEdit && this.editId) {
      payload.id = this.editId;
    }

    this.service
      .post('qa/correctivePreventiveAction.php?type=saveCapaReport', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success(
            this.isEdit
              ? 'CAPA resubmitted to Department Head for approval'
              : 'CAPA report saved and sent to Department Head for approval'
          );
          this.router.navigate(['/qa/corrective-preventive-action/report/log']);
        } else {
          alertify.error(response?.status || response?.message || 'Failed to save CAPA report');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/correctivePreventiveAction.php?type=downloadCapaReportPdf&id=' + id);
  }
}
