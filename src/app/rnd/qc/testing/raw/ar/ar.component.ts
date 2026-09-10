import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-ar',
  templateUrl: './ar.component.html',
  styleUrls: ['./ar.component.css']
})
export class ArComponent implements OnInit {
  isView = false;
  samplings;

  selectedReport = [];

  material_name = "";
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getARReport();
  }

  getARReport() {
    this.service.get('qc/testing/raw.php?type=getTestingReport').subscribe(response => {
      this.samplings = response;
    });
  }

  viewReport(index) {
    this.selectedReport = this.samplings[index];
    this.isView = true;
  }
  downloadReport(){
    this.service.open('pdf1/testing.php?type=ARReportlog');
  }
  downloadPDF(ar_no, type){
    if(type == 'manual'){
      this.service.open('pdf1/testing.php?type=ARReport&ar_no='+ar_no);
    }else{
      this.service.open('pdf1/testing.php?type=ARReportdigital&ar_no='+ar_no);
    }
  }

}
