import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate } from '../../employee-training-records/training-records.utils';
import { ChecklistPage } from '../gmp-inspection-clinical-site.checklist';

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
  loadingView = false;

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
        'qa/gmpInspectionClinicalSite.php?type=getGmpInspectionClinicalSiteLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.loadingView = true;
    this.service
      .get('qa/gmpInspectionClinicalSite.php?type=getGmpInspectionClinicalSiteById&id=' + record.id)
      .subscribe(
        (response: any) => {
          this.loadingView = false;
          if (response?.id) {
            this.selectedRecord = response;
            this.isView = true;
          }
        },
        () => {
          this.loadingView = false;
        }
      );
  }

  getChecklistPages(): ChecklistPage[] {
    return Array.isArray(this.selectedRecord?.checklist_data) ? this.selectedRecord.checklist_data : [];
  }

  downloadLog(): void {
    this.service.open(
      'qa/gmpInspectionClinicalSite.php?type=downloadGmpInspectionClinicalSiteLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadForm(id: number): void {
    this.service.open('qa/gmpInspectionClinicalSite.php?type=downloadGmpInspectionClinicalSiteForm&id=' + id);
  }
}
