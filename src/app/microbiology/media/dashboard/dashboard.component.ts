import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master', title: 'Media Master', route: 'master', icon: 'fa-clipboard-list', category: 'Master', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'stock', title: 'Media Receiving', route: 'stock', icon: 'fa-box', category: 'Stock', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'preparation', title: 'Media Preparation', route: 'preparation', icon: 'fa-flask', category: 'Preparation', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'sterilization', title: 'Autoclaving & Sterilization', route: 'sterilization', icon: 'fa-burn', category: 'Preparation', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'growth', title: 'Growth Promotion', route: 'growth', icon: 'fa-microscope', category: 'GPT', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
    { id: 'decontamination', title: 'Decontamination', route: 'decontamination', icon: 'fa-biohazard', category: 'Decontam', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)' },
    { id: 'disposal', title: 'Media Disposal Record', route: 'disposal', icon: 'fa-trash-alt', category: 'Disposal', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)' },
    { id: 'stockbook', title: 'Stock Book', route: 'stockbook', icon: 'fa-book', category: 'Stock', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)' },
    { id: 'consumption', title: 'Media Consumption', route: 'consumption', icon: 'fa-chart-pie', category: 'Stock', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)' },
    { id: 'stock1', title: 'Prepared Media Stock', route: 'stock1', icon: 'fa-boxes', category: 'Stock', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'parameter', title: 'Media Parameters', route: 'parameter', icon: 'fa-sliders-h', category: 'Master', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
    { id: 'issuance', title: 'Media Issuance', route: 'issuance', icon: 'fa-share-square', category: 'Stock', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
  ];

  constructor() {}

  ngOnInit(): void {}
}
