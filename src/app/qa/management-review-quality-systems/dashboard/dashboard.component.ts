import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DashCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  showWhen?: string;
}

@Component({
  selector: 'app-mrq-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Management Review of Quality Systems';
  closeLink = '/qa';
  formNo = 'FQA-042-A / FQA-042-B';
  sopRef = 'SOP-QA-042';

  managementCards: DashCard[] = [
    {
      id: 'announcement',
      title: 'Meeting Announcement',
      description: 'Schedule meeting & agenda (FQA-042-A)',
      route: 'announcement',
      icon: 'fa-bullhorn',
      category: 'Meeting',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      showWhen: 'user',
    },
    {
      id: 'start',
      title: 'Start Meeting',
      description: 'Start pending meetings',
      route: 'start',
      icon: 'fa-play',
      category: 'Meeting',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'inprocess',
      title: 'Minutes of Meeting',
      description: 'Capture MOM & action items (FQA-042-B)',
      route: 'inprocess',
      icon: 'fa-clipboard-list',
      category: 'Meeting',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'mom-view',
      title: 'View MOM',
      description: 'View MOM for meetings you joined',
      route: 'mom-view',
      icon: 'fa-file-alt',
      category: 'Meeting',
      gradient: 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
    },
    {
      id: 'log',
      title: 'Meeting Log Book',
      description: 'View completed meetings & PDF',
      route: 'log',
      icon: 'fa-book',
      category: 'Meeting',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
    {
      id: 'assessment',
      title: 'Assessment Form',
      description: 'FQA-042-B performance indicators → MRT approval',
      route: 'assessment',
      icon: 'fa-clipboard-check',
      category: 'Assessment',
      gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    },
  ];

  searchQuery = '';
  selectedCategory = 'All';
  isuser = 'No';

  paletteOptions = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getRights();
    const saved = localStorage.getItem('qa_mrq_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) this.selectedPalette = found;
    }
  }

  get categories(): string[] {
    return ['All', ...Array.from(new Set(this.managementCards.map((c) => c.category)))];
  }

  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards
      .filter((c) => !c.showWhen || c.showWhen !== 'user' || this.isuser === 'Yes')
      .filter(
        (c) =>
          (this.selectedCategory === 'All' || c.category === this.selectedCategory) &&
          (!q || c.title.toLowerCase().includes(q) || c.description.toLowerCase().includes(q))
      );
  }

  getRights(): void {
    const empId = localStorage.getItem('emp_id');
    const dept = localStorage.getItem('department') || '';
    if (!empId) return;
    this.service.get(`hr/employee.php?type=getrights&emp_id=${encodeURIComponent(empId)}&dep_name=${encodeURIComponent(dept)}`).subscribe((response: any) => {
      const rights = Array.isArray(response) && response[0] ? response[0] : {};
      this.isuser = rights.isuser || 'No';
    });
  }

  trackByCardId(_i: number, c: DashCard): string {
    return c.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(p: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = p;
    this.showPalette = false;
    try {
      localStorage.setItem('qa_mrq_palette', p.name);
    } catch {}
  }
}
