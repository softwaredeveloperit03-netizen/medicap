import { Component } from '@angular/core';

@Component({
  selector: 'app-rcd-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  forms = [
    {
      label: 'Retention Sample Receipt & Storage',
      route: 'receipt/log',
      formNo: 'FQA-008-A',
      icon: 'fa-box',
    },
    {
      label: 'Retention Sample Periodic Review',
      route: 'review/log',
      formNo: 'FQA-008-B',
      icon: 'fa-clipboard-check',
    },
    {
      label: 'Retention Sample Disposal',
      route: 'disposal/log',
      formNo: 'FQA-008-C',
      icon: 'fa-trash-alt',
    },
  ];
}
