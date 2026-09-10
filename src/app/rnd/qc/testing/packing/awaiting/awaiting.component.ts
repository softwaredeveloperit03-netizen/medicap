import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isUser = false;
  isChecker = false;
  isApprover = false;
  isInit = true;
  isMoa = false;
  testings;
  selectedTesting = [];
  selectedMOA = [];

  isStart = false;
  start_date;
  end_time;
  end_date;
  isStop = false;

  rechecks = [];
  pendings = [];
  tests = [];
  constructor(private service: DataAccessService) {/* 
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver'))); */
  }

  ngOnInit() {
    this.getPendingTestingForms();
  }

  getPendingTestingForms() {
    this.service.get('qc/testing/packing.php?type=getPendingTestingForms').subscribe(response => {
      this.testings = response;
    });
  } 

  viewSpecification(index) {
    this.rechecks = [];
    this.pendings = [];
    this.selectedTesting = this.testings[index];
    this.tests = this.selectedTesting['tests'];

    let i = 0, j = 0;
    this.tests.forEach(element => {
      if (element['status'] == 'recheck' && element['fail_status'] == 'correction' && element['fail_form'] == 'error1') {
        this.rechecks[i] = element;
        i++;
      } else {
        this.pendings[j] = element;
        j++;
      }
    });
    this.isInit = false;
  }

  viewMOA(index) {
    this.selectedMOA = this.tests[index];
    this.isMoa = true;
  }

  submitTest(data) {
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['test_no'] = this.selectedMOA["id"];
    temp['testing_no'] = this.selectedMOA["testing_no"];
    
    let descriptions = this.selectedMOA['descriptions'];
    for (let i = 0; i < descriptions.length; i++) {
      let description = descriptions[i];

      if (description["option"] == "chemical") {
        let chemicals = description['list'];
        for (let j = 0; j < chemicals.length; j++) {
          let chemical = chemicals[j];
          chemical['batches'] = null;
          chemicals[j] = chemical;
        }
        description['list'] = chemicals;
      }
      descriptions[i] = description;
    }
    temp['descriptions'] = descriptions;
    
    this.service.post('qc/testing/packing.php?type=saveTestingForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPendingTestingForms();
        this.isMoa = false;
        this.isInit = true;
        alertify.success('test successfully send for approval');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

  updateSamplingTime(value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'end') {
      var d = new Date(),
      h = (d.getHours()<10?'0':'') + d.getHours(),
      m = (d.getMinutes()<10?'0':'') + d.getMinutes();
      let time = new Date().toLocaleTimeString();
      this.end_time = h + ':' + m;
      this.end_date = new Date();
      this.isStop = true;
    }
  }

}
