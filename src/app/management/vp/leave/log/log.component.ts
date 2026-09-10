import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  selectedResult= [];
  isView = false;
  results;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
    this.getApprovedLeave();
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

  get_rights() {
     this.service.get('hr/employee.php?type=getrights&emp_id=' 
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



  getApprovedLeave() {
    this.service.get('hr/leaveForm.php?type=leave_statusForDept&departmentName='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  pending
    view(index) {
      this.selectedResult = this.results[index];
      this.pending = JSON.parse(this.selectedResult['pendingList']);
      this.isView = true;
    }

    download(){
      this.service.open('hr/leaveForm.php?type=downloadLeaveLog');
    }

    ismodified=false;
    modified;
    modi_leave_from ;
    modi_leave_to ;
    modi_no_day;
    errorCorrection(index) {
     this.modified = this.results[index];
     console.log(this.modified);
     this.modi_no_day =  this.modified["modi_no_day"];
     this.modi_leave_from = this.modified["modi_leave_from"];
     this.modi_leave_to =  this.modified["modi_leave_to"];
     console.log(this.modified ,this.modi_leave_to , this.modi_no_day);
      this.ismodified = true;
    }
}
