import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'challan', title: 'Inword Entry', route: 'challan', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'stationary', title: 'Stationary', route: 'stationary', icon: 'fa-pen-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'stock', title: 'Stock Book', route: 'stock', icon: 'fa-boxes', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'bincard', title: 'Bin Card', route: 'bincard', icon: 'fa-archive', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'dispensing', title: 'Dispensing', route: 'dispensing', icon: 'fa-prescription', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'labels', title: 'Label Printing', route: 'labels', icon: 'fa-tags', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'rack', title: 'Racks Master', route: 'rack', icon: 'fa-warehouse', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'rack-location-chart', title: 'Location Chart', route: 'rack/location-chart', icon: 'fa-map-marked-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'equipments', title: 'Equip Usages & Clean', route: 'equipments', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'balance', title: 'Balance Calibration', route: 'balance', icon: 'fa-balance-scale', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'return', title: 'Return Materials', route: 'return', icon: 'fa-undo', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'store-stock-opening', title: 'Opening Stock', route: 'store/stock/opening', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'indend', title: 'Indent', route: 'indend', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'rejection', title: 'Rejection', route: 'rejection', icon: 'fa-times-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'qms-maintenance', title: 'Maintenance', route: 'qms/maintenance', icon: 'fa-wrench', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'qms-deviation', title: 'Deviation', route: 'qms/deviation', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'qms-changecontrol', title: 'Change Control', route: 'qms/changecontrol', icon: 'fa-exchange-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'qms-sops', title: 'SOPs', route: 'qms/sops', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'qms-risk', title: 'Risk', route: 'qms/risk', icon: 'fa-exclamation-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'equipments', title: 'Equipment Usages & Cleaning', route: 'equipments', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'retest', title: 'Retest', route: 'retest', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'qms-sop', title: 'SOPs', route: 'qms/sop', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
