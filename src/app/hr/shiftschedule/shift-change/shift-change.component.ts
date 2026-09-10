import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-shift-change',
  templateUrl: './shift-change.component.html',
  styleUrls: ['./shift-change.component.css']
})
export class ShiftChangeComponent implements OnInit {
  current_shift;
  last_attendance;
  isShow = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCurrentShift();
    this.getTodaysAttendance();
  }

  getCurrentShift() {
    this.service.get('hrDepartment.php?type=getCurrentShift')
    .subscribe(response => {
      this.current_shift = response['shift'];
    });
  }

  getTodaysAttendance() {
    this.service.get('hrDepartment.php?type=getTodaysAttendance')
    .subscribe(response => {
      this.last_attendance = response;
      this.isShow = true;
    });
  }

  sendChangeRequest(data) {
    let temp = data.value;
    temp['type'] = "prior_shift_change";
    this.service.post('hrDepartment.php?type=shiftChangeRequest', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] == 'success') {
        alert('Shift Change Request sent to Reporting Person');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  updateTodaysAttendance(data) {
    let temp = data.value;
    temp['type'] = "updateTodaysAttendance";
    this.service.post('hrDepartment.php?type=updateTodaysAttendance', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] == 'success') {
        alert('Shift Change Request sent to Reporting Person');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
