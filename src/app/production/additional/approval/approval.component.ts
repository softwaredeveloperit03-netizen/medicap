import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView= false;
  selectedResult: [];
  results;
  remark;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getData();
  }
  getData() {
   
    this.service.get('production/additional_material.php?type=getadditional').subscribe(response => {
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
      this.service.post('production/additional_material.php?type=approveAdditional&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed to Update, Please try again!');
      }
    });
  }

  reject() {
    if (this.selectedResult['material_type'] == '') {
      alertify.error('Select Type!');
      return;
    }

    this.service.post('production/additional_material.php?type=rejectAdditional&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success("Record update successfully");
        // this.getPendingPO();
        this.remark = '';
       
      } else {
        alertify.error('Failed to Update, Please try again!');
      }

      //  this.AllRecord();
    });
  }
}
