import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { statusClass } from '../sqr.utils';

@Component({
  selector: 'app-sqr-qa-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['../sqr.shared.css'],
})
export class QaApprovalComponent implements OnInit {
  activeTab: 'stepwise' | 'final' = 'stepwise';
  stepwisePending: any[] = [];
  finalPending: any[] = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadPending();
  }

  setTab(tab: 'stepwise' | 'final'): void {
    this.activeTab = tab;
  }

  loadPending(): void {
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingStepwiseApprovals').subscribe((response: any) => {
      this.stepwisePending = Array.isArray(response) ? response : [];
    });
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingFinalReleaseApprovals').subscribe((response: any) => {
      this.finalPending = Array.isArray(response) ? response : [];
    });
  }

  reviewStepwise(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/stepwise/qa-review', record.id]);
  }

  reviewFinal(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/final/qa-review', record.id]);
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }
}
