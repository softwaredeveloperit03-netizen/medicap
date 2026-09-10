import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  client;
  from_date='';
  to_date='';
  client_code='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {    
   this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.salesLog();
    this.getClients();
  }
  salesLog(){
    this.service.get('dispatch/sales.php?type=getOrdersLog&client_code='+this.client_code+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.client=response;
    });
  }

  pdfUpload(){
    this.service.open('account/sales.php?type=salesReportPDF&id='+this.selectedResult['id']);
  }

}
