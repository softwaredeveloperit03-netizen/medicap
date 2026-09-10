import { Component, OnInit } from '@angular/core';
import { FormGroup, FormArray, FormBuilder, FormControl, ValidatorFn } from '@angular/forms';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;
declare let alertify;

@Component({
  selector: 'app-new-change-control',
  templateUrl: './new.component.html'
})
export class NewChangeControlComponent implements OnInit {

  isImpactQuality = false;
  departments;
  constructor(private service: DataAccessService,private router: Router) {
  }

  ngOnInit() {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response:any) =>{
      this.departments =  response;
    });
  }

  saveForm(data) {
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }
    temp['departments'] = test;
    this.service.post('changecontrol.php?type=saveform', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/qa/changecontrol/']);
        alertify.success('Successfully send for Approval');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

}