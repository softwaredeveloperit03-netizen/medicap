import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'approval', title: 'Training Approval', route: 'approval', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qaapproval', title: 'QA Training Approval', route: 'QaApproval', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'induction', title: 'induction Training report', route: 'induction', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'qainductionapproval', title: 'Final induction Tr. Appr.', route: 'qaInductionApproval', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor() { }
  department='';
  ngOnInit(): void {
    this.department = localStorage.getItem('department');
  }

}
