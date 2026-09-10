import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  isuser = 'No';
  loggedInDept: string | null = localStorage.getItem('department');

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.get_rights();
  }

  get cards(): QcDeptCard[] {
    const cards: QcDeptCard[] = [
      { id: 'new', title: 'Palette Master', route: 'new', icon: 'fa-boxes', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
      { id: 'log', title: 'Generate Barcode', route: 'log', icon: 'fa-barcode', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    ];
    if (this.isuser === 'Yes') {
      cards.unshift({
        id: 'mannual',
        title: 'Masters',
        route: 'mannual',
        icon: 'fa-database',
        category: 'Modules',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      });
    }
    return cards;
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe({
        next: (response: any) => {
          const r = Array.isArray(response) && response[0] ? response[0] : {};
          this.isuser = r.isuser || 'No';
        },
      });
  }
}
