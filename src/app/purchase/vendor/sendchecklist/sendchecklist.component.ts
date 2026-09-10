import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-sendchecklist',
  templateUrl: './sendchecklist.component.html',
  styleUrls: ['./sendchecklist.component.css']
})
export class SendchecklistComponent implements OnInit {
  vendors;
  checklist;
  selectedResult = [];
  vendor_no ='';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
    this.getVendorChecklist();
  }

  getVendors(){
    this.service.get('purchase/vendor.php?type=getVendorLog').subscribe(response=>{
      this.vendors = response;
    });
  }

  
  getVendorChecklist(){
    this.service.get('qa/vendorChecklist.php?type=getPrepareChecklist').subscribe(response=>{
      this.checklist = response;
    });
  }

  sendVendor(index){
    let temp = this.selectedResult;
    temp['vendor_no'] = this.vendor_no;
    this.selectedResult = this.checklist[index];
    this.service.post('purchase/vendor.php?type=sendChecklistQa',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('Checklist send Successfuly');
        this.getVendorChecklist();
      }else{
        alertify.error('Some error Occured');
      }
    });
  }

}
