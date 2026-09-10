import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html'
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New Change Control', route: '../new', icon: 'fa-plus-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Change Ctrl for Check', route: '../checking', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'review', title: 'Change Ctrl For Rev', route: '../review', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'implementation', title: 'Change Ctrl Impl.', route: '../implementation', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'log', title: 'Change Ctrl Trend/Log', route: '../log', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'correction', title: 'Correction', route: 'correction', icon: 'fa-wrench', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Change Control for Checking', route: '../checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'review', title: 'Change Control For Review', route: '../review', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'implementation', title: 'Change Control Implementation', route: '../implementation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'log', title: 'Change Control Trend / Log', route: '../log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'qms-changecontrol-correction', title: 'Correction', route: 'qms/changecontrol/correction', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  // isUser = false;
  // isChecker = false;
  // isApprover = false;

  constructor(private service:DataAccessService) {
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit(): void {
    this.get_rights();
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

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +        localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
      this.plant_head=this.rights[0].plant_head
      this.shift_allocator=this.rights[0].shift_allocator
    });
  }

}
