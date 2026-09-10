import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DashCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  showWhen?: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Safety';
  closeLink = '/admin';
  isuser = 'No';
  rights: any;
  loggedInDept: string | null = null;

  managementCards: DashCard[] = [
    { id: 'safety', title: 'Safety Alerts', description: 'Safety alerts and sub-modules', route: 'safety', icon: 'fa-exclamation-triangle', category: 'Safety', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'sign', title: 'Safety Signs & Draw', description: 'Safety signs and drawings', route: 'sign', icon: 'fa-map', category: 'Safety', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', showWhen: 'isuser' },
    { id: 'fire', title: 'Fire Map', description: 'Fire escape and evacuation map', route: 'fire', icon: 'fa-map-marked-alt', category: 'Safety', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', showWhen: 'isuser' },
    { id: 'moc', title: 'Fire Moc Drill', description: 'Fire mock drill records', route: 'moc', icon: 'fa-fire-extinguisher', category: 'Safety', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', showWhen: 'isuser' },
    { id: 'accident', title: 'Accidental Incident', description: 'Accident and incident reports', route: 'accident', icon: 'fa-exclamation-circle', category: 'Safety', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', showWhen: 'isuser' },
    { id: 'firetraining', title: 'Fire Trainings', description: 'Fire training and certificates', route: 'firetraining', icon: 'fa-chalkboard-teacher', category: 'Safety', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    { id: 'theft', title: 'Theft Information', description: 'Theft and loss information', route: 'theft', icon: 'fa-info-circle', category: 'Safety', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)', showWhen: 'isuser' }
  ];

  searchQuery = '';
  selectedCategory = 'All';
  get categories(): string[] { return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))]; }
  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      if (c.showWhen === 'isuser' && this.isuser !== 'Yes') return false;
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q));
      return matchCategory && matchSearch;
    });
  }
  trackByCardId(_index: number, card: DashCard): string { return card.id; }

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;
  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try { localStorage.setItem('admin_saftey_palette', palette.name); } catch { }
  }

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    const savedPalette = localStorage.getItem('admin_saftey_palette');
    if (savedPalette) { const found = this.paletteOptions.find(p => p.name === savedPalette); if (found) this.selectedPalette = found; }
  }

  get_rights(): void {
    this.service.get(
      'hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(localStorage.getItem('emp_id') || '') +
      '&dep_name=' + encodeURIComponent(this.loggedInDept || '')
    ).subscribe((response: any) => {
      this.rights = response;
      if (Array.isArray(response) && response[0]) {
        this.isuser = response[0].isuser || 'No';
      }
    });
  }
}
