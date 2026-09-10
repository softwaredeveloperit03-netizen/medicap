import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isView = false;
  requests;
  remark = '';  
checkpoints;
  selectedClearance = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingLineClearance();
    this.GET_warehouse_dis();
  }

  getPendingLineClearance() {
    this.service.get('qa/clearance.php?type=getPendingLineClearance').subscribe(response => {
      this.requests = response;
    });
  }

  checkPointData;
  GET_warehouse_dis(){
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Dispensing&form=Line Clearance').subscribe(response => {
      this.checkPointData = Array.isArray(response) ? response : [];
    });
  }
 


  updateLineClearance(value,status) {
    this.service.post('qa/clearance.php?type=updateLineClearance&action=' 
      + value + '&id=' + this.selectedClearance['id'] 
      + '&work_order_id='+this.selectedClearance['work_order_id'] 
      + '&material_type=' + 'Raw Material' 
      + '&status=' + status
      + '&remark=' + this.remark,JSON.stringify(this.checkpoints)).subscribe(response => {
      if (response['status'] === 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getPendingLineClearance();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  viewClearance(index) {
    this.selectedClearance = this.requests[index];
     this.isView = true;
  }

}
