import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  isView = false;
  specifications = [];
  selectedSpec = [];
  materiallist = [];
  product_code = '';
  grade = '';
  maxdate;

  material_type = '';
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getReports();
    this.getProducts();
    this.maxdate = new Date().toISOString().slice(0, 10);
  }

  getReports(){
    this.service.get('qc/specification/packing.php?type=getSpecificationsLog&material_type='+ this.material_type + '&grade='+ this.grade).subscribe((response:any)=>{
      this.specifications = response;
    })
  }

  getProducts(){

  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('qc/specification/packing.php?type=SpecificationPDF&specification_no=' + this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification/packing.php?type=SpecificationdigitalPDF&specification_no=' + this.selectedSpec['specification_no']);
    }
  }
  downloadReport(){
    this.service.open('qc/specification/packing.php?type=SpecificationLogPDF&material_type='+ this.material_type + '&grade='+ this.grade);
  }

}
