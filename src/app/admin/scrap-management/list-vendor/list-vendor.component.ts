import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-list-vendor',
  templateUrl: './list-vendor.component.html',
  styleUrls: ['./list-vendor.component.css']
})
export class ListVendorComponent implements OnInit {

  results1;
  results;
  selectedResult;
  selectedResult1;
  isView=false;
  id;
  state_code = '';
  states;
  vendor_for='';
  isVendor= false;
  vendor_type='';
  due_days='';
  pan_no='';
  mfg_lic='';
  gst_no='';
  contact_person='';
  email= '';
  state_code1 ='';
  city='';
  location='';
  address_corporate='';
  vendor_status='';
  vendor_name='';
  vendor_no='';

  vendor_name1 = '';
  filteredVendor = [];
  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.service.observableState.subscribe(response => {
      this.states=response;
    });
    this.getLogs();
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


  getLogs(){
    this.service.get('purchase/vendor.php?type=getVendorLog&vendor_for='+this.vendor_for+'&vendor_name='+this.vendor_name1).subscribe(response =>{
      this.results=response;
      this.searchVendor();
      console.log("crazy",this.results);
    });
  }

  searchVendor() {
    this.filteredVendor = [];
    for(let i = 0; i < this.results.length; i++) {
      let result = this.results[i];
      if (result['vendor_name'].toUpperCase().includes(this.vendor_name1.toUpperCase()) && result['vendor_for'].toUpperCase().includes(this.vendor_for.toUpperCase())) {
        this.filteredVendor[this.filteredVendor.length] = result;
      }
    }
  }

 
  edit(index) {
    this.selectedResult = this.results[index];
    this.vendor_type =this.selectedResult['vendor_type'];
    this.due_days =this.selectedResult['due_days'];
    this.pan_no =this.selectedResult['pan_no'];
    this.mfg_lic =this.selectedResult['mfg_lic'];
    this.gst_no =this.selectedResult['gst_no'];
    this.contact_person =this.selectedResult['contact_person'];
    this.email =this.selectedResult['email'];
    this.state_code1 =this.selectedResult['state_code'];
    this.city =this.selectedResult['city'];
    this.location =this.selectedResult['location'];
    this.address_corporate =this.selectedResult['address_corporate'];
    this.vendor_status =this.selectedResult['vendor_status'];
    this.vendor_name =this.selectedResult['vendor_name'];
    this.vendor_for =this.selectedResult['vendor_for'];
    this.vendor_no =this.selectedResult['vendor_no'];
    this.isVendor = true;
  }
  close(){
    this.isVendor = false;
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true; 
    console.log(this.id)
  }
  viewfile(link){
    window.open(this.service.url + 'upload/vendor/' + link);
  }
  updateVendor(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['vendor_no'] =  this.vendor_no;
  
    this.service.post('purchase/vendor.php?type=updateVendor', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getLogs();
        this.isVendor = false;
        alertify.success('Record Update successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  blacklist(index)
  {
    this.selectedResult = this.results[index];
    this.id = this.selectedResult['id']
    this.service.get('purchase/vendor.php?type=blacklistVendor&id='+this.id).subscribe(response =>{
      this.results1=response;
    }); 
    this.getLogs();
  }
  download(){
    this.service.open('purchase/vendor.php?type=downloadVendorLog&vendor_for='+this.vendor_for+'&vendor_name='+this.vendor_name1)
  }

}
