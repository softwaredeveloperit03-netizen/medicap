import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];
  material_type = '';
  grade = '';
  status = '';

  constructor(private service: DataAccessService) { 
  }
  ngOnInit() {
    this.getReports();
  }

  getReports() {
    this.service.get('qc/specification/retest.php?type=getSpecificationsLog&material_type=' + this.material_type + '&grade=' + this.grade + '&status=' + this.status).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

  downloadReport(){
    this.service.open('qc/specification/retest.php?type=SpecificationLogPDF&material_type=' + this.material_type + '&grade=' + this.grade + '&status=' + this.status);
  }

  downloadPDF(type) {
    if(type == 'manual'){
      this.service.open('qc/specification/retest.php?type=SpecificationPDF&specification_no='+this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification/retest.php?type=SpecificationdigitalPDF&specification_no='+this.selectedSpec['specification_no'])
    }
  }

}
