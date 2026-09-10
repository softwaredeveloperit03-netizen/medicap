import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-entry',
  templateUrl: './entry.component.html',
  styleUrls: ['./entry.component.css']
})
export class EntryComponent implements OnInit {

  employees: any[] = [];
  selectedEmployee: any = {};
  selectedEmpId = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getEmployees();
  }

  getEmployees() {
    this.service.get('employee.php?type=getAllEmployees').subscribe((response: any) => {
      this.employees = Array.isArray(response) ? response : [];
    });
  }

  getEmpDetails(empId: string) {
    if (!empId) {
      this.selectedEmployee = {};
      return;
    }
    this.selectedEmployee = this.employees.find(emp => emp.emp_id === empId) || {};
  }

  saveEmpInTime(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('security/attendance.php?type=addEmployeeIntime', data.value).subscribe({
      next: (response: any) => {
        const status = response && response.status;
        // Ignore legacy no_shift — form must save without shift allocation
        if (status === 'success') {
          alertify.success('Employee In Time Added');
          data.resetForm();
          this.selectedEmpId = '';
          this.selectedEmployee = {};
          this.router.navigate(['/security/attendence']);
        } else if (status === 'filled') {
          alertify.error('Employee In Time already added for today');
        } else if (status === 'no_shift') {
          // Old server file still deployed — do not show shift message
          alertify.error('Please upload updated security/attendance.php to the server, then try again.');
        } else {
          alertify.error((response && response.message) || 'Failed: An error occurred, please try again!');
        }
      },
      error: () => {
        alertify.error('Failed: Unable to save employee in time');
      }
    });
  }

}
