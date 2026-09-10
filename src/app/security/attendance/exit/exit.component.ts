import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-exit',
  templateUrl: './exit.component.html',
  styleUrls: ['./exit.component.css']
})
export class ExitComponent implements OnInit {

  isMobile = false;
  attendences: any[] = [];
  loading = false;

  constructor(private service: DataAccessService, private router: Router) {
    this.isMobile = this.service.isMobile;
  }

  ngOnInit() {
    this.getAttendence();
  }

  getAttendence() {
    this.loading = true;
    this.service.get('security/attendance.php?type=getPendingAttendance').subscribe({
      next: (response: any) => {
        this.attendences = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.attendences = [];
        this.loading = false;
        alertify.error('Failed to load pending attendance');
      }
    });
  }

  exitemployee(id: any, employee_id?: any) {
    if (!id) {
      alertify.error('Invalid attendance record');
      return;
    }
    const empId = employee_id || '';
    this.service
      .get(
        'security/attendance.php?type=addEmployeeOuttime&id=' +
          encodeURIComponent(id) +
          '&employee_id=' +
          encodeURIComponent(empId)
      )
      .subscribe({
        next: (response: any) => {
          if (response && response.status === 'success') {
            alertify.success('Employee Out time saved successfully!');
            this.getAttendence();
            this.router.navigate(['/security/attendence']);
          } else {
            alertify.error((response && response.message) || 'Failed: An error occurred, please try again!');
          }
        },
        error: () => {
          alertify.error('Failed: Unable to save employee out time');
        }
      });
  }

}
