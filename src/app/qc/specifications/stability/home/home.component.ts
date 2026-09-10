import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
})
export class HomeComponent implements OnInit {
  specifications;
  dosages;
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  dosage_form = '';
  grade = '';
  status = '';
  maxdate;
  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getDosages();
    this.getReports();
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
  clearrecords() {}

  getDosages() {
    this.service
      .get('qc/specification/stability.php?type=getDosages')
      .subscribe((response) => {
        this.dosages = response;
      });
  }

  getReports() {
    this.service
      .get(
        'qc/specification/stability.php?type=getSpecificationsLog&dosage_form=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&status=' +
          this.status
      )
      .subscribe((response) => {
        this.specifications = response;
      });
  }

  clear() {
    this.dosage_form = '';
    this.grade = '';
    this.status = '';
    this.getReports();
  }

  viewSpecification(index) {
    this.selectedSpec = this.specifications[index];
    this.selectedSpec['dosage_form'] = this.specifications['dosage_form'];
    this.isViewSpecification = true;
  }

  downloadPDF(type) {
    if (type == 'manual') {
      this.service.open(
        'qc/specification/stability.php?type=SpecificationPDF&specification_no=' +
          this.selectedSpec['specification_no']
      );
    } else {
      this.service.open(
        'qc/specification/stability.php?type=SpecificationdigitalPDF&specification_no=' +
          this.selectedSpec['specification_no']
      );
    }
  }
  downloadReport() {
    this.service.open(
      'qc/specification/stability.php?type=SpecificationLogPDF'
    );
  }
}
