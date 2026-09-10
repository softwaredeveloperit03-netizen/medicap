import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bod-log',
  templateUrl: './bod-log.component.html',
  styleUrls: ['./bod-log.component.css'],
  providers: [DatePipe],
})
export class BodLogComponent implements OnInit {
  from_date = '';
  to_date = '';
  results;
  labours;
  isNew = false;
  lafs;
  balances;
  incubators;
  selectedResult = [];
  flag = false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getAutoclave();
    this.getLabours();
    this.getIncubator();
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

  getIncubator() {
    this.service
      .get('equipments.php?type=getBODIncubators')
      .subscribe((response) => {
        this.incubators = response;
      });
  }
  getLabours() {
    this.service.get('common.php?type=getOperators').subscribe((response) => {
      this.labours = response;
    });
  }

  getAutoclave() {
    this.service
      .get(
        'microbiology/incubator.php?type=getIncubatorBodUsage&from_date=' +
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
      'microbiology/incubator.php?type=downloadIncubatorBodUsage&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'microbiology/incubator.php?type=saveIncubatorBodUsage',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isNew = false;
          this.getAutoclave();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }

  complete(index) {
    this.selectedResult = this.results[index];
    this.service
      .get(
        'microbiology/incubator.php?type=completeBodUsage&id=' +
          this.selectedResult['id']
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getAutoclave();
          alertify.success('Complete BOD Usage Successfully !!');
        } else {
          alertify.error('Error to Complete BOD !!');
        }
      });
  }

  onValueChange(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag = false;
    } else {
      this.flag = true;
    }
  }
}
