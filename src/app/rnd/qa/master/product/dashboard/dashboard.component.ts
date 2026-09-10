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
  isView = false;
  results;
  dosages;
  grades;
  dosage_form = '';
  grade = '';
  status = '';
  selectedResult = [];
  isMrp = false;
  selectedData = [];
  generic_name = '';
  product_code = '';
  constructor(private service: DataAccessService, private router: Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getProductsLog();
    this.getDosages();
    this.getGrades();
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

  getProductsLog() {
    this.service
      .get(
        'rnd/qa/master/product.php?type=getProductsLog&c=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&status=' +
          this.status +
          '&generic_name=' +
          this.generic_name
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe((response) => {
      this.dosages = response;
    });
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe((response) => {
      this.grades = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  editMrp(index) {
    this.selectedData = this.results[index];
    this.isMrp = true;
  }

  edit(index) {
    this.router.navigate(['/product/edit/' + this.results[index].id]);
  }

  open(file) {
    if (file !== '') {
      window.open(this.service.url + 'upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }

  saveMrp(data) {
    if (!data.valid) {
      alertify.error('All feilds Are required');
    }
    let temp = data.value;
    temp['product_code'] = this.selectedData['product_code'];
    this.service
      .post('rnd/qa/master/product.php?type=updateMRP', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Product Mrp send to approval');
          this.isMrp = false;
        } else {
          alertify.error('not save');
        }
      });
  }
}
