import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-test',
  templateUrl: './test.component.html',
  styleUrls: ['./test.component.css']
})
export class TestComponent implements OnInit {

  stocks;
  vendors;
  item = [];
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'under test';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getStock();
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
    this.service.open('store/packing.php?type=downloadStock&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['material_type'].toUpperCase().includes(this.material_type.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
}
