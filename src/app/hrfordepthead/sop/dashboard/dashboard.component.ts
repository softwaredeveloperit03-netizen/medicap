import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'deptheadreview', title: 'SOP For Dept. Head Review', route: 'deptHeadReview', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qaheadreview', title: 'QA Head Review', route: 'qaHeadReview', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor() { }
  department=localStorage.getItem('department');

  ngOnInit(): void {
    this.department=localStorage.getItem('department');

  }

}
