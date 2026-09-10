import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getShiftChangeRequests();
  }

  getShiftChangeRequests() {
    this.service.get('hr/shift.php?type=shift_chnge_Approval_log&approval_from=HR').subscribe((response: any) => {
      this.results = response;
    });
  }

}
