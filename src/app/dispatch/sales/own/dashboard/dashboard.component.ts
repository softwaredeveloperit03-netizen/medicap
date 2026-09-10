import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'rawlogcomponent', title: 'Raw Material Product', route: 'RawlogComponent', icon: 'fa-box-open', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'logfinishedcomponent', title: 'Finished Product', route: 'LogfinishedComponent', icon: 'fa-cube', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'loan', title: 'Loan License', route: 'loan', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'third', title: 'Third Party', route: 'third', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'tender', title: 'Tender / Rate Contract', route: 'tender', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
  ];


  results;
  isView=false;
  calculated = {
    gross: 0,
    disc: 0,
    taxable: 0,
    other: 0,
    round: 0,
    net: 0,
  };
  selectedResult=[];
  client;
  from_date='';
  to_date='';
  client_code='';
  gst_app: any;
  gst_type: any;
  bill_curr: any;
  pay_mode: any;
  po_date: any;
  po_no: any;
  wholeProducts: any[]=[];
  final_total=0;
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
    this.gst_app=this.selectedResult['gst_app'];
    this.gst_type=this.selectedResult['gst_type'];
    this.bill_curr=this.selectedResult['bill_curr'];
    this.pay_mode=this.selectedResult['pay_mode'];
    this.po_no=this.selectedResult['po_no'];
    this.po_date=this.selectedResult['po_date'];
    this.final_total=this.selectedResult['final_total'];
    console.log(this.selectedResult['sales_data']);
    let data = JSON.parse(this.selectedResult['sales_data']);
    this.wholeProducts= data;
    this.getCalculations();
    this.isView=true;
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.client=response;
    });
  }

  pdfUpload(){
    this.service.open('dispatch/sales.php?type=downloadOrder&id='+this.selectedResult['id']);
    console.log(this.selectedResult['id']);
  }

  getCalculations(){
    let data = this.selectedResult['materials'];
    this.calculated={
      gross: 0,
      disc: 0,
      taxable: 0,
      other: 0,
      round: 0,
      net: 0,
    }
    data.map(res=>{
      console.log(res.disc_total);
      this.calculated.gross += parseFloat(res.gross_total);
      // console.log(res);
      this.calculated.disc += parseFloat(res.disc_total);
      this.calculated.taxable += parseFloat(res.taxable);
      this.calculated.other += parseFloat(res.other);
      this.calculated.net += parseFloat(res.net_total);
    });
    console.log(this.calculated); 
  }
}
