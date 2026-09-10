import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate } from '../../employee-training-records/training-records.utils';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
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
        'qa/employeeInitialsSignature.php?type=getEmployeeInitialsSignatureLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  downloadLog(): void {
    this.service.open(
      'qa/employeeInitialsSignature.php?type=downloadEmployeeInitialsSignatureLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadForm(id: number): void {
    this.service.open(
      'qa/employeeInitialsSignature.php?type=downloadEmployeeInitialsSignatureForm&id=' + id
    );
  }
}
