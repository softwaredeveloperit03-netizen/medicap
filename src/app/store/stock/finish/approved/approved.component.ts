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

  lod_per = 0;
  stock_qty = 0;
  lod_qty = 0;
  dry_qty = 0;
  grades;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getAllStock() {
    this.service.get('store/raw.php?type=getStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }

  lodcalculation() {
    this.lod_qty = +parseFloat(((this.stock_qty * this.lod_per) / 100) + '').toFixed(1);
    this.dry_qty = this.stock_qty - this.lod_qty;
  }

}
