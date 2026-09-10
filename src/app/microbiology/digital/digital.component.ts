import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 
@Component({
  selector: 'app-digital',
  templateUrl: './digital.component.html',
  styleUrls: ['./digital.component.css'],
  providers: [DatePipe],
})
export class DigitalComponent implements OnInit {
  loading;
  checkOther;
  from_date = '';
  to_date = '';
  today = '';
  results;
  isNew = false;
  isView = false;
  selectedResult = [];
  checkpoints = [];
  checkpoint;
  fg_subtype_list;
  fg_sizes_list;
  fg_shapes_list;
  flag = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  addEmployees() {}
  ngOnInit(): void {
    this.getHdpe();
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

  getHdpe() {
    this.service
      .get(
        'microbiology/hdpe.php?type=getRecords&from_date=' +
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
      'microbiology/hdpe.php?type=downloadRecords&from_date=' +
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
    let temp = data.value;
    this.service
      .post('microbiology/hdpe.php?type=saveRecord', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getHdpe();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  // add(){
  //   alert('data stored');
  // }
  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.checkpoints[this.checkpoints.length] = temp['checkpoint'];
    data.resetForm();
  }

  addFgSubtypes(data) {
    if (!data.valid) {
      alert('All fiels are required');
      return;
    }
    this.fg_subtype_list[this.fg_subtype_list.length] = data.value;
    data.resetForm();
  }
  deleteRow(idx, id) {
    if (id == 1) {
      this.fg_subtype_list.splice(idx, 1);
    } else if (id == 2) {
      this.fg_sizes_list.splice(idx, 1);
    } else {
      this.fg_shapes_list.splice(idx, 1);
    }
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