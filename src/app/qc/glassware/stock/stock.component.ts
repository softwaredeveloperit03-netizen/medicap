import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stock',
  templateUrl: './stock.component.html',
  styleUrls: ['./stock.component.css'],
  providers:[DatePipe]
})
export class StockComponent implements OnInit {

  stocks;
  vendors;
  loading;
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  from_date='';
  to_date='';
  grades;
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd'); }

  ngOnInit() {
    this.getVendors();    
    this.getAllStock();

    /* 
    this.getMaterials(); */
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
/* 
  getMaterials(){
    this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    });
  } */

  getAllStock() {
    this.service.get('qc/glassware.php?type=getStock&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('qc/glassware.php?type=rawStockLog&material_code=&from_date=&to_date=');
  }

}
