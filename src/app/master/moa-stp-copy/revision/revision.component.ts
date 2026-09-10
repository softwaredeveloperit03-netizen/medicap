import { Component } from '@angular/core';

interface RevisionCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
}

@Component({
  selector: 'app-moa-stp-revision',
  templateUrl: './revision.component.html',
  styleUrls: ['./revision.component.css'],
})
export class RevisionComponent {
  searchQuery = '';

  readonly revisionCards: RevisionCard[] = [
    {
      id: 'moa-stp-revision',
      title: 'MOA/STP Revision',
      description: 'Unified MOA/STP logs table',
      route: '/master/moa-stp-copy/revision/moa-stp-revision',
      icon: 'fa-file-alt',
    },
    {
      id: 'revision-status',
      title: 'Revision Status',
      description: 'Track due and under-revision MOA/STP',
      route: '/master/moa-stp-copy/revision/revision-status',
      icon: 'fa-tasks',
    },
    {
      id: 'revision-history',
      title: 'Revision History Log',
      description: 'View revision records and history details',
      route: '/master/moa-stp-copy/revision/revision-history-log',
      icon: 'fa-history',
    },
    {
      id: 'periodic-review',
      title: 'Periodic Review of MOA/STP',
      description: 'Periodic review scheduling and tracking',
      route: '/master/moa-stp-copy/revision/periodic-review',
      icon: 'fa-calendar-check',
    },
    {
      id: 'training',
      title: 'Training',
      description: 'Revision training module',
      route: '/master/moa-stp-copy/revision/training',
      icon: 'fa-chalkboard-teacher',
    },
    {
      id: 'implementation',
      title: 'Implementation',
      description: 'Revision implementation module',
      route: '/master/moa-stp-copy/revision/implementation',
      icon: 'fa-cogs',
    },
  ];

  get filteredCards(): RevisionCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.revisionCards;
    }
    return this.revisionCards.filter((card) => {
      return card.title.toLowerCase().includes(q) || card.description.toLowerCase().includes(q);
    });
  }

  trackByCardId(_index: number, card: RevisionCard): string {
    return card.id;
  }
}

