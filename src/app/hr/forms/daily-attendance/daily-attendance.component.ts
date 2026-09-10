import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-daily-attendance',
  templateUrl: './daily-attendance.component.html',
  styleUrls: ['./daily-attendance.component.css']
})
export class DailyAttendanceComponent implements OnInit {
  employees;
  employees1 = [];
  filterresult;
  selectedEmployee;
  isEdit = false;
  intime;
  outtime;
  employee_id;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAttendance();
  }

  getPendingAttendance() {
    this.service.get('hrDepartment.php?type=getPendingAttendance')
    .subscribe(response => {
      this.employees = response;
      this.employees1 = this.employees;
    });
  }

  filtertable() {
    this.employees1 = [];
    let index = 0;
    let temp1 = this.filterresult.toLowerCase();
    this.employees.forEach(element => {
      let temp = element['emp_id'];
      temp = temp.toLowerCase();
      if(temp.indexOf(temp1) > -1) {
        this.employees1[index] = element;
        index++; 
      }
    });
  }

  filterAttendance(data) {
    let temp = data.value;
    this.employee_id = temp['employee_id'];
    this.service.get('hrDepartment.php?type=correctionFilter&employee_id='+temp['employee_id'])
    .subscribe(response => {
      this.selectedEmployee = response;
    });
  }

  editattendance(intime, outtime, id) {
    this.intime = intime;
    this.id = id;
    if(outtime !== "00:00:00") {
      this.outtime = outtime;
    }
    this.isEdit = true;
  }

  updateAttendance(data) {
    this.isEdit = false;
    let temp = data.value;
    temp["id"] = this.id;
    temp["employee_id"] = this.employee_id;
    temp["original_intime"] = this.intime;
    temp["original_outtime"] = this.outtime;
    this.service.post('hrDepartment.php?type=updateAttendance', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Employee'+this.employee_id +' Attendance Correction sent for Approval of Deaprtment Head');
        data.reset();
        this.selectedEmployee = [];
        this.getPendingAttendance();
      } else {
        alert(response['status']);
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
