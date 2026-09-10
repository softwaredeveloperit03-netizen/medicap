import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'product', title: 'Products', route: 'product', icon: 'fa-shopping-basket', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'material', title: 'Materials', route: 'material', icon: 'fa-paint-brush', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'stationary', title: 'General Materials', route: 'stationary', icon: 'fa-pen', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'equipments', title: 'Equipments', route: 'equipments', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'test', title: 'Test', route: 'test', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'subtest', title: 'Subtest', route: 'subtest', icon: 'fa-microscope', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'standard', title: 'Standard Master', route: 'standard', icon: 'fa-certificate', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'chemical', title: 'Chemical Master', route: 'chemical', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'glassware', title: 'Glassware Master', route: 'glassware', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'lab', title: 'Lab Master', route: 'lab', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'hplc', title: 'HPLC Column', route: 'hplc', icon: 'fa-columns', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'volumetric', title: 'Volumetric Solution', route: 'volumetric', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'indicator', title: 'Reagents / Indicators', route: 'indicator', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
