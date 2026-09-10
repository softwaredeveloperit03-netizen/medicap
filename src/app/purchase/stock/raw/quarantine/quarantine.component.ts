import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-quarantine',
  templateUrl: './quarantine.component.html',
  styleUrls: ['./quarantine.component.css']
})
export class QuarantineComponent implements OnInit {
  vendor_no='';
  stocks;
  vendors;
  material_for='';
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];
  item = [];
  material_type = '';
  grade = '';
  status = 'quarantine';
  material_name = '';
  grades;
  materials;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();
    this.getMaterials();
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }

  getAllStock() {
    this.service.get('store/raw.php?type=getStock&status='+this.status+'&material_for='+this.material_for+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
      this.filterItem();
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadRawQuarantine&material_name=' + this.material_name);
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