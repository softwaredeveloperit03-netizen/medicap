import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-check-shortages',
  templateUrl: './check-shortages.component.html',
  styleUrls: ['./check-shortages.component.css']
})
export class CheckShortagesComponent implements OnInit {

  results;
  isNewBP = false;
  isView = false;
  closeLink = '/production';

  
  batches;
  dosage_form = '';
  product_name = '';
  product_code = '';
  grade = '';
  grades;
  products;
  bom_no;
  materials;
  isnote = false;
  shortMaterial = [];

  bombtn = false;
  isBOM = false;
  bom = {};
  isShowAPI = false;
  selectedMaterial = [];
  selectedProduct = [];
  selectedResult = [];
  selectedBatch = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    const url = this.router.url || '';
    this.closeLink = url.includes('prod-f-ebmr') ? '/fproduction' : '/production';
    this.getProducts();
  }

  

  getProducts() {
    this.service.get('production/shortage.php?type=getProducts').subscribe(response => {
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

 

  /*getBatchSizes(index) {
    index = index - 1;
    this.product_code = this.grades[index].product_code;
    this.service.get('production.php?type=getBatchSizes&product_code=' + this.product_code).subscribe(response => {
      this.batches = response;
    });
  }*/

  /*getBOM() {
    this.service.get('production/shortage.php?type=getbombyno&bom_no=' + this.bom_no).subscribe(response => {
      this.bombtn = true;
      this.bom = response;
      this.materials = response['materials'];

      for (let i = 0; i < this.materials.length; i++) {
        if (this.materials[i].isShortage == 'yes') {
          this.isnote = true;
          this.shortMaterial = this.materials[i];
        }
      }
    });
  }*/

  showAPICalc(index) {
    this.selectedMaterial = this.materials[index];
    console.log(this.selectedMaterial['materials']);
    this.isShowAPI = true;
  }

  saveBatchPlanning() {
    if (this.dosage_form == '' || this.product_code == '' || this.bom_no == '' || this.materials.length == 0) {
      alert('All fields are required');
      return;
    }
    let temp = {};
    temp['dosage_form'] = this.dosage_form;
    temp['product_code'] = this.product_code;
    temp['bom_no'] = this.bom_no;
    temp['materials'] = this.materials;
    this.service.post('production.php?type=saveBatchPlanning', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alert('saved successfully');
        this.isNewBP = false;
        this.product_name = '';
        this.product_code = '';
        this.dosage_form = '';
        this.grade = '';
      } else {
        alert('error');
      }
    });
  }

  addIndend(index, indend) {
    if (indend == 'no') {
      this.materials[index].indend = 'yes';
    } else if (indend == 'yes') {
      this.materials[index].indend = 'no';
    }
  }

  viewBatch(index) {
    this.selectedBatch = this.results[index];
    this.isView = true;
  }

}
