import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = {
  navy: 'linear-gradient(135deg, #004a70 0%, #0ea5e9 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  cards: QcDeptCard[] = [
    {
      id: 'receive',
      title: 'Receive',
      searchText: 'Receive GRN',
      route: 'receive',
      icon: 'fa-truck-loading',
      category: 'GRN',
      gradient: G.navy,
    },
    {
      id: 'onHoldReceiving',
      title: 'On Hold Receiving',
      route: 'onHoldReceiving',
      icon: 'fa-pause-circle',
      category: 'GRN',
      gradient: G.blue,
    },
    {
      id: 'rejectedReceiving',
      title: 'Rejected Receiving',
      route: 'rejectedReceiving',
      icon: 'fa-times-circle',
      category: 'GRN',
      gradient: G.slate,
    },
    {
      id: 'log',
      title: 'Log',
      route: 'log',
      icon: 'fa-list',
      category: 'GRN',
      gradient: G.steel,
    },
  ];
}
