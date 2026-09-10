import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approved',
  templateUrl: './approved.component.html',
  styleUrls: ['./approved.component.css']
})
export class ApprovedComponent implements OnInit {

  stocks;
  vendors;

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'Approved';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();
    this.getMaterials();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getMaterials(){
    this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    });
  }

  getAllStock() {
    this.service.get('store/raw.php?type=getStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('pdf1/store.php?type=rawStockLog&material_code=&from_date=&to_date=');
  }

}
