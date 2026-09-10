import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormArray, FormGroup, FormControl, ValidatorFn } from '@angular/forms';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new-deviation',
  templateUrl: './new-deviation.component.html'
})
export class NewDeviationComponent implements OnInit {
  affecting_product = 'yes';
  affecting_equipment = 'no';

  form: FormGroup;
  ordersData = [];
  get ordersFormArray() {
    return this.form.controls.orders as FormArray;
  }
  constructor(private service: DataAccessService, private router:Router, private formBuilder: FormBuilder) {
    this.form = this.formBuilder.group({
      orders: new FormArray([], minSelectedCheckboxes(1))
    });
    of(this.getDepartments()).subscribe(orders => {
      this.ordersData = orders;
      this.addCheckboxes();
    });
  }
  private addCheckboxes() {
    this.ordersData.forEach(() => this.ordersFormArray.push(new FormControl(false)));
  }
  getDepartments() {
    return [
      { name: 'Store' },
      { name: 'Production' },
      { name: 'Quality Control' },
      { name: 'Packing' },
      { name: 'Marketing' },
      { name: 'Client' },
      { name: 'Regulatory Department' },
      { name: 'Management' },
      { name: 'HR' },
      { name: 'Engineering' }
    ];
  }
  ngOnInit() {
  }
  
  saveDeviation(FormData){
    const temp = FormData.value;
    temp['departmentlist'] = this.form.value.orders.map((checked, i) => checked ? this.ordersData[i].name : null).filter(v => v !== null);
    this.service.post('deviation.php?type=saveDeviations', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        this.closeDeviation();
        alert('Record Inserted Successfully');
      }
    })
  }
  closeDeviation(){
    this.router.navigateByUrl('deviation');
  }

}

function minSelectedCheckboxes(min = 1) {
  const validator: ValidatorFn = (formArray: FormArray) => {
    const totalSelected = formArray.controls
      .map(control => control.value)
      .reduce((prev, next) => next ? prev + next : prev, 0);

    return totalSelected >= min ? null : { required: true };
  };

  return validator;
}

