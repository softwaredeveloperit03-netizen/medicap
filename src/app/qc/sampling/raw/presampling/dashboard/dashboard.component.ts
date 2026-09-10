import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = {
  navy: 'linear-gradient(135deg, #004a70 0%, #0ea5e9 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  cards: QcDeptCard[] = [
    { id: 'area', title: 'Area', route: 'area', icon: 'fa-map-marker-alt', category: 'Pre-sampling', gradient: G.navy },
    { id: 'balance', title: 'Balance', route: 'balance', icon: 'fa-balance-scale', category: 'Pre-sampling', gradient: G.blue },
  ];
}
