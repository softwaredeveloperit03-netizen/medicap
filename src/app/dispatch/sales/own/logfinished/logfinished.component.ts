import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-logfinished',
  templateUrl: './logfinished.component.html',
  styleUrls: ['./logfinished.component.css'],
  providers:[DatePipe]
})
export class LogfinishedComponent implements OnInit {

  
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
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   
   this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.salesLog();
    this.getClients();
    
    this.get_rights();
  }
 
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
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
