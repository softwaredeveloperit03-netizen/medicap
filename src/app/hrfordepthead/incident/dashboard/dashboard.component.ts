import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
 import { DataAccessService } from 'src/app/data-access.service';
 
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'review', title: 'Review', route: 'Review', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qareview', title: 'QA Review', route: 'QAReview', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'qaheadreview', title: 'QA Head Review', route: 'QAheadReview', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'log', title: 'Incident Log', route: 'Log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];

  Department;
  constructor() { }

  ngOnInit(): void {
    this.Department=localStorage.getItem('department');
  }

}
