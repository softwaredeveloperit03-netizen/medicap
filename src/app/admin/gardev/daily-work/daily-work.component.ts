import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-daily-work',
  templateUrl: './daily-work.component.html',
  styleUrls: ['./daily-work.component.css'],
})
export class DailyWorkComponent implements OnInit {
  isNew = false;
  entries;
  labour_name = '';
  selectedEntry = [];
  isApprover;
  isChecker;
  isView = false;
  steps: string[] = [];
  Labour;

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept: string;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getDistructionDetails();
    this.getLabourDetails();
    this.get_rights();
  }

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

  addSteps() {
    if (!this.labour_name || String(this.labour_name).trim() === '') {
      return;
    }
    this.steps.push(this.labour_name);
    this.labour_name = '';
  }

  deleteSteps(index: number) {
    this.steps.splice(index, 1);
  }

  viewEntry(index: number) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  saveForm(data) {
    const formData = new FormData();
    formData.append('activity', data.value.activity);
    formData.append('approx_time', data.value.approx_time);
    formData.append('steps', this.steps.join(','));
    formData.append('entry_date', data.value.entry_date);

    const empId = localStorage.getItem('emp_id');
    if (empId) {
      formData.append('emp_id', empId);
    }

    this.service.post('admin.php?type=saveDailyWork', formData).subscribe(
      (response) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.steps = [];
          data.resetForm();
          this.getDistructionDetails();
          this.isNew = false;
          alert('Saved Successfully');
        } else {
          alert(result.message || 'An error has occurred, please try again');
        }
      },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      }
    );
  }

  getLabourDetails() {
    this.service.get('admin.php?type=getLabourDetails').subscribe((response) => {
      this.Labour = response;
    });
  }

  getDistructionDetails() {
    this.service.get('admin.php?type=getDailyWorkDetails').subscribe((response) => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/admin/gardev']);
  }
}