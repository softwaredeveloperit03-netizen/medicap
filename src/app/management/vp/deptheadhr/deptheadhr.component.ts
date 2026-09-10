import { Component, OnInit } from '@angular/core';
 import { DataAccessService } from 'src/app/data-access.service';
 
declare let alertify;

@Component({
  selector: 'app-deptheadhr',
  templateUrl: './deptheadhr.component.html',
  styleUrls: ['./deptheadhr.component.css']
})
export class DeptheadhrComponent implements OnInit {
Department:any
  constructor(private service: DataAccessService) {
   }

  ngOnInit(): void {
    this.getapprisals_log();
    this.getApprovedLeave();
    this.getData();
    this.Department=localStorage.getItem('department')
  }





 

  unreadAppraisal =0;
  unreadleave =0;
  unreadresignation =0;
  unreadVendor =0;


  
  getapprisals_log() {
    this.service.get('hr/appraisalchecklist.php?type=getapprisals_log_for_deptHeadNotification&department1='+localStorage.getItem('department')).subscribe((response: any) => {
      this.unreadAppraisal = response['pending_appraisal'];
      if(this.unreadAppraisal > 0){
        alertify.warning(response['text']);
      }
  });
}
 

getApprovedLeave() {
  this.service.get('hr/leaveForm.php?type=getPendingLeaveForDeptNotification&department_name=' + localStorage.getItem('department')).subscribe(response => {
    this.unreadleave = response['pending_leaveForm'];
    if(this.unreadleave > 0){
      alertify.warning(response['text']);
    }
  });
}


getData() {
  this.service.get('hr/resignation.php?type=get_resignation_by_deparetmentNotification&deptName='+localStorage.getItem('department')).subscribe(response => {
    this.unreadresignation = response['pending_resignation'];
    if(this.unreadresignation > 0){
      alertify.warning(response['text']);
    }
  });
}


  


 










}
