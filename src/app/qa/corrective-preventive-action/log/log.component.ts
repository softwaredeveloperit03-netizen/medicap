import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_A_NO,
  REVISION_NO,
  SOP_REF,
  defaultFromDate,
  defaultToDate,
  statusClass,
  statusLabel,
} from '../capa070.utils';

@Component({
  selector: 'app-capa070-log',
  templateUrl: './log.component.html',
  styleUrls: ['../capa070.shared.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  formNo = FORM_A_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;

  results: any[] = [];
  selectedRecord: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  maxDate = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.getLog();
  }

  getLog(): void {
    this.service
      .get(
        'qa/correctivePreventiveAction.php?type=getCapaTrackingLog&from_date=' +
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
      .get('qa/correctivePreventiveAction.php?type=getCapaById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  getStatusLabel(status: string): string {
    return statusLabel(status);
  }

  downloadLog(): void {
    this.service.open(
      'qa/correctivePreventiveAction.php?type=downloadCapaTrackingLogPdf&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadForm(id: number): void {
    this.service.open('qa/correctivePreventiveAction.php?type=downloadCapaReportPdf&id=' + id);
  }
}
