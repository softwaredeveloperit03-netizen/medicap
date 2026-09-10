import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'payrole', title: 'My Payroll', route: 'payrole', icon: 'fa-file-invoice-dollar', category: 'Salary', gradient: 'linear-gradient(135deg, #0ea5e9 0%, #1d4ed8 100%)' },
    { id: 'statement', title: 'Payroll Statement', route: 'statement', icon: 'fa-file-alt', category: 'Salary', gradient: 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)' },
    { id: 'slips', title: 'Salary Slip & Certificates', route: 'slips', icon: 'fa-download', category: 'Salary', gradient: 'linear-gradient(135deg, #d97706 0%, #b45309 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
