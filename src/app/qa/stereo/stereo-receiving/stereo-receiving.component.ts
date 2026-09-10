import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;

@Component({
  selector: 'app-stereo-receiving',
  templateUrl: './stereo-receiving.component.html',
  styleUrls: ['./stereo-receiving.component.css'],
})
export class StereoReceivingComponent implements OnInit {
  isNew = false;
  entries;

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getStereoIssue();
    this.get_rights();
  }

  saveForm(data) {
    const formData = new FormData();
    formData.append('issue_from', data.value.issue_from);
    formData.append('issue_by', data.value.issue_by);
    formData.append('stereo_no', data.value.stereo_no);
    formData.append('issue_date', data.value.issue_date);

    this.service.post('vendor.php?type=saveStereoIssue', formData).subscribe(
      (response) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.isNew = false;
          this.getStereoIssue();
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
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

  getStereoIssue() {
    this.service.get('vendor.php?type=getStereoIssue').subscribe((response) => {
      this.entries = response;
    });
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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
}
