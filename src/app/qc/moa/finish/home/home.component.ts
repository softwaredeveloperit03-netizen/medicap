import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
})
export class HomeComponent implements OnInit {
  isView = false;
  results;

  selectedMOA = [];
  productlist = [];
  resultslength = 0;
  product_code = '';
  fromdate = '';
  todate = '';
  selectedMethod = [];
  isShow = false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.fromdate = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');  this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getFinishMOA();
    this.getMaterialList();
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
          localStorage.getItem('department')
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

  getMaterialList() {
    this.service
      .get('qc/method.php?type=getFinishMOAMaterial')
      .subscribe((response: any) => {
        this.productlist = response;
      });
  }

  getFinishMOA() {
    this.service
      .get(
        'qc/method.php?type=getFinishMOALog&product_code=' +
          this.product_code +
          '&fromdate=' +
          this.fromdate +
          '&todate=' +
          this.todate
      )
      .subscribe((response) => {
        this.results = response;
        this.resultslength = this.results.length;
      });
  }

  view(index) {
    this.selectedMOA = this.results[index];
    this.isView = true;
  }

  clearrecords() {
    this.product_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.getFinishMOA();
  }

  show(index) {
    this.selectedMethod = this.selectedMOA['tests'];
    // this.selectedMethod = test[index];
    console.log('test', this.selectedMethod);
    this.isShow = true;
  }

  download(sign, value) {
    if (sign == 'manual') {
      this.service.open('pdf1/moa.php?type=FinishMOA&id=' + value);
    } else {
      this.service.open('pdf1/moa.php?type=FinishMOAdigital&id=' + value);
    }
  }

  downloadReport() {
    this.service.open(
      'pdf1/moa.php?type=FinishMOALog&product_code=' +
        this.product_code +
        '&fromdate=' +
        this.fromdate +
        '&todate=' +
        this.todate
    );
  }
}
