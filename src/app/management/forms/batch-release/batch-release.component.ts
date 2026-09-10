import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-batch-release',
  templateUrl: './batch-release.component.html',
  styleUrls: ['./batch-release.component.css']
})
export class BatchReleaseComponent implements OnInit {

  isView = false;
  results = [];
  orders;
  product_name='';
  from_date = '';
  to_date = '';
  products;
  product_code='';
  selectedResult = [];
  constructor(private service: DataAccessService) {
    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = "";
    if (day > 0 && day < 10) {
      mon = "0" + day;
    }
    if (m > 0 && m < 10) {
      mon = "0" + m;
    }
    this.to_date = d.getFullYear() + "-" + mon + "-" + day;
    this.from_date = d.getFullYear() + "-" + mon + "-01";
  }
  ngOnInit() {
    this.getbatchrelease();
    this.getProducts();
  }

  getbatchrelease() {
    this.service.get('batch-release.php?type=getBatchReleaseLog&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response:any) => {
      this.results = response;
    });
  }
  getProducts() {
    var date = new Date();
    var from_date = new Date(date.getFullYear(), date.getMonth(), 1);
    this.service.get('batch-release.php?type=getProducts').subscribe((response:any) => {
      this.products = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  printchecking(value, mode){
    if(mode == 'manual'){
      this.service.open('pdf1/batch-release.php?type=checkingrecord&id='+value);
    }else{
      this.service.open('pdf1/batch-release.php?type=checkingrecorddigital&id='+value);
    }
    
  }
  
  printcertificate(value,mode){
    if(mode == 'manual'){
      this.service.open('pdf1/batch-release.php?type=certificate&id='+value);
    }else{
      this.service.open('pdf1/batch-release.php?type=certificatedigital&id='+value);
    }
  }
  printreport(){
    this.service.open('pdf1/batch-release.php?type=printrecord');
  }

}
