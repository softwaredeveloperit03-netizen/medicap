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

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedLeave();
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
