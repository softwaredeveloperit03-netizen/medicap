import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master-standard', title: 'Standard Master', route: 'master/standard', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'master-chemical', title: 'Chemical Master', route: 'master/chemical', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'master-glassware', title: 'Glassware Master', route: 'master/glassware', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'lab', title: 'Lab Master', route: 'lab', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'master-hplc', title: 'HPLC Column', route: 'master/hplc', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'master-volumetric', title: 'Volumetric Sol. Master', route: 'master/volumetric', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'master-indicator', title: 'Reagents / Indicators', route: 'master/indicator', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'sampling', title: 'Sampling', route: 'sampling', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'testing', title: 'Testing', route: 'testing', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'specification', title: 'Specifications', route: 'specification', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'qc-moa', title: 'Method of Analysis', route: 'qc/moa/', icon: 'fa-chart-bar', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'water', title: 'Water Analysis', route: 'water', icon: 'fa-tint', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'volumetric', title: 'Volumetric Solution', route: 'volumetric', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'retest-dashboard', title: 'Retest Management', route: 'retest-dashboard', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'balance', title: 'Calibrations', route: 'balance', icon: 'fa-balance-scale', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'daily', title: 'Env. Monitoring', route: 'daily', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'chemicals', title: 'Chemicals & Reagents', route: 'chemicals', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'glassware', title: 'Glasswares Mgt', route: 'glassware', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'stationary', title: 'Stationary Mgt', route: 'stationary', icon: 'fa-pen', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'equipments', title: 'Equipment Inventory', route: 'equipments', icon: 'fa-wrench', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'accessories', title: 'Accessories Parts', route: 'accessories', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
