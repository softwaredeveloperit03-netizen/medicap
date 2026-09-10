import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'hub', title: 'ICH Qualification Hub', route: 'hub', icon: 'fa-project-diagram', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'urs', title: 'User Requirement Spec.', route: 'urs', icon: 'fa-user', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'dq', title: 'Design Qualification', route: 'dq', icon: 'fa-pen', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'factory', title: 'Factory Acceptance', route: 'factory', icon: 'fa-industry', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'site', title: 'Site Acceptance', route: 'site', icon: 'fa-building', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'qualification-request', title: 'Qualification Request', route: 'qualification-request', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'iq', title: 'Installation Qualification', route: 'iq', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #a18cd1 0%, #764ba2 100%)' },
    { id: 'oq', title: 'Operational Qualification', route: 'oq', icon: 'fa-cog', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'pq', title: 'Performance Qualification', route: 'pq', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #ff9a9e 0%, #764ba2 100%)' },
    { id: 'requalification', title: 'Requalification', route: 'requalification', icon: 'fa-sync-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'equipment-vendor', title: 'Equipment Vendor', route: 'equipment-vendor', icon: 'fa-truck-moving', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #667eea 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
