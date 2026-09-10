import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  emp_id = '';
  emp_name = '';
  department = '';
  designation = '';
  duty_details = '';
  from_date = '';
  to_date = '';
  total_days = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.emp_id = localStorage.getItem('emp_id') || '';
    this.emp_name = localStorage.getItem('username') || '';
    this.department = localStorage.getItem('department') || '';
    this.loadEmployeeProfile();
  }

  loadEmployeeProfile() {
    if (!this.emp_id) {
      return;
    }
    this.service.get('hr/employee.php?type=get_EMP_by_ID&emp_id=' + encodeURIComponent(this.emp_id)).subscribe((response: any) => {
      const emp = Array.isArray(response) ? response[0] : response;
      if (!emp) {
        return;
      }
      if (emp.firstname || emp.lastname) {
        this.emp_name = [emp.firstname, emp.middlename, emp.lastname].filter(Boolean).join(' ').trim() || this.emp_name;
      }
      if (emp.department) {
        this.department = emp.department;
      }
      if (emp.designation) {
        this.designation = emp.designation;
      }
    });
  }

  onDatesChange() {
    if (this.from_date && this.to_date) {
      const from = new Date(this.from_date);
      const to = new Date(this.to_date);
      if (!isNaN(from.getTime()) && !isNaN(to.getTime()) && to >= from) {
        const diff = Math.floor((to.getTime() - from.getTime()) / (1000 * 60 * 60 * 24)) + 1;
        this.total_days = String(diff);
      }
    }
  }

  saveduty(form: any) {
    if (!form.valid) {
      alertify.error('All required fields must be filled');
      return;
    }
    const payload = {
      emp_id: this.emp_id,
      emp_name: this.emp_name,
      department: this.department,
      designation: this.designation,
      duty_details: this.duty_details,
      from_date: this.from_date,
      to_date: this.to_date,
      total_days: this.total_days
    };
    this.service.post('admin/housekeeping.php?type=saveOutdoor_duty', JSON.stringify(payload)).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Outdoor duty saved successfully');
        form.resetForm();
        this.duty_details = '';
        this.from_date = '';
        this.to_date = '';
        this.total_days = '';
        this.router.navigate(['/employee-dashboard/duty']);
      } else {
        alertify.error(res?.message || 'Failed to save');
      }
    }, () => {
      alertify.error('Server error while saving');
    });
  }
}
