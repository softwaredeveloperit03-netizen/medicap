import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-lab-cleaning',
  templateUrl: './lab-cleaning.component.html',
  styleUrls: ['./lab-cleaning.component.css'],
  providers: [DatePipe],
})
export class LabCleaningComponent implements OnInit {
  from_date = '';
  to_date = '';
  results;
  isDaily = false;
  isWeekly = false;
  isMonthly = false;
  labours;
  lafs;
  balances;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getlabs();
    this.getLabours();
    this.getLaf();
    this.getBalances();
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
  getBalances() {
    this.service.get('equipments.php?type=getBalance').subscribe((response) => {
      this.balances = response;
    });
  }

  getLaf() {
    this.service.get('equipments.php?type=getLAF').subscribe((response) => {
      this.lafs = response;
    });
  }
  getLabours() {
    this.service.get('common.php?type=getOperators').subscribe((response) => {
      this.labours = response;
    });
  }
  getlabs() {
    this.service
      .get(
        'microbiology/lab.php?type=getPendingLab&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  download() {
    this.service.open(
      'microbiology/lab.php?type=downloadPendingLab&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
  save(data) {
    if (!data.valid) {
      alertify.error('all field are required!');
      return;
    }
    this.service
      .post('microbiology/lab.php?type=saveLab', JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isDaily = false;
          this.isWeekly = false;
          this.isMonthly = false;
          this.getlabs();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
}
