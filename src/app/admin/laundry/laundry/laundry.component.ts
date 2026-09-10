import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-laundry',
  templateUrl: './laundry.component.html',
  styleUrls: ['./laundry.component.css'],
})
export class LaundryComponent implements OnInit {
  selectedcontractor: any;
  selectedFile: any;
  isNew = false;
  list;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getStationarylist();
    this.get_rights();
  }

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
  getStationarylist() {
    this.service.get('admin.php?type=getGlownslist').subscribe((response) => {
      this.list = response;
    });
  }

  saveForm(data) {
    // let temp=data.value;
    var obj = {
      stationary: data.value.stationary,
      no_required: data.value.no_required,
      company: data.value.company,
      company1: data.value.company1,
      details: data.value.details,
    };
    this.service
      .post('admin.php?type=saveglowns', JSON.stringify(obj))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          data.resetForm();
          this.getStationarylist();
          this.isNew = false;
          alert('Successfully Save');
        } else {
          alert('please try again');
        }
      });
  }
}
