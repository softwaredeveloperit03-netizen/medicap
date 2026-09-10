import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-committee',
  templateUrl: './committee.component.html',
  styleUrls: ['./committee.component.css'],
  providers: [DatePipe],
})
export class CommitteeComponent implements OnInit {
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
  comitteeList = [];
  ngOnInit() {
    this.getComeeteeList();
  }
  InvolvedPersons;

  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
  NewaTT() {
    this.isNew = true;
    this.GET_InvolvedPersons();
    this.getComeeteeList();
  }
  ComeeteeList;
  getComeeteeList() {
    this.service
      .get('ehs/attendece.php?type=getComeeteeList')
      .subscribe((response) => {
        this.ComeeteeList = response;
      });
  }
  addAtt(data) {
    let temp = data.value;
    temp['date'] = this.date;
    this.service
      .post('ehs/Comeetee.php?type=saveComeeteeList', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getComeeteeList();
          alertify.success(this.service.t('common.savedSuccess'));
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }
  selectedEmp = [];
  getEmpdata(i) {
    this.selectedEmp = this.InvolvedPersons[i - 1];
  }
  RemoveAtt(id) {
    this.service
      .post('ehs/Comeetee.php?type=RemoveComeeteePerson&id=' + id, null)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getComeeteeList();
          alertify.success('Removed Successfully');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }
}
