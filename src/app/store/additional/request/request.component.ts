import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isView= false;
  selectedResult: [];
  results;
  remark;

  constructor(private service: DataAccessService) { }


 
  ngOnInit() { 
    this.getData();
   
  }
    getData() {
     
      this.service.get('production/additional_material.php?type=getApprovmaterials').subscribe(response => {
        this.results = response;
      
      });
    }


  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  approve() {
    if (this.selectedResult['material_type'] == '') {
      alertify.error('Select Type!');
      return;
    }
    if (this.selectedResult['qty'] == null) {
      alertify.error('Qty is Required');
      return;
    }
      this.service.post('production/additional_material.php?type=request_acceptmaterial&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  reject() {
    if (this.selectedResult['material_type'] == '') {
      alertify.error('Select Type!');
      return;
    }

    this.service.post('production/additional_material.php?type=requestmaterial&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success("update successfully");
        // this.getPendingPO();
        this.remark = '';
       
      } else {
        alertify.error('Failed to Update PO, Please try again!');
      }

      //  this.AllRecord();
    });
  }

}
