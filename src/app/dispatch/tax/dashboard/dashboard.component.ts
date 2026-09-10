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
  selectedResult=[];
  isView=false;
  client;
  to_date='';
  from_date='';
  client_code='';
  gst_app: any;
  gst_type: any;
  bill_curr: any;
  pay_mode: any;
  po_no: any;
  final_total: any;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {   
   this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
   this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getInvoicesLog();
    this.getClients();
    this.get_rights();
    this.getInvocieFromMasterGst()
    // this.downloadE_Invoice()
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




  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.client=response;
    });
  }

  getInvoicesLog(){
    this.service.get('dispatch/invoice.php?type=getInvoicesLog&client_code='+this.client_code+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
    // this.service.get('dispatch/sales.php?type=getOrdersLog&client_code='+this.client_code+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
    this.selectedResult=this.results[index];  
    // this.getCalculations();
    this.selectedResult=this.results[index];
    this.gst_app=this.selectedResult['gst_app'];
    this.gst_type=this.selectedResult['gst_type'];
    this.bill_curr=this.selectedResult['bill_curr'];
    this.pay_mode=this.selectedResult['pay_mode'];
    this.po_no=this.selectedResult['po_no'];
    this.to_date=this.selectedResult['po_date'];
    this.final_total=this.selectedResult['final_total'];
    console.log(this.selectedResult['sales_data']);
    // this.getCalculations();
  }
 
  downloadInvoice(){
    this.service.open('dispatch/invoice.php?type=invoicePDF&id='+this.selectedResult['id']);
  }

  getInvocieFromMasterGst(){
    this.service.get('dispatch/invoice.php?type=getMasterGstInvoice').subscribe(response=>{
      if (response['status'] == 'success') {
        alert('Record saved successfully');
      } else {
        alert('Failed: An error occured, please try again!');
      }   
     });
  }


  downloadE_Invoice(){
    console.log("inside E_invoice PDf")
    this.service.open('dispatch/invoice.php?type=downloadE_InvoiceLog')
  }



}
