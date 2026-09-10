import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-attendance-report',
  templateUrl: './attendance-report.component.html',
  styleUrls: ['./attendance-report.component.css']
})
export class AttendanceReportComponent implements OnInit {
  employees;
  candidates;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployees();
    this.getAttendence();
  }

  getAttendence() {
    this.service.get('hr/attendance.php?type=getAttendence').subscribe(response => {
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

  reportemployee(data) {
    alert('Download option not available in this version');
    /* const temp = data.value;
    window.location.href= this.service.url + 'reports/attendence.php?emp_id=' + temp["emp_id"] + '&fromdate=' + temp["fromdate"] + '&todate=' + temp["todate"]; */
  }

}
