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

  isNew = false;
  products;
  employee;
  units;

  employees;
  constructor(private service: DataAccessService, private router: Router) {

  }

  ngOnInit() {
    this.getProducts();
    this.getEmployees();
    this.getUnit();
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  getUnit() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getEmployees() {
    this.service.get('qa/controlsample.php?type=getSamplingEmployees').subscribe(response => {
      this.employees = response;
    });
  }
  saveForm(data) {
    // if (!data.valid) {
    //   alertify.warning('All fields are required!');
    //   return;
    // }
    this.service.post('qa/controlsample.php?type=saveFinishControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alertify.success( 'Record Saved Successfully');
        this.router.navigate(['/qa/controlsample/finish']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }



}
