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
  material_for='';
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];
  material_subtype='';
  material_type = '';
  grade = '';
  status = 'quarantine';
  material_name = '';
  grades;
  item = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getStock();
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
   /*  this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    }); */
  }

  getStock() {
    this.service.get('store/raw.php?type=getStock').subscribe(response => {
      this.stocks = response;
      this. filterLab();
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadStock&material_subtype='+this.material_subtype + '&grade=' + this.grade);
  }
  filterLab() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['material_subtype'].toUpperCase().includes(this.material_subtype.toUpperCase())&&material['grade'].toUpperCase().includes(this.grade.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }


  AllRecord(){
    this.item =this.stocks;
    this.material_subtype='';
    this.grade='';
    // this.material_name='';
  }
}
