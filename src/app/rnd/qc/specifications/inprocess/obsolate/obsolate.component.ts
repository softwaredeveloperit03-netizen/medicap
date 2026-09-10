import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-obsolate',
  templateUrl: './obsolate.component.html',
  styleUrls: ['./obsolate.component.css']
})
export class ObsolateComponent implements OnInit {
  specifications;
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  material_code = '';
  fromdate = '';
  todate = '';
  maxdate;
  constructor(private service: DataAccessService,private datePipe: DatePipe,) {
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getReports();
  }
  clearrecords(){

  }
  getReports() {
    this.service.get('qc/specification.php?type=inprocessobsolate').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.selectedSpec = this.specifications[index];
    this.isViewSpecification = true;
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('qc/specification.php?type=inprocessObsolatePDF&specification_no=' + this.selectedSpec['specification_no']);
    }else{
      this.service.open('qc/specification.php?type=inprocessObsolatedigitalPDF&specification_no=' + this.selectedSpec['specification_no']);
    }
  }
  downloadReport(){
    this.service.open('qc/specification.php?type=inprocessObsolateReportPDF');
  }

}