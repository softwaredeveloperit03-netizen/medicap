import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dispatch-report',
  templateUrl: './dispatch-report.component.html',
  providers:[DatePipe]
})
export class DispatchReportComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  client;
  to_date='';
  from_date='';
  client_code='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {   
   this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.getInvoicesLog();
    this.getClients();
  }
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.client=response;
    });
  }

  getInvoicesLog(){
    this.service.get('dispatch/invoice.php?type=getInvoicesLog&client_code='+this.client_code+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  downloadInvoice(){
    this.service.open('dispatch/invoice.php?type=invoicePDF&id='+this.selectedResult['id']);
  }

  pdfUpload() {}
}
