import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { API, FORM_ASSESSMENT, SOP_REF, defaultFromDate, defaultToDate } from '../management-review.utils';

@Component({
  selector: 'app-mrq-assessment-log',
  templateUrl: './assessment-log.component.html',
  styleUrls: ['../management-review.shared.css'],
  providers: [DatePipe],
})
export class AssessmentLogComponent implements OnInit {
  formNo = FORM_ASSESSMENT;
  sopRef = SOP_REF;
  loading = false;
  results: any[] = [];
  selected: any = null;
  isView = false;
  fromDate = '';
  toDate = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
  }

  ngOnInit(): void {
    this.loadLog();
  }

  loadLog(): void {
    this.loading = true;
    this.service
      .get(`${API}?type=getAssessmentLog&from_date=${this.fromDate}&to_date=${this.toDate}`)
      .subscribe(
        (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => {
          this.loading = false;
        }
      );
  }

  view(index: number): void {
    this.selected = this.results[index];
    this.isView = true;
  }

  downloadPdf(id: number): void {
    this.service.open(`${API}?type=downloadAssessmentPdf&id=${id}`);
  }
}
