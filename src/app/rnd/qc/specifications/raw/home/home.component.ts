import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];
  material_type = 'API';
  grade = '';

  grades;
  constructor(private service: DataAccessService) { 
  }
  ngOnInit() {
    this.getReports();
    this.getGrades();
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getReports() {
    this.service.get('qc/specification/raw.php?type=getSpecificationsLog&material_type=' + this.material_type + '&grade=' + this.grade).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

  downloadReport(){
    this.service.open('qc/specification/raw.php?type=SpecificationLogPDF&material_type=' + this.material_type + '&grade=' + this.grade);
  }

  downloadPDF(type) {
    if(type == 'manual'){
      this.service.open('qc/specification/raw.php?type=SpecificationPDF&specification_no='+this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification/raw.php?type=SpecificationdigitalPDF&specification_no='+this.selectedSpec['specification_no'])
    }
  }

}
