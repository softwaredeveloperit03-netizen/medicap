import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-changedepdash',
  templateUrl: './changedepdash.component.html',
  styleUrls: ['./changedepdash.component.css'],
})
export class ChangedepdashComponent implements OnInit {


  employees;
  isDIGI = false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getEmployees();
    this.get_rights();
  }


  isuser = 'No';
  rights;


  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
    });
  }

  getEmployees() {
    this.service.get('hr/employee.php?type=getdeptChangeemp').subscribe((response) => {
        this.employees = response;
    });
  }

  selectedResult = [];

   
  designations = [];

  savedeptChange() {


    let temp = {};

    temp['change_designation'] = this.selectedResult['change_designation'];
    temp['change_department'] = this.selectedResult['change_department'];
    temp['Olddepartment'] = this.selectedResult['department'];
    temp['Olddesignation'] = this.selectedResult['designation'];
    temp['emp_id123'] = this.selectedResult['emp_id'];
    temp['status'] = this.status;

    this.service.post('hr/employee.php?type=changeDepartmentApprove',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          this.getEmployees();
          alertify.success('Department Change Request '+ this.status +' Successfully');
          this.status = '';
          this.selectedResult = [];
        } else {
          alertify.error(response['status']);
        }
    });
  
  }


  status = '';
  openDigiSign(user,status) {
    this.isDIGI = true;
    this.selectedResult = user;
    this.status = status;
  }

  loginPassward = '';
  digiSign(data) {

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service.get('login.php?type=checkDigiSIgn&mpin=' +this.loginPassward +'&emp_id=' +localStorage.getItem('emp_id')).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward = '';
          this.savedeptChange();
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }


 
  
}
