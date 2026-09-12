import { Component, OnInit } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-equipment-work-order-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  department = '';
  cards: QcDeptCard[] = [];

  private readonly allCards: QcDeptCard[] = [
    {
      id: 'new',
      title: 'New Work Order',
      route: 'new',
      icon: 'fa-file-medical',
      category: 'Initiator',
      gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
    },
    {
      id: 'engg-review',
      title: 'Engineering Review',
      route: 'engg-review',
      icon: 'fa-clipboard-check',
      category: 'Engineering',
      gradient: 'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)',
    },
    {
      id: 'work-performed',
      title: 'Work Performed',
      route: 'work-performed',
      icon: 'fa-tools',
      category: 'Engineering',
      gradient: 'linear-gradient(135deg, #047857 0%, #10b981 100%)',
    },
    {
      id: 'verification',
      title: 'Work Order Verification',
      route: 'verification',
      icon: 'fa-user-check',
      category: 'Initiator',
      gradient: 'linear-gradient(135deg, #5b21b6 0%, #8b5cf6 100%)',
    },
    {
      id: 'qa-approval',
      title: 'QA Approval',
      route: 'qa-approval',
      icon: 'fa-shield-alt',
      category: 'QA',
      gradient: 'linear-gradient(135deg, #be123c 0%, #f43f5e 100%)',
    },
    {
      id: 'log',
      title: 'Work Order Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Records',
      gradient: 'linear-gradient(135deg, #334155 0%, #64748b 100%)',
    },
    {
      id: 'issuance-log',
      title: 'Issuance Log (Final)',
      route: 'issuance-log',
      icon: 'fa-file-alt',
      category: 'Engineering',
      gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
    },
  ];

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    const dept = (this.department || '').toLowerCase();
    const isEngg = dept.includes('engineering') || dept.includes('maintenance');
    const isQa =
      dept === 'qa' || dept.includes('quality assurance') || dept === 'quality assurance';

    this.cards = this.allCards.filter((c) => {
      if (c.category === 'Engineering') {
        return isEngg;
      }
      if (c.category === 'QA') {
        return isQa;
      }
      return true;
    });
  }
}
