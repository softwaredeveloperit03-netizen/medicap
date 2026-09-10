import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

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
  rights: any;
  loggedInDept: string | null = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getOrders();
    this.get_rights();
  }

  get cards(): QcDeptCard[] {
    const list: QcDeptCard[] = [];
    if (this.isuser === 'Yes') {
      list.push({
        id: 'new',
        title: 'New PO / FO Entry',
        route: 'new',
        icon: 'fa-plus-circle',
        category: 'FO / PO',
        gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
      });
      list.push({
        id: 'processing',
        title: 'Processing PO / FO Entry',
        route: 'processing',
        icon: 'fa-cogs',
        category: 'FO / PO',
        gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
      });
    }
    if (this.isapprover === 'Yes') {
      list.push({
        id: 'approval',
        title: 'PO Approval',
        route: 'approval',
        icon: 'fa-check-circle',
        category: 'FO / PO',
        gradient: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
      });
    }
    list.push({
      id: 'log',
      title: 'PO Log',
      route: 'log',
      icon: 'fa-book',
      category: 'FO / PO',
      gradient: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
    });
    list.push({
      id: 'summery',
      title: 'Order Summary',
      route: 'summery',
      icon: 'fa-list-alt',
      category: 'FO / PO',
      gradient: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
    });
    return list;
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          encodeURIComponent(localStorage.getItem('emp_id') || '') +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
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
      });
  }

  getOrders(): void {
    this.service.get('marketing/po.php?type=getOrdersChart').subscribe(() => {});
  }
}
