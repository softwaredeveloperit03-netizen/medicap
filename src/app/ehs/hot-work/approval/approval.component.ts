import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe],
})
export class ApprovalComponent implements OnInit {
  departments;
  isView = false;
  entrys;
  applicable = '';
  selectedBatch = [];
  selectedCondition = [];
  comment;
  emp_id: string;
  isDIGI: boolean;
  isbutton: boolean;
  status: any;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {}

  ngOnInit() {
    this.getDepartments();
    this.getEntrys();
  }
  view(index) {
    this.selectedBatch = this.entrys[index];
    this.selectedCondition = this.selectedBatch['conditions'];
    this.isView = true;
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }
  getEntrys() {
    this.service
      .get('ehs/hotwater.php?type=getCheckedWater')
      .subscribe((response) => {
        this.entrys = response;
      });
  }

  updateHotWork(status) {
    this.service
      .get(
        'ehs/hotwater.php?type=approveHotWater&status=' +
          status +
          '&id=' +
          this.selectedBatch['id'] +
          '&comment=' +
          this.comment
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Data updated Successfully!');
          this.isView = false;
          this.getEntrys();
        } else {
          alertify.error('Failed an error occured,please try again!');
        }
      });
  }

  openDigiSign(value) {
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = value;
  }

  loginPassward = '';
  digiSign(data) {
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service
      .get(
        'login.php?type=checkDigiSIgn&mpin=' +
          this.loginPassward +
          '&emp_id=' +
          this.emp_id
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward = '';
         this.updateHotWork(this.status)
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }
}
