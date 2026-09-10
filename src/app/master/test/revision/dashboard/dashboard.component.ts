import { Component, OnInit } from '@angular/core';

interface RevisionCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  gradient: string;
}

@Component({
  selector: 'app-revision-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class RevisionDashboardComponent implements OnInit {
  searchQuery = '';

  readonly cards: RevisionCard[] = [
    { id: 'request', title: 'Revision Request', description: 'Raise and manage revision requests', route: 'request', icon: 'fa-file-alt', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Revision Approval', description: 'Approve revisions with remarks', route: 'approval', icon: 'fa-check-circle', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'change-control', title: 'Raise Change Control', description: 'Raise change control after QA approval', route: 'change-control', icon: 'fa-edit', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'update-form', title: 'Update Form', description: 'Update form after change control approval', route: 'update-form', icon: 'fa-file-signature', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'history', title: 'Revision History', description: 'View revision and change control history', route: 'history', icon: 'fa-history', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' }
  ];

  get filteredCards(): RevisionCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) return this.cards;
    return this.cards.filter(
      (c) =>
        c.title.toLowerCase().includes(q) ||
        (c.description && c.description.toLowerCase().includes(q))
    );
  }

  trackByCardId(_index: number, card: RevisionCard): string {
    return card.id;
  }

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('revision_dashboard_palette', palette.name);
    } catch {}
  }

  ngOnInit(): void {
    const saved = localStorage.getItem('revision_dashboard_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) this.selectedPalette = found;
    }
  }
}
