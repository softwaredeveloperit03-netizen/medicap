import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-inventory',
  templateUrl: './inventory.component.html',
  styleUrls: ['./inventory.component.css'],
  providers:[DatePipe]
})
export class InventoryComponent implements OnInit {
  isView = false;
  results;
  from_date = '';
  to_date = '';
  selectedResult = [];
  selectedGRN = [];
  issued = [];
  product_name = '';
  product_type = '';
  isView1 = false;
  results1;
  isNewIssue = false;
  products;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    // this.getMaterialOutDetails();
    this.getProducts();
    this.getDispatchProducts();
  }
  fg_sub_materials=[]
  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe(response => {
      // let data =response;
      if (value == "") {
        return;
      }
      let idx = -1;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      if (idx >= 0) {
        let data = response[idx]
        this.fg_sub_materials = data['sub_materials'];
        // this.checkCopyFrom();
      } else {
        this.fg_sub_materials = []
      }
    });
  }
  
  getDispatchProducts() {
    this.service.get('dispatch.php?type=getDispatchProducts').subscribe(response => {
      this.results = response;
      this.results1 = response;
      // this.filterEquipment();
    });
  }
  

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  view1(index) {
    let grns = this.selectedResult['grns'];
    this.selectedGRN = grns[index];
    this.issued = this.selectedGRN['issued'];
    this.isView1 = true;
  }

  download(){
    this.service.open('dispatch/opening.php?type=downloadStock');
  }

  downloadp() {
    this.service.open('store/bincard.php?type=downloadView&id=' + this.selectedResult['id']);
  }
  downloadgrn() {
    this.service.open('store/bincard.php?type=downloadGrn&id=' + this.selectedResult['id']);
  }

  

  downloadIssueRecords() {
    this.service.open('store/bincard.php?type=downloadMaterialBinCard&ar_no=' + this.selectedGRN['ar_no']);
  }

  

  
 

}
