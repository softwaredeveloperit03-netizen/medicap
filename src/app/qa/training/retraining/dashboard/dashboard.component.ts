import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'awaiting', title: 'Awaiting', route: 'awaiting', icon: 'fa-user-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Retraining for Approval', route: 'approval', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Retraining Log', route: 'log', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];




  constructor(private service:DataAccessService,private router:Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

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
  //---------------------------------------------------------------------------------//
  onChangeTraining(trainingType: string) {
    console.log(trainingType);
    switch(trainingType) {
      case 'Need Based Training':
        this.router.navigate(['qa/training/need']);
        break;
      case 'On Job Training':
        this.router.navigate(['qa/training/job-training']);
        break;
      case 'Document Training Training':
        this.router.navigate(['qa/training/document']);
        break;
      case 'QMS Training':
        this.router.navigate(['qa/training/qms']);
        break;
      case 'Retraining Training':
        this.router.navigate(['qa/training/retraining']);
        break;
      case 'Daily Training':
        this.router.navigate(['qa/training/daily']);
        break;
      case 'Induction Training':
        this.router.navigate(['qa/training/induction']);
        break;
      default:
        break;
    }
  }
}
