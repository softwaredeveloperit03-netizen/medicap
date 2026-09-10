import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  selectedResult: any = {};
  isView = false;
  isNew = false;
  results: any[] = [];

  // from old dashboard component
  leave_type: any;
  isLeavetype = false;
  leave: any;
  salary_types: any[] = [];
  leaveList: any[] = [];
  designations: any[] = [];
  leave_deduct = '';
  leavetypes: any[] = [];
  forward: any;

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights: any;
  loggedInDept: any;

  constructor(
    private service: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getLeaves();
    this.get_rights();

    // data needed for new form
    this.getDesignations();
    this.getSalaryTypes();
    this.getLeaveTypes();
  }

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response: any) => {
        this.rights = response;
        if (this.rights && this.rights[0]) {
          this.isuser = this.rights[0].isuser;
          this.ischecker = this.rights[0].ischecker;
          this.isapprover = this.rights[0].isapprover;
          this.qms_approver = this.rights[0].qms_approver;
          this.dept_head = this.rights[0].dept_head;
          this.isauditor = this.rights[0].isauditor;
          this.plant_head = this.rights[0].plant_head;
          this.shift_allocator = this.rights[0].shift_allocator;
        }
      });
  }

  getLeaves() {
    this.service.get('hr/leavepolicy.php?type=getLeavePolicy').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.isNew = false;
  }

  openNewForm() {
    this.isNew = true;
    this.isView = false;
    this.resetNewFormState();
  }

  cancelNewForm() {
    this.isNew = false;
    this.resetNewFormState();
  }

  resetNewFormState() {
    this.leaveList = [];
    this.leave_deduct = '';
    this.leave_type = '';
    this.forward = '';
    this.selectedResult = {};
  }

  saveForm(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    const temp = data.value;
    this.service
      .post(
        'hr/leavepolicy.php?type=updateLeavePolicy&id=' + this.selectedResult['id'],
        JSON.stringify(temp)
      )
      .subscribe((response: any) => {
        if (response['status'] == 'success') {
          alertify.success('Record Inserted Successfully');
          data.resetForm();
          this.isView = false;
          this.getLeaves();
        } else {
          alertify.error('Failed: An error occured, Please try again!');
        }
      });
  }

  getSalaryTypes() {
    this.service.get('hr/employee.php?type=getSalaryTypes').subscribe((response: any) => {
      this.salary_types = Array.isArray(response) ? response : [];
    });
  }

  getLeaveTypes() {
    this.leavetypes = [];
    this.service.get('hr/leavepolicy.php?type=getLeaveTypes').subscribe((response: any) => {
      this.leavetypes = Array.isArray(response) ? response : [];
    });
  }

  getLeavetype() {
    this.service.get('master/leavetype.php?type=getLeavetype').subscribe((response: any) => {
      this.leave = response;
    });
  }

  getDesignations() {
    this.service.get('common.php?type=getDesignationHeading').subscribe((response: any) => {
      this.designations = Array.isArray(response) ? response : [];
    });
  }

  addLeavetype() {
    this.isLeavetype = this.leave_type === 'ADD NEW';
  }

  addData(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    const temp = data.value;
    this.leaveList.push(temp);
    data.resetForm();
  }

  delData(index: number) {
    this.leaveList.splice(index, 1);
  }

  saveLeaveType(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
    const obj = { leave_type: temp['leave_type'] };

    this.service.post('hr/leavepolicy.php?type=saveLeaveType', JSON.stringify(obj)).subscribe((response: any) => {
      if (response['status'] == 'success') {
        this.isLeavetype = false;
        this.getLeaveTypes();
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  save(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.leaveList.length == 0) {
      alertify.error('Please enter leave types');
      return;
    }

    const temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['leaveList'] = this.leaveList;

    this.service.post('hr/leavepolicy.php?type=saveLeavePolicy', JSON.stringify(temp)).subscribe((response: any) => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();

        // return to log and refresh
        this.isNew = false;
        this.getLeaves();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }
}