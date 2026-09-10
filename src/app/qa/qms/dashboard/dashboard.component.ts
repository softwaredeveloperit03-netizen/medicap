import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'sops', title: 'SOP', route: 'sops', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'risk', title: 'Risk', route: 'risk', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'capa', title: 'CAPA', route: 'capa', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'incident', title: 'Incident', route: 'incident', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    {
      id: 'pharma-self-inspection',
      title: 'Self Inspection & Internal Audit',
      route: 'pharma-self-inspection',
      icon: 'fa-clipboard-check',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #065f46 0%, #0d9488 100%)',
    },
  ];


  constructor() { }
plant_id;
  ngOnInit(): void {
    this.plant_id=localStorage.getItem('plant_id')
  }

}
