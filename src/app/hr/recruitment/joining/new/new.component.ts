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
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
  getSection(index) {
    index = index - 1;
    let department = this.departments[index];
    this.sections = department['sections'];
  }
  getDesignation(idx) {
    this.designations =[];
    this.designations = this.departments[idx-1]['designations']
  }


  saveSalaryAnnexure(Form) {
    const temp = Form.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('All Field Requried');
      return;
    }
    // temp['earnings'] = this.earnings_list;
    // temp['deductions'] = this.detuctions_list;
    // temp['ctc'] = this.ctc_list;
    // temp['type'] = 'Employee';
    // temp['total_earnings'] = this.total_earn_month_amt;
    // temp['total_deductions'] = this.total_ctc_ded_month_amt;
    // temp['ctc_deductions'] = this.total_ded_month_amt;
    // temp['total_ctc'] = this.gross_salary;
    // temp['ctc_annual'] = this.ctc_annual;
    // temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt);
    // console.log(JSON.stringify(temp));

    this.service.post('hr/candidate.php?type=joiningreport', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Report Generated Successfully !');
        this.router.navigate(['/hr/recruitment/joining']);
        
      }
    });


  }

}
