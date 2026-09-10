import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  isView = false;
  selectedResult = [];
  Deductions = [];
  Calculation = [];
  Earnings = [];
  constructor(
    private service: DataAccessService,
    private router: Router,
    private masterHubReturn: MasterHubReturnService
  ) {
    this.loggedInDept = localStorage.getItem('department');

    
  }

  ngOnInit(): void {
    this.getSalaryHead();
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
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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


  getSalaryHead() {
    this.service.get('hr/salaryhead.php?type=get_salary_heads').subscribe(response => {
      this.results = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.Deductions=this.selectedResult['Deductions']
    this.Calculation=this.selectedResult['CTC Calculation']
    this.Earnings=this.selectedResult['Earnings']
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/hr');
  }

}