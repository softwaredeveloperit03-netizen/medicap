import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
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
export class DashboardComponent implements OnInit {
  ischecker = 'No';
  isapprover = 'No';
  loggedInDept: string | null = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
  }

  get cards(): QcDeptCard[] {
    const list: QcDeptCard[] = [
      {
        id: 'awaiting',
        title: 'Awaiting',
        route: 'awaiting',
        icon: 'fa-user-clock',
        category: 'Retraining',
        gradient: G.teal,
      },
    ];
    if (this.ischecker === 'Yes' || this.isapprover === 'Yes') {
      list.push({
        id: 'approval',
        title: 'Retrain for Approval',
        route: 'approval',
        icon: 'fa-check-circle',
        category: 'Retraining',
        gradient: G.amber,
      });
    }
    list.push({
      id: 'log',
      title: 'Retraining Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Retraining',
      gradient: G.navy,
    });
    return list;
  }

  get sidebarTabs() {
    return buildSidebarTabsFromCards(this.cards);
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.ischecker = r.ischecker || 'No';
        this.isapprover = r.isapprover || 'No';
      });
  }
}
