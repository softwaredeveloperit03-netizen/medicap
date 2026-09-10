import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  loading;
  dosages;
  products;
  results;
  isNew = false;
  dosage_form = '';
  product_code = '';
  from_date = '';
  to_date = '';
  plant_id;

  selectedResult = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getBatchNosLog();
    this.getDosages();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.get_rights();
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe((response) => {
      this.dosages = response;
    });
  }

  getProductsByDosage() {
    this.service
      .get(
        'common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form
      )
      .subscribe((response) => {
        this.products = response;
      });
  }

  getBatchNosLog() {
    // this.service.get('production/plan.php?type=getBatchNosLog&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
    //   this.results = response;
    // });
    this.service
      .get('production/workorder.php?type=get_qa_approved_work_orders')
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  downloadLog() {
    this.service.open('production/plan.php?type=downloadPlansLog');
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
