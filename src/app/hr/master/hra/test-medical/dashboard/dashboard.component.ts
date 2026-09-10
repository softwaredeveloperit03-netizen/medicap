import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify: any;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isTestname = false;
  test_name = '';
  mtest_name;
  tests;
  isNewTest;
  classification = 'Raw Material';
  test_type = '';
  entries = [];
  dosage_form = '';
  test = '';
  item = [];
  constructor(
    private service: DataAccessService,
    private router: Router,
    private masterHubReturn: MasterHubReturnService
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getTests();
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

  addTestname() {
    if (this.mtest_name == 'Add New') {
      this.isTestname = true;
    } else {
      this.isTestname = false;
    }
  }
  saveTest(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    this.service
      .post('master/test.php?type=saveTest', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          // this.getProductgroup();
          this.isTestname = false;
          alertify.success('Record Inserted Successfully');
          data.resetForm();
        } else {
          alert('Please try Again');
        }
      });
  }

  getTests() {
    this.service
      .get('master/test.php?type=get_saveTest_medical')
      .subscribe((response) => {
        this.tests = response;
        this.filterItem();
      });
  }

  download() {
    this.service.open(
      'hr/test.php?type=downloadTestsLog&test_type=' + this.test_type
    );
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.tests.length; i++) {
      let material = this.tests[i];
      if (
        material['test_name']
          .toUpperCase()
          .includes(this.test_name.toUpperCase())
      ) {
        this.item[this.item.length] = material;
      }
    }
  }
  AllRecord() {
    this.item = this.tests;
    this.mtest_name = '';
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }
}

