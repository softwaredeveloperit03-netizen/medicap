import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  closeRouterLink = '/fproduction';
  cards: QcDeptCard[] = [
    { id: 'temperature', title: 'Temperature / Humidity Log', route: 'temperature', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'pressure', title: 'Pressure Differential Log', route: 'pressure', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'equipments', title: 'Equipment Log', route: 'equipments', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'equipments-status', title: 'Equipment Status Log', route: 'equipments/status', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'maintenance', title: 'Maintenance Log', route: 'maintenance', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
  ];

  constructor(private route: ActivatedRoute) {}

  ngOnInit() {
    this.route.queryParams.subscribe((p) => {
      if (p['returnUrl']) {
        this.closeRouterLink = String(p['returnUrl']);
      }
    });
  }
}
