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
  results = [];

  selectedMOA = [];
  materiallist = [];
  resultslength = 0;
  material_code = '';
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getReports();
    this.getProductList();
  }
  getProductList(){
    this.service.get('qc/method.php?type=getRetestMOAMaterial').subscribe((response:any)=>{
      this.materiallist = response;
    })
  }

  getReports() {
    this.service.get('qc/method.php?type=getRetestMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.results = response;
      this.resultslength = this.results.length;
    });
  }

  view(index) {
    this.selectedMOA = this.results[index];
    this.isView = true;
  }

  clearrecords(){
    this.material_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getReports();
  }

  download(sign, value){
    if(sign == 'manual'){
      this.service.open('pdf1/moa.php?type=RetestMOA&id='+value)
    }else{
      this.service.open('pdf1/moa.php?type=RetestMOAdigital&id='+value)
    }
  }

  downloadReport(){
    this.service.open('pdf1/moa.php?type=RetestMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
