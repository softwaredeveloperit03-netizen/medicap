import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormArray, FormGroup, FormControl, ValidatorFn } from '@angular/forms';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {
  affecting_product = 'yes';
  affecting_equipment = 'no';

  departments;
  constructor(private service: DataAccessService, private router:Router) {
  }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.departments = [
      { department_name: 'Store', value: false},
      { department_name: 'Production', value: false},
      { department_name: 'Quality Control', value: false},
      { department_name: 'Packing', value: false},
      { department_name: 'Marketing', value: false},
      { department_name: 'Client', value: false},
      { department_name: 'Regulatory Department', value: false},
      { department_name: 'Management', value: false},
      { department_name: 'HR', value: false},
      { department_name: 'Engineering', value: false }
    ]
  }

  saveUserForm(data) {
    // if(!data.valid){
    //   alertify.error('All fields are required!');
    //   return;
    // }
    // let temp = data.value;

    // let test = [];
    // for (let i = 0; i < this.departments.length; i++) {
    //   let department = this.departments[i];
    //   if (department['status']) {
    //     test[test.length] = department['department_name'];
    //   }
    // }
    // temp['departments'] = test;
    if(!data.valid){
        alertify.error('All fields are required!');
        return;
      }
    this.service.post('qms/incident.php?type=saveIncident', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/qa/incidents']);
        alertify.success('Successfully send for Approval');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  close() {
    this.router.navigate(['/incident']);
  }
}

