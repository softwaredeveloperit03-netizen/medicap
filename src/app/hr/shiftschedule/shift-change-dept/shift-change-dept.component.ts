import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-shift-change-dept',
  templateUrl: './shift-change-dept.component.html',
  styleUrls: ['./shift-change-dept.component.css']
})
export class ShiftChangeDeptComponent implements OnInit {
  shifts;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getShiftChangeRequests();
  }

  getShiftChangeRequests() {
    this.service.get('hrDepartment.php?type=getShiftChangeRequests')
    .subscribe(response => {
      this.shifts = response;
    });
  }

  shiftchangeAction(value) {
    this.service.get('hrDepartment.php?type=shiftchangeAction&action='+value)
    .subscribe(response => {
      this.getShiftChangeRequests();
    });
  }

}
