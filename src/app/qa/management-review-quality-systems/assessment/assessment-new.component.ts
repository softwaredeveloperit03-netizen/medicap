import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  API,
  FORM_ASSESSMENT,
  INDICATOR_STATUSES,
  REVIEW_PERIODS,
  SOP_REF,
  buildEmptyIndicators,
  loadManagers,
  participantLabel,
  stampNow,
} from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-assessment-new',
  templateUrl: './assessment-new.component.html',
  styleUrls: ['../management-review.shared.css'],
  providers: [DatePipe],
})
export class AssessmentNewComponent implements OnInit {
  formNo = FORM_ASSESSMENT;
  sopRef = SOP_REF;
  revisionNo = '00';
  effectiveDate = '2025-04-16';
  reviewPeriods = REVIEW_PERIODS;
  indicatorStatuses = INDICATOR_STATUSES;

  reviewPeriod = 'Annual';
  reviewYear = '';
  periodFrom = '';
  periodTo = '';
  preparedBy = '';
  indicators: any[] = [];
  capaActions: any[] = [];
  overallConclusion = '';
  recommendations = '';
  complianceNotes =
    'Assessment covers suitability and effectiveness vs Canadian GMP, US cGMP, International GMP and corporate quality policies.';

  employees: any[] = [];
  actionText = '';
  actionResponsible = '';
  actionTargetDate = '';
  saving = false;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.reviewYear = this.datePipe.transform(new Date(), 'yyyy') || '';
    this.indicators = buildEmptyIndicators();
  }

  ngOnInit(): void {
    this.preparedBy = stampNow(this.datePipe);
    loadManagers(this.service).subscribe((response: any) => {
      this.employees = Array.isArray(response) ? response : [];
    });
  }

  employeeLabel(emp: any): string {
    return participantLabel(emp);
  }

  addCapa(): void {
    if (!this.actionText.trim()) {
      alertify.error('Enter CAPA action');
      return;
    }
    this.capaActions.push({
      action: this.actionText,
      responsible: this.actionResponsible,
      target_date: this.actionTargetDate,
    });
    this.actionText = '';
    this.actionResponsible = '';
    this.actionTargetDate = '';
  }

  stampPreparedBy(): void {
    this.preparedBy = stampNow(this.datePipe);
  }

  save(): void {
    const missing = this.indicators.some((i) => !i.status);
    if (missing) {
      alertify.error('Please select status for all performance indicators');
      return;
    }
    if (!this.overallConclusion.trim()) {
      alertify.error('Please enter overall conclusion');
      return;
    }
    if (this.saving) return;
    this.saving = true;
    const payload = {
      review_period: this.reviewPeriod,
      review_year: this.reviewYear,
      period_from: this.periodFrom,
      period_to: this.periodTo,
      prepared_by: this.preparedBy || stampNow(this.datePipe),
      indicators: this.indicators,
      capa_actions: this.capaActions,
      overall_conclusion: this.overallConclusion,
      recommendations: this.recommendations,
      compliance_notes: this.complianceNotes,
    };
    this.service.post(`${API}?type=saveAssessment`, JSON.stringify(payload)).subscribe(
      (response: any) => {
        this.saving = false;
        if (response?.status === 'success') {
          alertify.success('Assessment saved and sent to MRT for review');
          this.router.navigate(['/qa/management-review-quality-systems/assessment/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      },
      () => {
        this.saving = false;
        alertify.error('Failed to save');
      }
    );
  }
}
