import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DashCard { id: string; title: string; description: string; route: string; icon: string; category: string; gradient: string; showWhen?: string; }

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Management Review Meeting';
  closeLink = '/management';

  managementCards: DashCard[] = [
    { id: 'announcement', title: 'Meeting Announcement', description: 'Announce meetings', route: 'announcement', icon: 'fa-bullhorn', category: 'Meeting', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhen: 'user' },
    { id: 'start', title: 'Start Meeting', description: 'Start a meeting', route: 'start', icon: 'fa-play', category: 'Meeting', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'inprocess', title: 'Inprocess Meetings', description: 'Meetings in progress', route: 'inprocess', icon: 'fa-calendar-alt', category: 'Meeting', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'log', title: 'Meeting Log Book', description: 'Meeting log', route: 'log', icon: 'fa-book', category: 'Meeting', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }
  ];

  searchQuery = '';
  selectedCategory = 'All';
  get categories(): string[] { return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))]; }
  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    const byRights = this.managementCards.filter(c => { if (c.showWhen === 'user' && this.isuser !== 'Yes') return false; return true; });
    return byRights.filter((c) => (this.selectedCategory === 'All' || c.category === this.selectedCategory) && (!q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q))));
  }
  trackByCardId(_i: number, c: DashCard): string { return c.id; }

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;
  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(p: { name: string; background: string; cardShadow: string }): void { this.selectedPalette = p; this.showPalette = false; try { localStorage.setItem('management_meeting_palette', p.name); } catch { } }

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
    const s = localStorage.getItem('management_meeting_palette'); if (s) { const f = this.paletteOptions.find(x => x.name === s); if (f) this.selectedPalette = f; }
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + this.loggedInDept)
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
}
