import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {
  specifications;
  dosages;
  isView = false;
  selectedSpec = [];
  materiallist = [];
  dosage_form = '';
  grade = '';
  status = '';
  maxdate;
  constructor(private service: DataAccessService) { 
  }
  ngOnInit() {
    this.getReports();
    this.getDosages();
  }

  getDosages(){
    this.service.get('qc/specification/inprocess.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  
  getReports(){
    this.service.get('qc/specification/inprocess.php?type=getSpecificationsLog&dosage_form=' + this.dosage_form + '&grade=' + this.grade + '&status=' + this.status).subscribe((response:any)=>{
      this.specifications = response;
    });
  }
  
  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
    this.selectedSpec['dosage_form'] = this.specifications['dosage_form'];
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('qc/specification.php?type=inprocessHistoryPDF&specification_no=' + this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification.php?type=inprocessHistorydigitalPDF&specification_no=' + this.selectedSpec['specification_no']);
    }
  }
  downloadReport(){
    this.service.open('qc/specification.php?type=inprocessHistoryReportPDF');
  }

}
