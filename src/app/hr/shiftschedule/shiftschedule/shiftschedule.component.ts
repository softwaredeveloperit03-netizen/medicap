import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-shiftschedule',
  templateUrl: './shiftschedule.component.html',
  styleUrls: ['./shiftschedule.component.css']
})
export class ShiftscheduleComponent implements OnInit {
  departments;
  shifts;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getShifts();
  }

  getShifts() {
    this.service.get('hrDepartment.php?type=getShifts')
    .subscribe(response => {
      this.shifts = response;
    });
  }

}
