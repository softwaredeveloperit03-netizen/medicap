import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master-product', title: 'Products', route: 'master/product', icon: 'fa-shopping-basket', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'master-material', title: 'Materials', route: 'master/material', icon: 'fa-cubes', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'master-equipment', title: 'Equipments', route: 'master/equipment', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'soops', title: 'SOP Management', route: 'soops', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'controlsample', title: 'Control Sample', route: 'controlsample', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'stability', title: 'Stability Management', route: 'stability', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'risk', title: 'Risk Management', route: 'risk', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'vendor', title: 'Vendor Management', route: 'vendor', icon: 'fa-store', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'technicaldoc', title: 'Technical Document', route: 'technicaldoc', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'deviation', title: 'Deviation', route: 'deviation', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'changecontrol', title: 'Change Control', route: 'changecontrol', icon: 'fa-exchange-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'incident', title: 'Incident Reporting', route: 'incident', icon: 'fa-bug', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
