import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-committee-attendence',
  templateUrl: './committee-attendence.component.html',
  styleUrls: ['./committee-attendence.component.css'],
  providers: [DatePipe],
})
export class CommitteeAttendenceComponent implements OnInit {
  date;
  departments;
  isNew = false;
  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe,
    private router: Router
  ) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getAttendenceAll();
  }

  NewaTT() {
    this.isNew = true;
    this.GET_InvolvedPersons();
    this.getAttendenceToday();
  }
  dates;
  TodaysAttdence;
  AllAttdence;
  getAttendenceAll() {
    this.service
      .get('ehs/attendece.php?type=getAttendenceAllComitee&dates=' + this.dates)
      .subscribe((response) => {
        this.AllAttdence = response;
      });
  }
  getAttendenceToday() {
    this.service
      .get('ehs/attendece.php?type=getAttendenceTodayComitee&date=' + this.date)
      .subscribe((response) => {
        this.TodaysAttdence = response;
      });
  }
  selectedEmp = [];
  getEmpdata(i) {
    this.selectedEmp = this.InvolvedPersons[i - 1];
  }

  addAtt(data) {
    let temp = data.value;
    temp['date'] = this.date;
    this.service
      .post('ehs/attendece.php?type=saveEHS_Att_committe', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getAttendenceToday();
          alertify.success(this.service.t('common.savedSuccess'));
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }
  RemoveAtt(id) {
    this.service
      .post('ehs/attendece.php?type=RemoveAtt_commitee&id=' + id, null)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getAttendenceToday();
          alertify.success('Removed Successfully');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }

  InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
  saveData(data) {
    let temp = data.value;
  }
}
