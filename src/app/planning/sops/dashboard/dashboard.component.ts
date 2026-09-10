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
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights: any;
  loggedInDept: string | null = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
  }

  get cards(): QcDeptCard[] {
    const list: QcDeptCard[] = [];

    if (this.isapprover === 'Yes') {
      list.push({
        id: 'initiate',
        title: 'New SOP Request',
        route: 'initiate',
        icon: 'fa-file-alt',
        category: 'SOP Initiation',
        gradient: G.teal,
      });
    }

    list.push({
      id: 'draft',
      title: 'Prepare Draft',
      route: 'draft/0',
      icon: 'fa-file-alt',
      category: 'SOP Initiation',
      gradient: G.blue,
    });

    if (this.ischecker === 'Yes') {
      list.push({
        id: 'initiate-checking',
        title: 'SOPs Checkg for Dept Hd',
        route: 'initiate-checking',
        icon: 'fa-file-alt',
        category: 'SOP Initiation',
        gradient: G.slate,
      });
    }

    if (this.isuser === 'Yes') {
      list.push({
        id: 'new',
        title: 'Prepare final SOP',
        route: 'new',
        icon: 'fa-file-alt',
        category: 'New SOP & Revision',
        gradient: G.indigo,
      });
    }

    if (this.ischecker === 'Yes') {
      list.push({
        id: 'checking',
        title: 'SOP for Checking',
        route: 'checking',
        icon: 'fa-file-alt',
        category: 'New SOP & Revision',
        gradient: G.amber,
      });
    }

    list.push({
      id: 'log',
      title: 'SOP Log',
      route: 'log',
      icon: 'fa-file-alt',
      category: 'New SOP & Revision',
      gradient: G.navy,
    });

    if (this.isapprover === 'Yes') {
      list.push(
        {
          id: 'training',
          title: 'Training of SOP',
          route: 'training',
          icon: 'fa-file',
          category: 'Training & Implementation',
          gradient: G.emerald,
        },
        {
          id: 'implementation',
          title: 'Impl. of SOP',
          route: 'implementation',
          icon: 'fa-file',
          category: 'Training & Implementation',
          gradient: G.steel,
        },
        {
          id: 'distribution',
          title: 'SOP Dist. Record',
          route: 'distribution',
          icon: 'fa-book',
          category: 'Training & Implementation',
          gradient: G.violet,
        }
      );
    }

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
        this.rights = response;
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.isuser = r.isuser || 'No';
        this.ischecker = r.ischecker || 'No';
        this.isapprover = r.isapprover || 'No';
      });
  }
}
