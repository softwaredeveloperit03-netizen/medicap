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

  constructor(private service: DataAccessService, private router: Router) {

  }

  ngOnInit() {
    this.getProducts();
    this.getEmployee();
    this.getUnits();
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  saveForm(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('rnd/qa/controlsample.php?type=saveFinishControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alertify.success( 'Record Saved Successfully');
        this.router.navigate(['/qa/controlsample/finish']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

  getEmployee() {
    this.service.get('employee.php?type=getEmp').subscribe(response => {
      this.employee = response;
    });
  }

}
