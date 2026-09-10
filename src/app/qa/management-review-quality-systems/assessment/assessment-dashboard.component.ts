import { Component } from '@angular/core';

@Component({
  selector: 'app-mrq-assessment-dashboard',
  templateUrl: './assessment-dashboard.component.html',
  styleUrls: ['../dashboard/dashboard.component.css'],
})
export class AssessmentDashboardComponent {
  pageTitle = 'Assessment Form (FQA-042-B)';
  closeLink = '/qa/management-review-quality-systems';

  cards = [
    {
      id: 'new',
      title: 'New Assessment',
      description: 'Prepare management review assessment (QA Director / Designate)',
      route: 'new',
      icon: 'fa-edit',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'approval',
      title: 'MRT Review / Approval',
      description: 'Management Review Team review and approval',
      route: 'approval',
      icon: 'fa-user-check',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'log',
      title: 'Assessment Log',
      description: 'Approved / returned assessment records',
      route: 'log',
      icon: 'fa-book',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
  ];
}
