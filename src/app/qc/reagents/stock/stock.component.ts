import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stock',
  templateUrl: './stock.component.html',
  styleUrls: ['./stock.component.css'],
  providers: [DatePipe],
})
export class StockComponent implements OnInit {
  stocks;
  vendors;
  loading;
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  from_date = '';
  to_date = '';
  isView = false;
  results;
  grndetails = [];
  selectedReport = [];
  grades;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.service.observableGrade.subscribe((response) => {
      this.grades = response;
    });
    this.getAllStock(); /* 
    this.getMaterials(); */

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

  /* 
  getMaterials(){
    this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    });
  } */

  getAllStock() {
    this.service
      .get(
        'qc/indicator.php?type=getStock&status=' +
          this.status +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.stocks = response;
      });
  }

  generatePDF() {
    let url =
      this.service.url +
      'pdf/stockbook.php?token=' +
      localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download() {
    this.service.open(
      'qc/chemical.php?type=downloadgetStock&status=' +
        this.status +
        '&from_date=' +
        this.from_date +
        '&grade=' +
        this.grade +
        '&to_date=' +
        this.to_date
    );
  }
}
