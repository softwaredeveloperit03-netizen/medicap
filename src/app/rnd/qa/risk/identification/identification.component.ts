import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-identification',
  templateUrl: './identification.component.html',
  styleUrls: ['./identification.component.css']
})
export class IdentificationComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];

  isNew = false;
  departments;
  sections = [];

  isProduct = false;
  isEquipment = false;

  products;
  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department'); }

  ngOnInit(): void {
    this.getIdentification();
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

  getIdentification() {
    this.service.get('rnd/qa/risk.php?type=getIdentification').subscribe(response => {
      this.results = response;
    });
  }

  newRisk() {
    this.getDepartments();
    this.isNew = true;
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getSections(index) {
    index = index - 1;
    this.sections = this.departments[index].sections;
  }

  checkIdentification(value) {
    if (value == 'Product') {
      this.isProduct = true;
      this.isEquipment = false;
      this.getProducts();
    } else if (value == 'Equipment') {
      this.isProduct = false;
      this.isEquipment = true;
    } else {
      this.isProduct = false;
      this.isEquipment = false;
    }
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  saveIdentification(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('rnd/qa/risk.php?type=saveIdentification', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isNew = false;
        this.getIdentification();
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];
    this.isView = true;
  }

}
