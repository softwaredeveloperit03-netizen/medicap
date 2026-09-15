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
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  trainig_cordinator = 'No';
  rights: any;
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
        id: 'batch-formula',
        title: 'Batch Formula',
        route: 'batch-formula',
        icon: 'fa-puzzle-piece',
        category: 'Planning',
        gradient: G.indigo,
      },
    ];

    if (this.isapprover === 'Yes') {
      list.push({
        id: 'batch-plan-approval',
        title: 'Plan Approval',
        route: 'batch-plan-approval',
        icon: 'fa-clipboard-check',
        category: 'Planning',
        gradient: G.slate,
      });
    }

    list.push(
      {
        id: 'batch',
        title: 'Approved Batch Plans',
        route: 'batch',
        icon: 'fa-tasks',
        category: 'Planning',
        gradient: G.navy,
      },
      {
        id: 'stplan',
        title: 'STP / Planning',
        route: 'stplan',
        icon: 'fa-calendar-alt',
        category: 'Planning',
        gradient: G.emerald,
      },
      {
        id: 'indend',
        title: 'Material Indent',
        route: 'indend',
        icon: 'fa-file-alt',
        category: 'Planning',
        gradient: G.steel,
      },
      {
        id: 'stock',
        title: 'Stock Book',
        route: 'stock',
        icon: 'fa-book',
        category: 'Planning',
        gradient: G.violet,
      },
      {
        id: 'purchase',
        title: 'Purchase Status',
        route: 'purchase',
        icon: 'fa-shopping-cart',
        category: 'Planning',
        gradient: G.rose,
      },
      {
        id: 'sops',
        title: 'SOP',
        route: 'sops',
        icon: 'fa-book',
        category: 'QMS',
        gradient: G.navy,
      }
    );

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
        this.qms_approver = r.qms_approver || 'No';
        this.dept_head = r.dept_head || 'No';
        this.isauditor = r.isauditor || 'No';
        this.plant_head = r.plant_head || 'No';
        this.shift_allocator = r.shift_allocator || 'No';
        this.trainig_cordinator = r.trainig_cordinator || 'No';
      });
  }
}
