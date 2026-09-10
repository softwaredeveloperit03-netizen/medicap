import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'inprocess-formulation', title: 'Inprocess Formulation', route: 'inprocess-formulation', icon: 'fa-check-square', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'appr', title: 'Dispensing Approvals', route: 'appr', icon: 'fa-check-square', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'receiving', title: 'Disp Material Recvng', route: 'receiving', icon: 'fa-truck', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'requisition', title: 'Dispensing Requisition', route: 'requisition', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'activity', title: 'Dispensing Activity', route: 'activity', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'receiving', title: 'Dispensed Material Receiving', route: 'receiving', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'status', title: 'Dispensed Status', route: 'status', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.get_rights();
    this.getAcceptedRequests();
  }
  results
  getAcceptedRequests() {
    this.service.get('store/dispensing.php?type=get_Dispensing_Requests_prod_checking&material_type=Raw Material').subscribe(response => {
      this.results = response;
      console.log('this.results :>> ', this.results);
    });
  }


 rights;
  righ;
  isapprover
  isuser = 'No';
  ischecker = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
 
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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

}
