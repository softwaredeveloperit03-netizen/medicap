import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-coffapp',
  templateUrl: './coffapp.component.html',
  styleUrls: ['./coffapp.component.css']
})
export class CoffappComponent implements OnInit {

  department_name='';
  selectedResult= [];
  emp_id='';
  isView = false;
  results;
  remark;
    isDIGI=false
    status: any;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getApprovedLeave();
  }
  getApprovedLeave() { 
    this.service.get('hr/leaveForm.php?type=getPendingLeaveForDeptcoff&approver='+localStorage.getItem('loger_id') + '&department_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
  pending
    view(index) {
      this.selectedResult = this.results[index];
      this.pending = JSON.parse(this.selectedResult['pendingList']);
      this.isView = true;
    }

    isCorrection = false;

    errorCorrection() {
      this.isCorrection = true;

    }
    
    modi_leave_from;
    modi_leave_to;
    modi_no_day;


    update(status) {
     this.service.post('hr/leaveForm.php?type=updateLeaveFormHODCoff&status='+status +'&id='+this.selectedResult['id'] 
    + '&modi_leave_from=' + this.modi_leave_from+ '&modi_leave_to=' + this.modi_leave_to+ 
    '&modi_no_day=' + this.modi_no_day+'&remark=' + this.remark, JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        this.remark = '';
        alertify.success('Record updated successfully');
        this.isCorrection = false;
        this.isView=false;
        this.getApprovedLeave();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.loginPassward ='';
        this.update(this.status)
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
  

}
