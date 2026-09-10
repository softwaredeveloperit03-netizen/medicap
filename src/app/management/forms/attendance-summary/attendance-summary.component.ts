import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-attendance-summary',
  templateUrl: './attendance-summary.component.html',
  styleUrls: ['./attendance-summary.component.css']
})
export class AttendanceSummaryComponent implements OnInit {
  employees;
  candidates;
  emp_id;
  fromdate;
  todate;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployees();
    this.getAttendence();
  }
  clear(){
    this.emp_id = '';
    this.fromdate= '';
    this.todate = '';
  }

  getAttendence() {
    this.service.get('hrDepartment.php?type=getAttendence')
    .subscribe(response => {
      this.employees = response;
    });
  }

  getSelectedReport() {
    this.service.get('security.php?type=getSelectedReport')
    .subscribe(response => {
      this.employees = response;
    });
  }

  getEmployees() {
    this.service.get('security.php?type=getCandidates')
    .subscribe(response => {
      this.candidates = response;
    });
  }

  getEmployeeDetails(data) {
    this.service.post('security.php?type=getEmployeeDetails', JSON.stringify(data.value))
    .subscribe(response => {
      this.employees = response;
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
