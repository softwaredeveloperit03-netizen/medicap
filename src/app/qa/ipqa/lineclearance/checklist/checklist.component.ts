import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checklist',
  templateUrl: './checklist.component.html',
  styleUrls: ['./checklist.component.css'],
})
export class ChecklistComponent implements OnInit {
  isView = false;
  isNew = false;
  results;
  selectedResult = [];

  departments;
  sections;

  checkpoints = [];
  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getAreaChecklists();
    this.get_rights();
  }

  getAreaChecklists() {
    this.service
      .get('qa/clearance.php?type=getAreaChecklists')
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  new() {
    this.getDepartments();
    this.isNew = true;
  }

  getDepartments() {
    this.service
      .get('qa/clearance.php?type=getPendingDepartments')
      .subscribe((response) => {
        this.departments = response;
      });
  }

  getSections(index) {
    index = index - 1;
    if (index != -1) {
      this.sections = this.departments[index].sections;
    }
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.checkpoints[this.checkpoints.length] = temp;
    data.resetForm();
  }

  del(index) {
    this.checkpoints.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.checkpoints.length == 0) {
      alert('Checkpoints are required');
      return;
    }
    let temp = data.value;
    temp['checklist'] = this.checkpoints;
    this.service
      .post('qa/clearance.php?type=saveCheckpoints', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Checklist Saved Successfully');
          this.isNew = false;
          this.getAreaChecklists();
        } else {
          alert('Failed: An error occured, please try again!');
        }
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
  //---------------------------------------------------------------------------------//
}
