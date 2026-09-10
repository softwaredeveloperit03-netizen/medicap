import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'urs', title: 'User Requirement Spec.', route: 'urs', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'dq', title: 'Design Qualification', route: 'dq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'factory', title: 'Factory Acceptance', route: 'factory', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'equipment-qualification-site', title: 'Site Acceptance', route: 'equipment-qualification/site', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'equipment-qualification-qualification-request', title: 'Qualification Request (IQ)', route: 'equipment-qualification/qualification-request', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'iq', title: 'Installation Qualification', route: 'iq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'item', title: 'Operational Qualification', route: '', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'item', title: 'Performance Qualification', route: '', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'item', title: 'Requalification', route: '', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'item', title: 'Qualification Records', route: '', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'equipment-qualification-equipment-vendor', title: 'Equipment Vendor', route: 'equipment-qualification/equipment-vendor', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
