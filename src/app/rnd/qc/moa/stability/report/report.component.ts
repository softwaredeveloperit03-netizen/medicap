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
  results;

  selectedMOA = [];
  productlist = [];
  resultlenght = 0;
  product_code = '';
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe,) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getReports();
    this.getProductsList();
  }
  getProductsList(){
    this.service.get('qc/method.php?type=getStabilityMOAMaterial').subscribe((response:any)=>{
      this.productlist = response;
    })
  }

  getReports() {
    this.service.get('qc/method.php?type=getStabilityMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
      this.results = response;
      this.resultlenght = this.results.length;
    });
  }

  view(index) {
    this.selectedMOA = this.results[index];
    this.isView = true;
  }

  clearrecords(){
    this.product_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getReports();
  }

  download(sign, value){
    if(sign == 'manual'){
      this.service.open('pdf1/moa.php?type=StabilityMOA&id='+value)
    }else{
      this.service.open('pdf1/moa.php?type=StabilityMOAdigital&id='+value)
    }
    
  }

  downloadReport(){
    this.service.open('pdf1/moa.php?type=StabilityMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }


}
