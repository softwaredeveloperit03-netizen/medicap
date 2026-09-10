import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  designations;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDesignations();
  }
  getDesignations() {
    this.service.get('hr/designation.php?type=getPendingDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  updateDesignation(status){
    this.service.get('hr/designation.php?type=updateDesignation&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Designation Updated Successfully');
        this.isView = false;
        this.getDesignations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  
  view(index){
    this.selectedResult=this.designations[index];
    this.isView=true;
  }

}
