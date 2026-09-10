import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  dep_name;
  ngOnInit(): void {
    this.dep_name=localStorage.getItem('department')
    this.get_rights();
  }
  loggedInDept;
  rights;
isuser;
ischecker;
isapprover;
qms_approver;
dept_head;
isauditor;
plant_head;
shift_allocator;
trainig_cordinator;
  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('loger_id') +
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
        this.trainig_cordinator = this.rights[0].trainig_cordinator;
      });
  }
  
  //---------------------------------------------------------------------------------//
}

