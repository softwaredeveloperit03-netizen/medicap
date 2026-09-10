import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  specifications;
  dosages;
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  dosage_form = '';
  grade = '';
  maxdate;

  grades;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
    this.getDosages();
    this.getReports();
  }

  getDosages(){
    this.service.get('qc/specification/finish.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getReports() {
    this.service.get('qc/specification/finish.php?type=getSpecificationsLog&product_type=' + this.dosage_form + '&grade=' + this.grade).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.selectedSpec = this.specifications[index];
    this.selectedSpec['dosage_form'] = this.specifications['dosage_form'];
    this.isViewSpecification = true;
  }

  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('qc/specification/finish.php?type=SpecificationPDF&specification_no=' + this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification/finish.php?type=SpecificationdigitalPDF&specification_no=' + this.selectedSpec['specification_no']);
    }
  }
  downloadReport(){
    this.service.open('qc/specification/finish.php?type=SpecificationLogPDF&dosage_form=' + this.dosage_form + '&grade=' + this.grade);
  }

  clear(){
    this.dosage_form = '';
    this.grade = '';
    this.getReports();
  }

}
