import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dasboard',
  templateUrl: './dasboard.component.html',
})
export class DasboardComponent implements OnInit {
  cards: QcDeptCard[] = [];

  isuser = 'No';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.get_rights();
  }

  private get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        const rights = Array.isArray(response) && response.length ? response[0] : null;
        this.isuser = rights?.isuser || 'No';
        this.buildCards();
      });
  }

  private buildCards(): void {
    const items: QcDeptCard[] = [];

    if (this.isuser === 'Yes') {
      items.push({
        id: 'new',
        title: 'New Form',
        route: 'new',
        icon: 'fa-file-alt',
        category: 'Modules',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      });
    }

    items.push({
      id: 'log',
      title: 'Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)',
    });

    this.cards = items;
  }
}
