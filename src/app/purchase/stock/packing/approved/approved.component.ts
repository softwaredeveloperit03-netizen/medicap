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
  material_subtype='';
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  item = [];
  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'Approved';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getStock();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getStock() {
    this.service.get('store/packing.php?type=getStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
      this.filterItem();
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/packing.php?type=downloadStockBookLog&vendor_no='+this.vendor_no+'&material_type='+this.material_type );
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['vendor_no'].toUpperCase().includes(this.vendor_no.toUpperCase())&&(material['material_subtype'].toUpperCase().includes(this.material_subtype.toUpperCase()))) {
        this.item[this.item.length] = material;
      }
    }
  }
}
