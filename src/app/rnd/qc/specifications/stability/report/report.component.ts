import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {

  specifications;
  dosages;
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  dosage_form = '';
  grade = '';
  status = '';
  maxdate;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDosages();
    this.getReports();
  }
  clearrecords(){

  }

  getDosages(){
    this.service.get('qc/specification/stability.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getReports() {
    this.service.get('qc/specification/stability.php?type=getSpecificationsLog&dosage_form=' + this.dosage_form + '&grade=' + this.grade + '&status=' + this.status).subscribe(response => {
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
      this.service.open('qc/specification/stability.php?type=SpecificationPDF&specification_no=' + this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification/stability.php?type=SpecificationdigitalPDF&specification_no=' + this.selectedSpec['specification_no']);
    }
    
  }
  downloadReport(){
    this.service.open('qc/specification/stability.php?type=SpecificationLogPDF');
  }

}
