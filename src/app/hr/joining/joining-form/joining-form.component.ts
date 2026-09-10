import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-joining-form',
  templateUrl: './joining-form.component.html',
  styleUrls: ['./joining-form.component.css']
})
export class JoiningFormComponent implements OnInit {

  isNew = false;
  employees;
  departments;
  qualifications;
  designations;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployees();
    this.getDepartments();
    this.getDesignations();
    this.getQualifications();
  }

  getEmployees() {
    this.service.get('qa.php?type=getEmployees').subscribe(response => {
      this.employees = response;
    });
  }

  getDepartments() {
    this.service.get('qa.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignations() {
    this.service.get('qa.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  getQualifications() {
    this.service.get('qa.php?type=getQualifications').subscribe(response => {
      this.qualifications = response;
    });
  }

  saveDirectEmployee(data) {
    let temp = data.value;
    this.service.post('qa.php?type=saveDirectEmployee', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Employee Added in List.\nLogin ID: ' + response['username'] + '\nPassword:' + temp['password']);
        data.resetForm();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
