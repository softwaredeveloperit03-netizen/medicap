import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
declare let alertify;
@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css'],
  providers:[DatePipe]
})
export class ReportComponent implements OnInit {
  vendors;
  isView = false;
  results;
  selectedResult: [];
  to_date='';
  from_date='';
  vendor_no='';
  isEdit=false;
  isQEdit = false;
  today='';
  item=[];
  vendor_name='';
  material_type='';
  entry_date='';
  gsts: any;
  plant_id:any;

  constructor(private service: DataAccessService,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getQuotationLog();
    this.getVendors();
    this.getGst();
    this.get_rights();
    this.getPendingQuotationsnotification();
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


  getQuotationLog(){
    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=1').subscribe(response => {
      this.results = response;
    });
  }

  getQuotationLog1(value){
    console.log(value)

    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=2&category='+value).subscribe(response => {
      this.results = response;
    });
  }
  getQuotationLog3(value){
    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=3&category='+value).subscribe(response => {
      this.results = response;
    });
  }




  
  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe((response:any) => {
      this.vendors = response;
      console.log("getVendors");
      console.log(response);
    });
  }

  view(index) {
    this.selectedResult = this.filteredMaterials[index];
    // console.log(this.results[index]);
    this.isView = true;
  }
  uploadQuatation(url){
    url = this.service.url + '../../upload/quotation/' + url;
    window.open(url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
 

  download(){
    this.service.open('purchase/quotation_log.php?type=downloadQuotationLog&vendor_no='+this.vendor_no+'&entry_date='+this.entry_date)
  }

  editRawMaterial(){

  }
  filterVendor() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
    console.log(material);
      if (material['vendor_no']?.toUpperCase().includes(this.vendor_no?.toUpperCase()) &&       material.materials[0]['material_type']?.toUpperCase().includes(this.material_type.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  getGst(){
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }
  AllRecord(){
    this.vendor_no='';
    this.material_type='';
    this.filterVendor();
 }
 updateQuotation(data){

 }
 editData(){
   this.isEdit = false;
   this.isQEdit = true;
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




    
  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.results.filter(material => {
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



 

  unreadQuotations =0;
  
  getPendingQuotationsnotification() {
    this.service.get('purchase/quotation.php?type=getPendingQuotationsForNotification').subscribe(response  => {
      this.unreadQuotations = response['Pending_quatation'];
      if(this.unreadQuotations > 0){
        alertify.error(response['text']);
      }
    });
  }

}
