import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
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

  material_type = '';
  material_code = '';
  entries;
  products;
  employee;
  product;
  material_name;
  sample_product;
  batch;
  releaseid;

  selectedProduct;

  constructor(private service: DataAccessService, private router: Router) {

  }

  ngOnInit() {
    this.getControlsamples();
    this.getEmployee();
  }

  getProducts(){
    this.service.get('qa/controlsample.php?type=getProducts&material_type=' + this.material_type).subscribe(response => {
      this.products = response;
    });
  }

 

  onProductChange(index) {
    this.selectedProduct = this.sample_product[index];
  }

  saveForm(data) {
    if (!data.valid) {
      alertify.warning('All fields are required!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.isNew = false;
        this.getControlsamples();
        data.reset();
        alertify.success( 'Saved Successfully');
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

  getControlsamples() {
    this.service.get('control_sample.php?type=getControlsamples').subscribe(response => {
      this.entries = response;
    });
  }


  close() {
    this.router.navigate(['/control-sample-dashboard']);
  }

}
