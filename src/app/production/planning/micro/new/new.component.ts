import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  products;
  batches = [];

  selectedProduct = [];
  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getProducts();
  }

  getProducts() {
    this.service.get('production/plan.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
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
    temp['product_code']=this.selectedResult['product_code'];
    temp['stages']=this.selectedProduct['stages']
    this.service.post('planning/micro.php?type=savePlan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alert('Saved Successfully');
        this.router.navigate(['/planning/micro']);
      } else {
        alert('An error occured, Please try again');
      }
    });
  }

}
