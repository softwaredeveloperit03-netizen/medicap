import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isUser = false;
  isChecker = false;
  isApprover = false;
  isEdit = false;
  isView = false;
  isPassword = false;
  title = 'Log';
  requisitions;
  department = '';
  department_name;
  designations;
  departments1 = [];
  salary_types;
  type = '';
  designation = '';
  description = '';
  candiates = '';
  qualification = '';
  candidates = 0;
  status = '';
  is_log = false;
  date = '';
  selectedResult = [];
  departments;
  option_selected = 'all';
  is_review = false;
  qualifications;
  for_approval = false;
  isNew: any;
  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }
  ngOnInit() {
    this.getrequisitions(this.option_selected);
    this.getApprovedQualifications();
    this.getDepartments();
    this.getSalaryTypes();
    this.get_rights();
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
  getrequisitions(stat) {
    if (stat == 'all') {
      this.title = 'Log';
      this.is_log = true;
      this.is_review = false;
      this.for_approval = false;
    } else if (stat == 'revision') {
      this.title = 'For Review';
      this.is_review = true;
      this.is_log = false;
      this.for_approval = false;
    } else {
      this.title = 'For Approval';
      this.is_log = false;
      this.is_review = false;
      this.for_approval = true;
    }
    this.option_selected = stat;
    this.requisitions = [];
    this.service
      .get('hr/manpower.php?type=getRequisitionLog&status=' + stat)
      .subscribe((response: any) => {
        this.requisitions = response;
      });
  }

  view(index) {
    this.selectedResult = this.requisitions[index];
    this.isView = true;
  }
  getDepartments() {
    this.service
      .get('hr/employee.php?type=get_department_by_designation')
      .subscribe((response) => {
        this.departments = response;
      });
  }
  getDesignation(department) {
    for (let i = 0; i < this.departments.length; i++) {
      if (this.departments[i]['department_name'] == department) {
        this.designations = this.departments[i]['designations'];
      }
    }
  }
  updateStatus(status, id) {
    this.service
      .get(
        'hr/manpower.php?type=accept_reject_requisition&status=' +
          status +
          '&id=' +
          id
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getrequisitions(this.option_selected);
          alertify.success('Staus updated');
          this.router.navigate(['/hr/recruitment/requisition']);
        } else {
          alertify.error(response['status']);
        }
      });
  }
  download() {
    this.service.open(
      'hr/manpower.php?type=getRequisitionLog' +
        this.department +
        '&designation=' +
        this.designation +
        '&status=' +
        this.status
    );
  }
  getApprovedQualifications() {
    this.service
      .get('common.php?type=getQualifications')
      .subscribe((response) => {
        this.qualifications = response;
      });
  }
  edit(index) {
    this.selectedResult = this.requisitions[index];
    this.isEdit = true;
    this.description = this.selectedResult['description'];
    this.department = this.selectedResult['department'];

    for (let i = 0; i < this.departments.length; i++) {
      if (this.departments[i]['department_name'] == this.department) {
        this.designations = this.departments[i]['designations'];
        this.designation = this.selectedResult['designation'];
      }
    }

    this.type = this.selectedResult['type'];
    this.qualification = this.selectedResult['qualification'];
    this.candidates = this.selectedResult['candidates'];
  }
  getSalaryTypes() {
    this.service
      .get('hr/employee.php?type=getSalaryTypes')
      .subscribe((response) => {
        this.salary_types = response;
      });
  }
  number(value) {
    if (isNaN(value)) {
      alertify.error('Number 10 digit Only');
      return false;
    }
  }
  updateData(data) {
    if (!data.valid) {
      alertify.error('All fields are mandatory');
      return;
    }
    let temp = data.value;
    this.service
      .post(
        'hr/manpower.php?type=update_requisition&id=' +
          '&id=' +
          this.selectedResult['id'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getrequisitions(this.option_selected);
          alertify.success('Staus updated');
          this.router.navigate(['/hr/recruitment/requisition']);
        } else {
          alertify.error(response['status']);
        }
      });
  }
}

