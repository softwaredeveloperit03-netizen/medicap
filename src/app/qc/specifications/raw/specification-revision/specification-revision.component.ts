import { Component } from '@angular/core';

interface RevisionCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
}

@Component({
  selector: 'app-specification-revision',
  templateUrl: './specification-revision.component.html',
  styleUrls: ['./specification-revision.component.css'],
})
export class SpecificationRevisionComponent {
  searchQuery = '';

  readonly revisionCards: RevisionCard[] = [
    {
      id: 'specification-revision',
      title: 'Specification Revision',
      description: 'Unified specification logs table',
      route: '/master/specification/raw/specification-revision/specification-revision',
      icon: 'fa-file-alt',
    },
    {
      id: 'revision-status',
      title: 'Revision Status',
      description: 'Track due, active and under-revision specifications',
      route: '/master/specification/specification-revision/revision-status',
      icon: 'fa-tasks',
    },
    {
      id: 'revision-history',
      title: 'Revision History Log',
      description: 'View revision records and history details',
      route: '/master/specification/specification-revision/revision-history-log',
      icon: 'fa-history',
    },
    {
      id: 'periodic-review',
      title: 'Periodic Review of Specification',
      description: 'Periodic review scheduling and tracking',
      route: '/master/specification/specification-revision/periodic-review',
      icon: 'fa-calendar-check',
    },
  ];

  get filteredCards(): RevisionCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.revisionCards;
    }
    return this.revisionCards.filter((card) => {
      return (
        card.title.toLowerCase().includes(q) ||
        card.description.toLowerCase().includes(q)
      );
    });
  }

  trackByCardId(_index: number, card: RevisionCard): string {
    return card.id;
  }
}
