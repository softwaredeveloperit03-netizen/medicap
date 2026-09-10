import { Component } from '@angular/core';

@Component({
  selector: 'app-moa-stp-copy-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  /** Same tabs as Zuma QC → MOA → Proceed workspace */
  readonly cards: Array<{
    title: string;
    route: string;
    icon: string;
    gradient: string;
    queryParams?: Record<string, string>;
  }> = [
    {
      title: 'Proceed / Pending',
      route: '/master/moa-stp-copy/new-moa-stp',
      icon: 'fa-plus-circle',
      gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
    },
    {
      title: 'Checking',
      route: '/master/moa-stp-copy/checking',
      icon: 'fa-check-circle',
      gradient: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
    },
    {
      title: 'Approval',
      route: '/master/moa-stp-copy/approval',
      icon: 'fa-thumbs-up',
      gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
    },
    {
      title: 'Log Table',
      route: '/master/moa-stp-copy/log',
      icon: 'fa-list',
      gradient: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
    },
    {
      title: 'Correction',
      route: '/master/moa-stp-copy/correction',
      icon: 'fa-undo',
      gradient: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
    },
    {
      title: 'MOA STP Revision',
      route: '/master/moa-stp-copy/revision',
      icon: 'fa-history',
      gradient: 'linear-gradient(135deg, #9a3412 0%, #ea580c 100%)',
    },
  ];
}
