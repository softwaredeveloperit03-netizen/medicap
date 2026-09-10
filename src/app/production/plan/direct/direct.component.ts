import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-direct',
  templateUrl: './direct.component.html',
  styleUrls: ['./direct.component.css']
})
export class DirectComponent implements OnInit {

  dosages;
  products;
  batches = [];

  selectedProduct = [];
  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDosages();
  }

  getDosages() {
    this.service.get('production/plan.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts(index) {
    index = index - 1;
    if (index !== -1) {
      this.products = this.dosages[index].products;
    }
  } 


  getProductDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedProduct = this.products[index];
      this.batches = this.selectedProduct['batches'];
    } else {
      this.batches = [];
    }
  }

  getBatchDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.batches[index];
    } else {
      this.batches = [];
    }
  }

  saveBatchPlan(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;
    temp['plan_no']=this.selectedResult['plan_no'];
    temp['id']=this.selectedResult['id'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['dosage_form']=this.selectedResult['dosage_form'];
    temp['materials']=this.selectedResult['materials'];
    temp['stages']=this.selectedResult['stages'];
    temp['mfr_no']=this.selectedResult['mfr_no'];
    this.service.post('production/plan.php?type=saveBatchPlan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alert('Saved Successfully');
      this.router.navigate(['/production/plan']);
      } else {
        alert('An error occured, Please try again');
      }
    });
  }

}
