import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New Usages Entry', route: 'new', icon: 'fa-plus-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-list', category: 'Modules', gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)' },
  ];
}
