import { Component } from '@angular/core';

@Component({
  selector: 'app-etr-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  forms = [
    {
      label: 'General Training Record',
      route: 'general/log',
      formNo: 'FQA-003-A',
      icon: 'fa-file-alt',
    },
    {
      label: 'GMP Training',
      route: 'gmp/log',
      formNo: 'FQA-003-B',
      icon: 'fa-industry',
    },
    {
      label: 'Group Training',
      route: 'group/log',
      formNo: 'FQA-003-C',
      icon: 'fa-users',
    },
    {
      label: 'On-The-Job Training',
      route: 'ojt/log',
      formNo: 'FQA-003-D',
      icon: 'fa-user-graduate',
    },
  ];
}
