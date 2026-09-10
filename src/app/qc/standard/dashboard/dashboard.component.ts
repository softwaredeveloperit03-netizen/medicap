import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = {
  navy: 'linear-gradient(135deg, #004a70 0%, #0ea5e9 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  indigo: 'linear-gradient(135deg, #312e81 0%, #6366f1 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  cards: QcDeptCard[] = [
    { id: 'certificate', title: 'Certificate', route: 'certificate', icon: 'fa-certificate', category: 'Standard', gradient: G.navy },
    { id: 'cost-report', title: 'Cost Report', route: 'cost-report', icon: 'fa-file-invoice-dollar', category: 'Standard', gradient: G.blue },
    { id: 'destruction', title: 'Destruction', route: 'destruction', icon: 'fa-trash-alt', category: 'Standard', gradient: G.slate },
    { id: 'expiry', title: 'Expiry', route: 'expiry', icon: 'fa-clock', category: 'Standard', gradient: G.steel },
    { id: 'master', title: 'Master', route: 'master', icon: 'fa-database', category: 'Standard', gradient: G.indigo },
    { id: 'matrix', title: 'Matrix', route: 'matrix', icon: 'fa-th', category: 'Standard', gradient: G.teal },
    { id: 'ordering', title: 'Ordering', route: 'ordering', icon: 'fa-shopping-cart', category: 'Standard', gradient: G.navy },
    { id: 'qualification', title: 'Qualification', route: 'qualification', icon: 'fa-user-graduate', category: 'Standard', gradient: G.blue },
    { id: 'receiving', title: 'Receiving', route: 'receiving', icon: 'fa-truck-loading', category: 'Standard', gradient: G.slate },
    { id: 'stock-report', title: 'Stock Report', route: 'stock-report', icon: 'fa-chart-bar', category: 'Standard', gradient: G.steel },
    { id: 'storage', title: 'Storage', route: 'storage', icon: 'fa-warehouse', category: 'Standard', gradient: G.indigo },
    { id: 'issuance', title: 'Issuance', route: 'issuance', icon: 'fa-hand-holding', category: 'Standard', gradient: G.teal },
  ];
}
