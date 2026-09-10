import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'ws-matrix', title: 'WS Vial Matrix', route: 'ws-matrix', icon: 'fa-th', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'working-standard-qualicication', title: 'Preparation of', route: 'working-standard-qualicication', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'qc-standard-ordering', title: 'Specification of', route: 'qc/standard/ordering', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'qc-standard-receiving', title: 'Testing of Working Std', route: 'qc/standard/receiving', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'qc-standard-issuance', title: 'Issuance of Working Std', route: 'qc/standard/issuance', icon: 'fa-truck', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'qc-standard-qualification-new', title: 'Consumption Report of', route: 'qc/standard/qualification/new', icon: 'fa-chart-bar', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'working-standard-qualicication', title: 'Preparation of Working Standard', route: 'working-standard-qualicication', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'qc-standard-ordering', title: 'Specification of Working Standard', route: 'qc/standard/ordering', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'qc-standard-receiving', title: 'Testing of Working Standard', route: 'qc/standard/receiving', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'qc-standard-issuance', title: 'Issuance of Working Standard', route: 'qc/standard/issuance', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'qc-standard-qualification-new', title: 'Consumption Report of Working Standard', route: 'qc/standard/qualification/new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
