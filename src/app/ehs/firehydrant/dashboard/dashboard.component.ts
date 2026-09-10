import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'firefight', title: 'List of Trained', route: 'firefight', icon: 'fa-bug', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'cleaningschd', title: 'Cleaning Schedule of', route: 'cleaningschd', icon: 'fa-bug', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'cleaningrec', title: 'Fire Hydrant Water', route: 'cleaningrec', icon: 'fa-bug', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'inspectionfhyd', title: 'Inspection Of Fire', route: 'inspectionfhyd', icon: 'fa-bug', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
