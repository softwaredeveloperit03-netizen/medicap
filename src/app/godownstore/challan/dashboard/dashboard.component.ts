import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';



declare let alertify;



@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'approval', title: 'Inwards From Security', route: 'approval', icon: 'fa-sign-in-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'verification', title: 'Challans Verification', route: 'verification', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'hold', title: 'On Hold', route: 'hold', icon: 'fa-pause-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'correction', title: 'Correction', route: 'correction', icon: 'fa-edit', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this. get_rights();
    this. getPendingIndends();
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
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
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



  challanVeri = 0;


  getPendingIndends() {
    this.service
      .get('notification.php?type=pendingChallanVeri').subscribe((response: any) => {
        this.challanVeri = response['pendingChallanVeri'];
        if (this.challanVeri > 0) {
          alertify.warning(response['text']);
        }

      });
  }
}
