import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-quarantine',
  templateUrl: './quarantine.component.html',
  styleUrls: ['./quarantine.component.css']
})
export class QuarantineComponent implements OnInit {

  stocks;
  vendors;

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';

  material_type = '';
  grade = '';
  status = 'quarantine';
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
    this.service.get('store/packing.php?type=getStock&status='+this.status+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/packing.php?type=downloadStock&status='+this.status+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }

}
