import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-employee-attendance',
  templateUrl: './employee-attendance.component.html',
  styleUrls: ['./employee-attendance.component.css']
})
export class EmployeeAttendanceComponent implements OnInit {
  attendances;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
  }

  getEmployeeAttendance() {
    this.service.get('hrDepartment.php?type=getEmployeeAttendance')
    .subscribe(response => {
      this.attendances = response;
    });
  }

}
