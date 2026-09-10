import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  results: any;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.getIndendsLog();
    this.categories = ['All', ...Array.from(new Set(this.allCards.map(c => c.category)))];
    this.updateFilteredCards();
    const savedPalette = localStorage.getItem('purchase_vendor_material_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) {
        this.selectedPalette = found;
        this.cdr.markForCheck();
      }
    }
  }

  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;
  categories: string[] = ['All'];
  filteredCards: DeptCard[] = [];

  private allCards: DeptCard[] = [
    {
      id: 'map',
      title: 'Map',
      description: 'Map material to vendor',
      route: '/purchase/vendor/material/map',
      icon: 'fa-link',
      category: 'Material',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
  ];

  paletteOptions = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
  ];
  selectedPalette = this.paletteOptions[0];

  getIndendsLog() {
    this.service.get('master/material.php?type=get_supplier_by_materials_log').subscribe((response: any) => {
      this.results = response;
      this.cdr.markForCheck();
    });
  }

  updateFilteredCards(): void {
    const q = (this.searchQuery || '').trim().toLowerCase();
    this.filteredCards = this.allCards.filter(c => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchCategory && matchSearch;
    });
    this.cdr.markForCheck();
  }

  onSearchChange(): void {
    this.updateFilteredCards();
  }

  selectCategory(cat: string): void {
    this.selectedCategory = cat;
    this.updateFilteredCards();
  }

  trackByCardId(_index: number, card: DeptCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
    this.cdr.markForCheck();
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('purchase_vendor_material_dashboard_palette', palette.name);
    } catch (e) {}
    this.cdr.markForCheck();
  }
}
