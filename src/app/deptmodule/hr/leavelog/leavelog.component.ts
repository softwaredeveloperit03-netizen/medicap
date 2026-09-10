import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-leavelog',
  templateUrl: './leavelog.component.html',
  styleUrls: ['./leavelog.component.css']
})
export class LeavelogComponent implements OnInit {


  results;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.leaveLog();
    this.getLeaveData();
  }


  leaveLog() { 
    this.service.get('hr/leaveForm.php?type=getApprovedLeaveLog').subscribe(response => {
      this.results = response;
    });
  }

  last_7_days =0;
  last_month =0;
  current_day =0;



  getLeaveData() { 
    this.service.get('hr/leaveForm.php?type=getLeaveData').subscribe(response => {
      this.last_month = response['last_month'];
      this.current_day = response['current_day'];
      this.last_7_days = response['last_7_days'];

      console.log(this.last_month);
      console.log(this.current_day);
      console.log(this.last_7_days);
    });
  }

}
