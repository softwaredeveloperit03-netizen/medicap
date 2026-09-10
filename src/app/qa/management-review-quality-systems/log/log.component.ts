import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { API, FORM_MOM, SOP_REF, defaultFromDate, defaultToDate, hasStamp } from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-log',
  templateUrl: './log.component.html',
  styleUrls: ['../management-review.shared.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  formNo = FORM_MOM;
  sopRef = SOP_REF;
  loading = false;
  results: any[] = [];
  selectedResult: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  stamping = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
  }

  ngOnInit(): void {
    this.getMeetingsLog();
  }

  getMeetingsLog(): void {
    this.loading = true;
    this.service
      .get(`${API}?type=getMeetingsLog&from_date=${this.fromDate}&to_date=${this.toDate}`)
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
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  hasQaStamp(value: string): boolean {
    return hasStamp(value);
  }

  stampQaVerified(record: any): void {
    if (!record?.id || hasStamp(record.qa_verified_by) || this.stamping) return;
    this.stamping = true;
    this.service.post(`${API}?type=stampQaVerified`, JSON.stringify({ id: record.id })).subscribe(
      (response: any) => {
        this.stamping = false;
        if (response?.status === 'success') {
          record.qa_verified_by = response.qa_verified_by;
          if (this.selectedResult?.id === record.id) {
            this.selectedResult.qa_verified_by = response.qa_verified_by;
          }
          alertify.success('QA Verified saved.');
        } else {
          alertify.error(response?.status || 'Failed to stamp');
        }
      },
      () => {
        this.stamping = false;
        alertify.error('Failed to stamp');
      }
    );
  }

  downloadLogPdf(): void {
    this.service.open(`${API}?type=downloadMeetingsLogPdf`);
  }

  downloadMomPdf(id: number): void {
    this.service.open(`${API}?type=downloadMomPdf&id=${id}`);
  }
}
