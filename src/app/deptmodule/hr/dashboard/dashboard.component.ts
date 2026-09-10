import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'plant-head-hr-emp-list', title: 'Employee List', route: 'plant_head/hr/emp_list', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'plant-head-hr-attendance', title: 'Employee Attendance', route: 'plant_head/hr/attendance', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'plant-head-hr-apprisal', title: 'Appraisal Requests', route: 'plant_head/hr/apprisal', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'plant-head-hr-shiftappr', title: 'Shift Approval Request', route: 'plant_head/hr/shiftappr', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'plant-head-hr-requisition', title: 'Manpower Req. Approval', route: 'plant_head/hr/requisition', icon: 'fa-users', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'management-manage-approval', title: 'Recruitment Approval', route: 'management/manage_approval', icon: 'fa-user-tie', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'shift', title: 'Shift Management', route: 'shift', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'resignation', title: 'Employee Resignation', route: 'resignation', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'leaveapp', title: 'Leave Approval', route: 'leaveapp', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'leavelog', title: 'Leave Log', route: 'leaveLog', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'goveagency', title: 'Interview Approval', route: 'goveAgency', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'holiday', title: 'Holiday', route: 'holiday', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
