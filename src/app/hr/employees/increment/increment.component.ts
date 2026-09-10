import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-increment',
  templateUrl: './increment.component.html',
  styleUrls: ['./increment.component.css']
})
export class IncrementComponent implements OnInit {

  employees;
    salryAnnexureResult:any
    salaryAnexureDetails:any
  constructor(private service: DataAccessService, private router: Router) {

  }


  ngOnInit() {
    this.getEmployees();
    this.get_rights();
  }
  isView=false
  viewDetails=false
  View=false
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
selectedResult=[]
selectedSalaryDetailsResult=[]
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




  getEmployees() {
    // this.service.get('hr/emp.php?type=getemp').subscribe(response => {
    this.service.get('hr/employee.php?type=getEmployeesList_increment_letter').subscribe(response => {
      this.employees = response;
    });
  }
  
  downloadLetter(emp_id,old_ctc,annexure_id,new_ctc,applicable_from) {
     this.service.open('hr/employee.php?type=download_Increment_letter&emp_code='+emp_id+'&last_ctc='+old_ctc+'&new_ctc='+new_ctc+'&annexure_id='+annexure_id+'&applicable_from='+applicable_from);
    console.log(emp_id);
    console.log(old_ctc);
  }

  view(index)
  {
    this.selectedResult=this.employees[index]
    this.View=true
    this.isView=true
    this.service.get('hr/employee.php?type=getEmployeesSalaryAnnexure&empid='+this.selectedResult['emp_id']).subscribe(response => {
      this.salryAnnexureResult = response;
    });
    
  }

  salaryAnnexureView(index)
  {

    this.selectedSalaryDetailsResult=this.salryAnnexureResult[index]
    this.viewDetails=true
    this.View=false
    this.isView=true
    this.service.get('hr/employee.php?type=getEmployeeSalaryAnnexureDetails&id='+this.selectedSalaryDetailsResult['id']).subscribe(response => {
      this.salaryAnexureDetails = response;
    });
    

  }

  closeView()
  {
    this.isView=false
    this.View=false
  }
  closedDetailsView()
  {
    this.viewDetails=false
    this.View=true
    this.isView=true

  }
}
