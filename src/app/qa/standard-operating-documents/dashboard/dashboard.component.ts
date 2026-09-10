import { Component } from '@angular/core';

@Component({
  selector: 'app-sod-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  forms = [
    {
      label: 'SOP Distribution and Reconciliation',
      route: 'distribution/log',
      formNo: 'FQA-013-A',
      icon: 'fa-share-alt',
    },
    {
      label: 'Biennial Review Log',
      route: 'biennial/log',
      formNo: 'FQA-013-B',
      icon: 'fa-calendar-check',
    },
  ];
}
