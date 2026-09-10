import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  earnings_list = [];
  candidates = [];
  last_Emp_code='0';
  last_Emp;
  selectedCandidate =[];
  pending_employees;
  departments;
  sections;
  designations;
  date;
  entry_date;
  location;
  reporting;
  candidate;
  letter_date;
  department;
  designation;
  join_date;
  emp_no;
  employees;

  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getLastEmployee();
    this.getpendingcandidate();
    this.getCandidates();
    this.getDepartments();

  } 
  getCandidates() {
    this.service.get('hr/candidate.php?type=getPendingJoiningCandidates&department_name=').subscribe(response => {
      this.pending_employees = response;
    });
  }
  getLastEmployee(){
    this.last_Emp=[];
    this.service.get('/hr/employee.php?type=get_last_emp_code').subscribe(response => {
      this.last_Emp = response;
      this.last_Emp_code = response[0]['emp_id']
    });
  }

  
  setCandidate(idx){
    this.selectedCandidate = this.pending_employees[idx-1];
  }

  getpendingcandidate() {
    this.service.get('hr/candidate.php?type=pendingoffer').subscribe((response: any) => {
      this.candidates = response;
    });
  }
  // getDepartments() {
  //   this.service.get('hr/employee.php?type=get_department_by_designation')
  //     .subscribe(response => {
  //       this.departments = response;
  //     });
  // } 
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe(response => {
      this.departments = response;
    });
}
  getEmployees(dept, designation) {
    this.service.get('hr/employee.php?type=getDepartmentEmployees' + '&department_name=' + dept + '&designation=' + designation)
    .subscribe(response => {
      this.employees = response;
    });
  }
  getDesignation(data) {
    let department = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }
  getSection(index) {
    index = index - 1;
    let department = this.departments[index];
    this.sections = department['sections'];
  }
  // getDesignation(idx) {
  //   this.designations =[];
  //   this.designations = this.departments[idx-1]['designations']
  // }


  // savejoining(Form) {
  //   const temp = Form.value;

  //   this.service.post('hr/candidate.php?type=joiningreport', JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alert('Report Generated Successfully !');
  //       this.router.navigate(['/hr/recruitment/joining']);
        
  //     }
  //   });


  // }

  savejoining(Form){

    
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }



    let temp = Form.value;
    // temp['result']=this.result;
    this.service.post('hr/candidate.php?type=joiningreport', JSON.stringify(temp)) 
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }

}
