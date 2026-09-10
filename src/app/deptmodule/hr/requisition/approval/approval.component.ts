import { ChangeDetectorRef, Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
declare var html2canvas: any;
declare var jspdf: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router,private cbr:ChangeDetectorRef) { }
  ngOnInit() {
    this.getRequisitionPendingForApproval()
  }

 
 
  isView = false;
  selectedResult = [];
  view(data) {
    this.selectedResult = data;
    this.isView = true;
  }
  

  requisitions;
 
  getRequisitionPendingForApproval() {
      this.service.get('hr/manpower.php?type=getRequisitionPendingForApproval').subscribe((response: any) => {
        this.requisitions = response;
      });
  }



    approveManPower(status) {
 
    let temp = {};
    temp['status'] = status;
    temp['id'] = this.selectedResult['id'];

    this.service.post('hr/manpower.php?type=approveManPower', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        this.selectedResult = [];
        alertify.success('Manpower Requisition '+status+' Successfully');
        this.isView = false;
        this.getRequisitionPendingForApproval()

      } else {
        alertify.error(response['status']);
      }
      });
  }
  
 
  
 
 
}