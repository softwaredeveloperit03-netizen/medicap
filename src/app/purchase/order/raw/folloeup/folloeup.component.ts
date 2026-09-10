import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-folloeup',
  templateUrl: './folloeup.component.html',
  styleUrls: ['./folloeup.component.css'],
  providers:[DatePipe]
})
export class FolloeupComponent implements OnInit {
  is_hold_cancel = false;
  cancel_hold_flag="Cancel"
  po_status="";
  isView = false;
  orders;
  disc_amt=0;
  total=0;
  selectedOrder;
  remark = '';
  selectedMaterial=[];
  terms_condition:any=[];
  additional_terms:any=[];
  from_date='';
  to_date='';
  today='';

  status='';
  vendor_no='';
  departments;
  item = [];
  vendors;
  plant_id:any;
  selectedBill: any;
  selectedShip: any;
    totalAmount: number;
  constructor(private service:DataAccessService,private datePipe:DatePipe, private router: Router) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPendingPO();
 
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
    //----------------------For Pagination---------------------------------//

    currentPage: number = 1;
    pageSize: number = 10; // Default page size
  
    calculateStartSrNo(): number {
      return (this.currentPage - 1) * 10 ;
    }
    
    onPageChange(page: number) {
      this.currentPage = page;
      console.log(this.currentPage);
    }
    
    onPageSizeChange(event: any) {
      this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
    }
    viewf(){
      this.isView=false;
       this.currentPage=1;
      this.pageSize =10;
      
    }
    // ---------------------------------------------------------------------//

  getPendingPO() {
     this.service.get('purchase/po/raw.php?type=getAllPOLog').subscribe(response => {
      this.orders = response;
     });
  }

  serch_po(value){

    this.service.get('purchase/po/raw.php?type=search_po&value='+value).subscribe(response => {
      this.orders = response;
     });

  }

 

   
 followhistory;
 

  followUphistory(){
     
    this.service.get('purchase/po/email_file.php?type=followuphistory&po_no='+this.selectedOrder['po_no']).subscribe(response => {
      this.followhistory = response;
    })
  }

  getBillCompany(val){
    console.log(val);
    this.service.get('master/company.php?type=getCompanyByCode&company_code='+val).subscribe(response => {
      this.selectedBill=response[0];
    })
  }


  getShipCompany(val){
    console.log(val);
    this.service.get('master/company.php?type=getCompanyByCode&company_code='+val).subscribe(response => {
      this.selectedShip=response[0];
      console.log('array',this.selectedShip);
    })
  }

 
  viewOrder(id: any) {  
    const index = this.orders.findIndex((obj: any)=>obj.id == id);
    if(index != -1) {
      this.selectedOrder = this.orders[index];
    }
    this.selectedOrder.gstData = this.selectedOrder.gstSplitData;
    this.disc_amt=this.selectedOrder['net_total']*1*this.selectedOrder['discount']/100;
    this.total=this.selectedOrder['net_total']*1-this.disc_amt*1;
    this.selectedOrder = this.orders[index];
    console.log(this.selectedOrder);
     this.additional_terms =  this.selectedOrder['additional_term'];
    this.getBillCompany(this.selectedOrder['billcompany_code']);
    this.getShipCompany(this.selectedOrder['shipcompany_code']);
    this.terms_condition= this.selectedOrder['terms_conditions'];
     this.additional_terms = this.selectedOrder['additional_term'];
      this.disc_amt=this.selectedOrder['net_total']*1*this.selectedOrder['discount']/100;
    this.total=this.selectedOrder['net_total']*1-this.disc_amt*1;
    this.isView = true;
    if(this.selectedOrder['status'] == 'Hold'){
      this.po_status = "This PO Is On Hold";
    }else if(this.selectedOrder['status'] == 'Cancel'){
      this.po_status = "This PO Is Cancelled";
    }else{
      this.po_status='';
    }
    this.totalAmount = Number(this.selectedOrder['shipping_handling'] )+ this.selectedOrder['shipping_handling'] *Number(this.selectedOrder['shipping_gst'])/100;
  }


 

  download(){
    this.service.open('purchase/po/raw.php?type=downloadAllPOLog');
  }
 
 
  downloadPOReport()
  {
    this.service.open('purchase/po_print.php?type=downloadPOReport&id='+this.selectedOrder['id']);
    console.log(this.selectedOrder['id']);
  }





  delivery_schedule_date;



  folowup(id: any){
    const index = this.orders.findIndex((obj: any)=>obj.id == id);
    if(index != -1) {
      this.selectedOrder = this.orders[index];
    }
    this.selectedOrder = this.orders[index];
    this.delivery_schedule_date = this.selectedOrder['materials'][0]?.delivery_schedule_date;
    this.is_hold_cancel = true;
  }


  message = '';

  sendEmail(data){



    let temp = {};
    
    temp['po_no'] = this.selectedOrder['po_no'];
    temp['message'] = this.message;
    temp['deliverydate'] = this.delivery_schedule_date;


    this.service.post('purchase/po/email_file.php?type=sendfollowupEmail',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Email has been Send');
        this.is_hold_cancel = false;
        this.getPendingPO();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 

  ishistory =false;
 
  history(id: any){
    const index = this.orders.findIndex((obj: any)=>obj.id == id);
    if(index != -1) {
      this.selectedOrder = this.orders[index];
    }
    this. ishistory =true;
    this.followUphistory();
  }

  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.orders; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.orders.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
  getStatusColor(status: string): string {
    switch (status) {
      case 'Rejected':
        return 'brown';
      case 'Approved':
        return 'green';
      case 'Cancelled':
        return 'red';
      case 'On Hold':
        return 'blue';
      default:
        return 'black'; // Default color if status doesn't match any case
    }
  }
}

