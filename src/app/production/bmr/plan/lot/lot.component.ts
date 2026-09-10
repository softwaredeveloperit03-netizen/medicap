import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-lot',
  templateUrl: './lot.component.html',
  styleUrls: ['./lot.component.css']
})
export class LotComponent implements OnInit {
  products;
  batches = [];
  isShow=false;
  selectedProduct = [];
  selectedResult = [];
  selectedStage=[];
  units;
  product_code='';
  company_unit='';
  lots;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getUnits();
  }

  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 
  getProducts(product_type) {
    this.service.get('production/bmr/plan.php?type=getProducts&product_type='+product_type).subscribe(response => {
      this.products = response;
    });
  } 

  getPendingLotsforConvert() {
    this.service.get('production/bmr/plan.php?type=getPendingLotsforConvert&company_unit='+ this.company_unit + '&product_code='+this.product_code).subscribe(response => {
      this.lots = response;
    });
  } 



  getProductDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
    this.batches = this.selectedProduct['batches'];
    this.isShow=true;
  }

  onChange(event, index) {
    let material = this.products[index];
    if (event) {
      material['selected'] = true;
    } else {
      material['selected'] = false;
    }
    this.products[index] = material;
  }

}
