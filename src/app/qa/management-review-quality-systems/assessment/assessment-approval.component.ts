import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { API, FORM_ASSESSMENT, SOP_REF } from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-assessment-approval',
  templateUrl: './assessment-approval.component.html',
  styleUrls: ['../management-review.shared.css'],
})
export class AssessmentApprovalComponent implements OnInit {
  formNo = FORM_ASSESSMENT;
  sopRef = SOP_REF;
  loading = false;
  results: any[] = [];
  selected: any = null;
  isView = false;
  mrtComment = '';
  processing = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadPending();
  }

  loadPending(): void {
    this.loading = true;
    this.service.get(`${API}?type=getPendingAssessments`).subscribe(
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
    this.mrtComment = '';
    this.isView = true;
  }

  decide(action: 'approve' | 'return'): void {
    if (!this.selected?.id || this.processing) return;
    this.processing = true;
    this.service
      .post(
        `${API}?type=approveAssessment`,
        JSON.stringify({ id: this.selected.id, action, mrt_comment: this.mrtComment })
      )
      .subscribe(
        (response: any) => {
          this.processing = false;
          if (response?.status === 'success') {
            alertify.success(action === 'approve' ? 'Assessment approved by MRT' : 'Assessment returned');
            this.isView = false;
            this.loadPending();
          } else {
            alertify.error(response?.status || 'Failed');
          }
        },
        () => {
          this.processing = false;
          alertify.error('Failed');
        }
      );
  }

  downloadPdf(id: number): void {
    this.service.open(`${API}?type=downloadAssessmentPdf&id=${id}`);
  }
}
