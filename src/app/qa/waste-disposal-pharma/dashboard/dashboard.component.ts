import { Component } from '@angular/core';

@Component({
  selector: 'app-waste-disposal-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  forms = [
    {
      label: 'Waste Disposal Record',
      route: 'log',
      formNo: 'FQA-022-A',
      icon: 'fa-trash-alt',
    },
  ];
}
