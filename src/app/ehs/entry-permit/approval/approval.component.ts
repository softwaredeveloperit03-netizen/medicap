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
  date;
  departments;
  isView = false;
  entrys;
  comment;
  applicable = '';
  selectedBatch = [];
  selectedCondition = [];
  conditions = [
    { id: 1, condition: 'Washing Complete ?', applicable: '' },
    { id: 2, condition: 'Complete Drained ?', applicable: '' },
    {
      id: 3,
      condition:
        'All incoming lines and outgoing lines suitably blinded/disconnected',
      applicable: '',
    },
    {
      id: 4,
      condition: 'Suitable to enter With respect temperature',
      applicable: '',
    },
    {
      id: 5,
      condition: 'Surrounding area cleaned and free form Hazardous chemical',
      applicable: '',
    },
    {
      id: 6,
      condition: 'Is there airline continue in Vessel/reactor',
      applicable: '',
    },
    { id: 7, condition: 'Motor belt is removed', applicable: '' },
    {
      id: 8,
      condition: 'Equipment electrically isolated /fuse removed',
      applicable: '',
    },
    {
      id: 9,
      condition: 'Safety belt and ladder used by the personal going inside',
      applicable: '',
    },
    {
      id: 10,
      condition: 'Name of the person standing at manhole holding the life line',
      applicable: '',
    },
    {
      id: 11,
      condition: 'Check all parameter before enter the confined/vessel',
      applicable: '',
    },
  ];
  emp_id: string;
  isDIGI: boolean;
  status: any;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDepartments();
    this.getEntrys();
  }
  view(index) {
    this.selectedBatch = this.entrys[index];
    this.selectedCondition = this.selectedBatch['conditions'];
    this.isView = true;
  }
  change(index) {
    let con = this.conditions[index];
    console.log(this.applicable);
    con['applicable'] = con['applicable'];
    this.conditions[index] = con;
    // console.log(this.conditions)
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }
  getEntrys() {
    this.service
      .get('ehs/vesselEntry.php?type=getCheckedVessel')
      .subscribe((response) => {
        this.entrys = response;
      });
  }

  updateHotWork(status) {
    this.service
      .get(
        'ehs/vesselEntry.php?type=approveVessel&status=' +
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