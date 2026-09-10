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
    { id: 'new', title: 'New', route: 'new', icon: 'fa-plus-circle', category: 'Control', gradient: G.navy },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-list', category: 'Control', gradient: G.blue },
    { id: 'expired', title: 'Expired', route: 'expired', icon: 'fa-clock', category: 'Control', gradient: G.slate },
    { id: 'distroy', title: 'Destroy', route: 'distroy', icon: 'fa-trash-alt', category: 'Control', gradient: G.steel },
    { id: 'review', title: 'Review', route: 'review', icon: 'fa-search', category: 'Control', gradient: G.indigo },
    { id: 'rack', title: 'Rack', route: 'rack', icon: 'fa-th-large', category: 'Control', gradient: G.navy },
    { id: 'withdrawal', title: 'Withdrawal', route: 'withdrawal', icon: 'fa-hand-holding', category: 'Control', gradient: G.blue },
  ];
}
