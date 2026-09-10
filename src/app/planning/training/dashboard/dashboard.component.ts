import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'identification',
      title: 'Training Need Identification',
      route: 'identification',
      icon: 'fa-clipboard-check',
      category: 'Training',
      gradient: G.teal,
    },
    {
      id: 'announcement',
      title: 'Training Announcement',
      route: 'identification',
      icon: 'fa-bullhorn',
      category: 'Training',
      gradient: G.blue,
    },
    {
      id: 'retraining',
      title: 'Retraining Training',
      route: 'retraining',
      icon: 'fa-redo-alt',
      category: 'Training',
      gradient: G.amber,
    },
    {
      id: 'certificate',
      title: 'On Job Training',
      route: 'certificate',
      icon: 'fa-book-open',
      category: 'Training',
      gradient: G.indigo,
    },
    {
      id: 'induction',
      title: 'Induction Training',
      route: 'induction',
      icon: 'fa-graduation-cap',
      category: 'Training',
      gradient: G.navy,
    },
    {
      id: 'individual',
      title: 'Individual Training Record',
      route: 'individual',
      icon: 'fa-file-alt',
      category: 'Training',
      gradient: G.steel,
    },
    {
      id: 'evaluate',
      title: 'Training Evaluation',
      route: 'evaluate',
      icon: 'fa-poll',
      category: 'Training',
      gradient: G.emerald,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
