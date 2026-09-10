import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare var swal: any;

@Component({
  selector: 'app-distruction-form',
  templateUrl: './distruction-form.component.html',
  styleUrls: ['./distruction-form.component.css'],
})
export class DistructionFormComponent implements OnInit {
  isNew = false;
  entries;
  step_name = '';
  selectedEntry;
  employee;
  isApprover;
  isView = false;
  steps = [];
  remark = '';
  // tslint:disable-next-line: variable-name
  department_name = '';
  // tslint:disable-next-line: variable-name
  request_by_employee = '';
  departments;
  product_name = '';
  dosage_form = '';
  dosages;
  products;
  batch;
  batch_no = '';
  result;

  constructor(private service: DataAccessService, private router: Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getDistructionDetails();
    this.getDepartments();
    this.getDosage();
    this.get_rights();
    this.getProduct();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
    } else {
      this.isApprover = false;
    }
  }

  addSteps() {
    this.steps[this.steps.length] = this.step_name;
    this.step_name = '';
  }

  deleteSteps(index) {
    this.steps.splice(index, 1);
  }

  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  updateDistruction(action) {
    this.service
      .get(
        'audit-trails.php?type=updateDistruction&id=' +
          this.selectedEntry.id +
          '&action=' +
          action +
          '&remark=' +
          this.remark
      )
      .subscribe((response) => {
        alert('Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getDistructionDetails();
      });
  }

  saveForm(data) {
    const formData = new FormData();
    formData.append('dosage_form', data.value.dosage_form);
    formData.append('product_name', data.value.product_name);
    formData.append('batch_no', data.value.batch_no);
    formData.append('department_name', data.value.department_name);
    formData.append('request_by_employee', data.value.request_by_employee);
    formData.append('reason_for_request', data.value.reason_for_request);
    formData.append('equipment', data.value.equipment);
    formData.append('distruction_loc', data.value.distruction_loc);
    formData.append('steps', this.steps.toString());

    this.service
      .post('audit-trails.php?type=saveDistruction', formData)
      .subscribe(
        (response) => {
          const result = JSON.parse(JSON.stringify(response));
          if (result.status === 'success') {
            this.steps = [];
            data.resetForm();
            this.getDistructionDetails();
            this.isNew = false;

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

  destroyEntry(record: any) {
    const formData = new FormData();
    formData.append('id', record);
    this.service
      .post('audit-trails.php?type=destroyEntry', formData)
      .subscribe((response: any) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          alert('Destroyed Successfully');
          this.getDistructionDetails();
        }
      });
  }

  getDosage() {
    this.service
      .get('audit-trails.php?type=getDosage')
      .subscribe((response) => {
        this.dosages = response;
      });
  }

  getProduct() {
    this.products = [];
    this.service
      .get(
        'audit-trails.php?type=getDosagebyId&selecteddosage_form=' +
          this.dosage_form
      )
      .subscribe((response) => {
        this.products = response;
      });
  }

  getBatch() {
    this.batch = [];
    this.service
      .get(
        'audit-trails.php?type=getBatchbyId&selectedproduct_name=' +
          this.product_name
      )
      .subscribe((response) => {
        this.batch = response;
      });
  }

  getDepartments() {
    this.service
      .get('hrDepartment.php?type=getDepartments')
      .subscribe((response) => {
        this.departments = response;
      });
  }

  getEmployee() {
    this.employee = undefined;
    this.service
      .get(
        'employee.php?type=getEmployeesbyDpt&selecteddepartment=' +
          this.department_name
      )
      .subscribe((response) => {
        this.employee = response;
      });
  }

  getDistructionDetails() {
    this.service
      .get('audit-trails.php?type=getDistructionDetails')
      .subscribe((response) => {
        this.entries = response;
      });
  }

  close() {
    this.router.navigate(['/qa/stereo']);
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
