import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  isNew = false;
  isView = false;
  constructor(public service: DataAccessService, private router: Router) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
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

  new() {
    this.getMaterialsLog();
    this.isNew = true;
    this.getUnits();
  }
  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  results;
  getMaterialsLog() {
    this.service
      .get('master/material.php?type=getMaterials&material_type=Raw Material')
      .subscribe((response) => {
        this.results = response;
      });
  }
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  selectedResult = [];
  grade;
  material_code;
  get_data(index) {
    this.selectedResult = this.results[index - 1];
    this.grade = this.selectedResult['gradeName'];
    this.material_code = this.selectedResult['material_code'];
    console.log(this.grade);
    this.get_test_data();
  }
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  units;
  getUnits() {
    this.service.observableUnit.subscribe((response) => {
      this.units = response;
    });
  }
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  tests;
  get_test_data() {
    this.service
      .get(
        'qc/standard/standard.php?type=get_test_data&material_code=' +
          this.material_code
      )
      .subscribe((response) => {
        this.tests = response;
      });
  }
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  productList = [];
  addProduct(data) {
    const selectedItems = this.tests.filter((term) => term.selected);

    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }

    this.productList.push(
      ...selectedItems.map((item) => ({
        material_name: item.material_name,
        material_code: item.material_code,
        ar_no: item.ar_no,
        grn_no: item.GRN_NO,
        specification_no: item.specification_no,
        assay_result: item.assay_result,
        limit_type: item.limit_type,
        vendor_name: item.vendor_name,
        vendor_code: item.vendor_code,
        testing_no: item.testing_no,
      }))
    );

    // Reset selected property for each selected item
    for (const item of selectedItems) {
      item.selected = false;
    }

    data.resetForm();
    console.log(this.productList);
  }
}


