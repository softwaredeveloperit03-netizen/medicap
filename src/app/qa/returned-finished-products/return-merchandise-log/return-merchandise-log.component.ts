import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_B_NO,
  REVISION_NO,
  SOP_REF,
  defaultFromDate,
  defaultToDate,
  statusClass,
} from '../rfp.utils';

@Component({
  selector: 'app-return-merchandise-log',
  templateUrl: './return-merchandise-log.component.html',
  styleUrls: ['../rfp.shared.css'],
  providers: [DatePipe],
})
export class ReturnMerchandiseLogComponent implements OnInit {
  formNo = FORM_B_NO;
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
        'qa/returnedFinishedProducts.php?type=getReturnMerchandiseLog&from_date=' +
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
      .get('qa/returnedFinishedProducts.php?type=getReturnMerchandiseById&id=' + record.id)
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

  downloadLog(): void {
    this.service.open(
      'qa/returnedFinishedProducts.php?type=downloadReturnMerchandiseLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadReport(id: number): void {
    this.service.open('qa/returnedFinishedProducts.php?type=downloadReturnMerchandiseReportForm&id=' + id);
  }
}
