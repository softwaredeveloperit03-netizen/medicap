import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'initialpq', title: 'initial PQ protocol', route: 'InitialPq', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checklist', title: 'Performance Check', route: 'checklist', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'operation', title: 'Oparation Inputs', route: 'operation', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'performace', title: 'Performance Criteria', route: 'performace', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'utilities', title: 'Utilities Req.', route: 'utilities', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'environment', title: 'Environment Checks', route: 'environment', icon: 'fa-tree', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'variable', title: 'Variable To Be Met', route: 'variable', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'documents', title: 'Material Use', route: 'documents', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'procedure', title: 'Procedure', route: 'procedure', icon: 'fa-list-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'report', title: 'Performance Report', route: 'report', icon: 'fa-file-contract', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'deviation', title: 'Deviation Report', route: 'deviation', icon: 'fa-file-contract', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'final-report', title: 'Final Report', route: 'final-report', icon: 'fa-file-contract', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
   

  }
 
  }


