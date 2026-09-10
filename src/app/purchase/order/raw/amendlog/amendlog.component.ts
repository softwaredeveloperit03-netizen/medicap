 import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify: any;


@Component({
  selector: 'app-amendlog',
  templateUrl: './amendlog.component.html',
  styleUrls: ['./amendlog.component.css']
})
export class AmendlogComponent implements OnInit {

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
 

  status='';
  vendor_no='';
  departments;
  item = [];
  vendors;
  plant_id:any;
  selectedBill: any;
  selectedShip: any;
    totalAmount: number;
  constructor(private service:DataAccessService,  private router: Router) {
 
  }

  ngOnInit() {
    this.getPendingPO('f');
    this.getVendors();
    this.getDepartment();
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
      //  this.getLogs();
      this.currentPage=1;
      this.pageSize =10;
      
    }
    // ---------------------------------------------------------------------//
    material_type = '';
  getPendingPO(value) {
    // this.service.get('purchase/po/raw.php?type=getPOLog&vendor_no='+this.vendor_no+'&from_date='+this.from_date +'&to_date=' + this.to_date).subscribe(response => {
    this.service.get('purchase/po/raw.php?type=getAllRevisedPOLog&material_type='+value).subscribe(response => {
      this.orders = response;
      // this.filterItem();
    });
  }

  serch_po(value){
    this.service.get('purchase/po/raw.php?type=search_po&value='+value).subscribe(response => {
      this.orders = response;
     });
  }

 

  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    })
  }

  getVendors(){
    this.service.get('purchase/vendor.php?type=getVendorLog').subscribe(response=>{
      this.vendors = response;
    });
  }
 

  formatDate(dateString: string): string {
    // Check if the input date string is valid
    if (!dateString) {
      return '-';
    }
  
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthIndex = date.getMonth();
    const year = date.getFullYear().toString().slice(-2); // Get last two digits of the year
  
    return `${day}-${monthNames[monthIndex]}-${year}`;
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
      // console.log('array',this.selectedShip);
    })
  }

  viewOrder(id: any) {  
    const index = this.orders.findIndex((obj: any)=>obj.id == id);
    if(index != -1) {
      this.selectedOrder = this.orders[index];
    }
    this.selectedOrder.gstData = this.selectedOrder.gstSplitData;
    // this.selectedOrder.gstData = JSON.parse(this.selectedOrder.gstSplitData);
    // this.selectedOrder.add_term = JSON.parse(this.selectedOrder.additional_term);
    this.disc_amt=this.selectedOrder['net_total']*1*this.selectedOrder['discount']/100;
    this.total=this.selectedOrder['net_total']*1-this.disc_amt*1;
    this.selectedOrder = this.orders[index];
    console.log(this.selectedOrder);
     this.additional_terms =  this.selectedOrder['additional_term'];
    this.getBillCompany(this.selectedOrder['billcompany_code']);
    this.getShipCompany(this.selectedOrder['shipcompany_code']);
    this.terms_condition= this.selectedOrder['terms_conditions'];
     this.additional_terms = this.selectedOrder['additional_term'];
    // this.additional_terms = JSON.parse(this.selectedOrder['additional_term']);
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

 
 
  }


