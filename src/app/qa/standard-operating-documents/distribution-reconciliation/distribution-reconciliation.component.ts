import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  DepartmentDistributionRow,
  FORM_A_NO,
  RevReconciliation,
  SOP_REF,
  REVISION_NO,
  EFFECTIVE_DATE,
  createDefaultDepartmentRows,
  createDefaultReconciliations,
  defaultFromDate,
  defaultToDate,
} from '../sod.utils';

declare let alertify: any;

@Component({
  selector: 'app-distribution-reconciliation',
  templateUrl: './distribution-reconciliation.component.html',
  styleUrls: ['../sod.shared.css'],
  providers: [DatePipe],
})
export class DistributionReconciliationComponent implements OnInit {
  formNo = FORM_A_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;

  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  sopNumber = '';
  departmentRows: DepartmentDistributionRow[] = createDefaultDepartmentRows();
  reconciliations: RevReconciliation[] = createDefaultReconciliations();

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
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) {
      this.getLog();
    }
  }

  getLog(): void {
    this.service
      .get(
        'qa/sodDocuments.php?type=getDistributionReconciliationLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.service
      .get('qa/sodDocuments.php?type=getDistributionReconciliationById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  save(form: NgForm): void {
    if (form.invalid || !this.sopNumber.trim()) {
      alertify.error('Please enter SOP Number');
      return;
    }

    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      sop_number: this.sopNumber,
      department_rows: this.departmentRows,
      reconciliations: this.reconciliations,
    };

    this.service
      .post('qa/sodDocuments.php?type=saveDistributionReconciliation', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Distribution and reconciliation form saved');
          this.router.navigate(['/qa/standard-operating-documents/distribution/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/sodDocuments.php?type=downloadDistributionReconciliationForm&id=' + id);
  }
}
