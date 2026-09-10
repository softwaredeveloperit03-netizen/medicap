import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-packing',
  templateUrl: './packing.component.html',
  styleUrls: ['./packing.component.css']
})
export class PackingComponent implements OnInit {

  stocks;
  vendors;

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getAllStock() {
    this.service.get('store/packing.php?type=getStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  download(){
    this.service.open('store/packing.php?type=downloadStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }

}
