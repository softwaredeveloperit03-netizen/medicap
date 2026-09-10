import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  tests;
  isNewTest;
  classification = 'Raw Material';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;
  test_type = '';
  entries = [];
  dosage_form = '';
  test = '';
  status = '';
  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

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
  checkClassification(value) {
    if (value === 'Raw Material') {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Packing Material') {
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Finish Product') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = true;
      this.isInprocess = false;
    } else if (value === 'Inprocess') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = true;
    }
  }

  getTests() {
    this.service
      .get(
        'master/test.php?type=getTestsLog&classification=' +
          this.classification +
          '&dosage_form=' +
          this.dosage_form +
          '&test_type=' +
          this.test_type
      )
      .subscribe((response) => {
        this.tests = response;
      });
  }

  download() {
    this.service.open(
      'master/test.php?type=downloadTestsLog&classification=' +
        this.classification +
        '&dosage_form=' +
        this.dosage_form +
        '&test_type=' +
        this.test_type
    );
  }
}
