import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 import { HttpClient } from '@angular/common/http';
declare let alertify;

@Component({
  selector: 'app-shifts',
  templateUrl: './shifts.component.html',
  styleUrls: ['./shifts.component.css']
})
export class ShiftsComponent implements OnInit {
  startDate: string;
  endDate: string;
  startDate1: string;
  endDate1: string;
  calendar: { date: string, shiftName: string, startTime: string, endTime: string }[] = [];
  calendar1: { date: string, shiftName: string, startTime: string, endTime: string }[] = [];
  cur_shift_data;
  cur_shift_data1;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getcurrentShift();
    this.getcurrentShift_emp_change();
  }
 


  getcurrentShift() {
    this.service.get('hr/shift.php?type=getcurrentShift_emp').subscribe((response: any) => {
      this.cur_shift_data = response;
      this.startDate = this.cur_shift_data[0]['Start_date'];
      this.endDate = this.cur_shift_data[0]['End_date'];
      const shiftName = this.cur_shift_data[0]['shift_name'];
      const startTime = this.cur_shift_data[0]['start_time'];
      const endTime = this.cur_shift_data[0]['end_time'];

      const start = new Date(this.startDate);
      const end = new Date(this.endDate);
      const current = new Date(start);

      while (current <= end) {
        this.calendar.push({
          date: current.toDateString(),
          shiftName: shiftName,
          startTime: startTime,
          endTime: endTime
        });
        current.setDate(current.getDate() + 1);
      }
      console.log(this.calendar);
    });
   
}
getcurrentShift_emp_change() {
    this.service.get('hr/shift.php?type=getcurrentShift_emp_change').subscribe((response: any) => {
      this.cur_shift_data1 = response;
      this.startDate1 = this.cur_shift_data[0]['Start_date'];
      this.endDate1 = this.cur_shift_data[0]['End_date'];
      const shiftName = this.cur_shift_data[0]['shift_name'];
      const startTime = this.cur_shift_data[0]['start_time'];
      const endTime = this.cur_shift_data[0]['end_time'];

      const start = new Date(this.startDate);
      const end = new Date(this.endDate);
      const current = new Date(start);

      while (current <= end) {
        this.calendar1.push({
          date: current.toDateString(),
          shiftName: shiftName,
          startTime: startTime,
          endTime: endTime
        });
        current.setDate(current.getDate() + 1);
      }
      console.log(this.calendar1);
    });
   
}

}
