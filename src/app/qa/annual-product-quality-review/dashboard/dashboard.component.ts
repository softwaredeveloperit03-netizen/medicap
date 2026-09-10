import { Component } from '@angular/core';

@Component({
  selector: 'app-apqr-module-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  forms = [
    {
      label: 'In-House APQR / APR Report',
      route: 'inhouse/log',
      formNo: 'Attachment 1',
      icon: 'fa-file-medical-alt',
    },
    {
      label: 'Third Party Report Review',
      route: 'third-party/log',
      formNo: 'FQA-027-A',
      icon: 'fa-building',
    },
  ];
}
