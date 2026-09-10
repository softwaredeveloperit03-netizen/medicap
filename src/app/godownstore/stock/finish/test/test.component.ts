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
  material_for='';

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'under test';
  material_name = '';
  grades;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAllStock();
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
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

}
