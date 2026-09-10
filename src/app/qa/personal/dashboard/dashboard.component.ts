import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'qa-personal-therotical-test', title: 'Theoretical Know Test', route: 'qa/personal/therotical-test', icon: 'fa-pen', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qual-cert', title: 'Qualif. & Certificate', route: 'qual-cert', icon: 'fa-certificate', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'analyst', title: 'Analyst Qualif.', route: 'analyst', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'personal-employee', title: 'Employee Details', route: 'personal/employee', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'qa-personal-therotical-test', title: 'Therotical Knowledge Test', route: 'qa/personal/therotical-test', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'qual-cert', title: 'Qualification & Certificate', route: 'qual-cert', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'analyst', title: 'Analyst Qualification', route: 'analyst', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'qa-personal-personal', title: 'Operator Qualification', route: 'qa/personal/personal', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
