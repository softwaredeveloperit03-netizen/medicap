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
  resultslength = 0;
  product_code = '';
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getReports();
    this.getProductList();
  }
  getProductList(){
    this.service.get('qc/method.php?type=getInprocessMOAMaterial').subscribe((response:any)=>{
      this.productlist = response;
    })
  }

  getReports() {
    this.service.get('qc/method.php?type=getInprocessMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
      this.results = response;
      this.resultslength = this.results.length;
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
      this.service.open('pdf1/moa.php?type=InprocessMOA&id='+value)
    }else{
      this.service.open('pdf1/moa.php?type=InprocessMOAdigital&id='+value)
    }
  }

  downloadReport(){
    this.service.open('pdf1/moa.php?type=InprocessMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
