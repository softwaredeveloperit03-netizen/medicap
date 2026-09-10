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
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getFinishMOA();
    this.getMaterialList();
  }
  getMaterialList(){
    this.service.get('qc/method.php?type=getFinishMOAMaterial').subscribe((response:any)=>{
      this.productlist = response;
    })
  }

  getFinishMOA() {
    this.service.get('qc/method.php?type=getFinishMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
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
    this.getFinishMOA();
  }

  download(sign, value){
    if(sign == 'manual'){
      this.service.open('pdf1/moa.php?type=FinishMOA&id='+value)
    }else{
      this.service.open('pdf1/moa.php?type=FinishMOAdigital&id='+value)
    }
  }

  downloadReport(){
    this.service.open('pdf1/moa.php?type=FinishMOALog&product_code='+this.product_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
