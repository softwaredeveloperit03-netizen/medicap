import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-qc',
  templateUrl: './qc.component.html',
  styleUrls: ['./qc.component.css'],
  providers:[DatePipe]
})
export class QcComponent implements OnInit {

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
    this.getMaterialOutDetails();
  }

  
  getMaterialOutDetails() {
    this.service.get('ipqc/finish.php?type=getProducts').subscribe(response => {
      this.results = response;
      this.results1 = response;
      // this.filterEquipment();
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

  download() {
    this.service.open('store/bincard.php?type=downloadMaterialLog&product_type=' + this.product_type);
  }
  downloadp() {
    this.service.open('store/bincard.php?type=downloadView&id=' + this.selectedResult['id']);
  }
  downloadgrn() {
    this.service.open('store/bincard.php?type=downloadGrn&id=' + this.selectedResult['id']);
  }

  saveIssuedEntry(data) {
    if (!data.valid) {
      alertify.error('Invalid Data!');
      return;
    }
    let temp = data.value;
    temp['grn_no'] = this.selectedGRN['grn_no'];
    temp['ar_no'] = this.selectedGRN['ar_no'];
    temp['material_code'] = this.selectedGRN['material_code'];
    temp['unit'] = this.selectedGRN['unit'];
    this.service.post('store/bincard.php?type=issueMaterial', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully!');
        this.isNewIssue = false;
        this.isView = false;
        this.isView1 = false;
        data.reset();
        this.getMaterialOutDetails();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  downloadIssueRecords() {
    this.service.open('store/bincard.php?type=downloadMaterialBinCard&ar_no=' + this.selectedGRN['ar_no']);
  }

  clear(){
    this.product_name = '';
    this.product_type = '';
    this.results1 = this.results;
  }


}
