import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approved',
  templateUrl: './approved.component.html',
  styleUrls: ['./approved.component.css']
})
export class ApprovedComponent implements OnInit {

  isCalculator = false;
  stocks;
  vendors;

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  material_for='';
  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'Approved';
  material_name = '';
  item = [];
  lod_per = 0;
  stock_qty = 0;
  lod_qty = 0;
  dry_qty = 0;
  grades;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getStock();
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getStock() {
    this.service.get('store/raw.php?type=getStock&status=Approved&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
      this.filterItem();
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadRawapproved&material_name='+this.material_name);
  }

  lodcalculation() {
    this.lod_qty = +parseFloat(((this.stock_qty * this.lod_per) / 100) + '').toFixed(1);
    this.dry_qty = this.stock_qty - this.lod_qty;
  }
  filterItem() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }

  AllRecord(){
    this.item =this.stocks;
    this.material_name='';
    
}
}
