import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan',
  templateUrl: './plan.component.html',
  styleUrls: ['./plan.component.css'],
})
export class PlanComponent implements OnInit {
  isNew = false;
  departments;
  entries;
  remark = '';
  isView = false;
  selectedPlan;
  isApprover;

  constructor(private service: DataAccessService, private router: Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getDepartments();
    this.getInspectionPlan();
    this.get_rights();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
    } else {
      this.isApprover = false;
    }
  }

  getDepartments() {
    this.service
      .get('hrDepartment.php?type=getDepartments')
      .subscribe((response) => {
        this.departments = response;
      });
  }

  updateInspectionPlan(action) {
    this.service
      .get(
        'audit-trails.php?type=updateInspectionPlan&id=' +
          this.selectedPlan.id +
          '&action=' +
          action +
          '&remark=' +
          this.remark
      )
      .subscribe((response) => {
        alert('Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getPendingPlan();
      });
  }

  viewPlan(index) {
    this.selectedPlan = this.entries[index];
    this.isView = true;
  }

  getPendingPlan() {
    this.service
      .get('audit-trails.php?type=getPendingPlan')
      .subscribe((response) => {
        this.entries = response;
      });
  }

  saveForm(inspectionplan) {
    this.service
      .post(
        'audit-trails.php?type=saveInspectionPlan',
        JSON.stringify(inspectionplan.value)
      )
      .subscribe(
        (response) => {
          if (response['status'] === 'success') {
            inspectionplan.resetForm();
            alert('Successfully send for Approval');
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

  getInspectionPlan() {
    this.service
      .get('audit-trails.php?type=getInspectionPlan')
      .subscribe((response) => {
        this.entries = response;
      });
  }

  close() {
    this.router.navigate(['/qa/audit']);
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
