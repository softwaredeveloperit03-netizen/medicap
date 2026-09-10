import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-testing',
  templateUrl: './testing.component.html',
  styleUrls: ['./testing.component.css']
})
export class TestingComponent implements OnInit {

  isView = false;
  results;

  selectedPlan = [];

  isTest = false;
  selectedTest = [];
  test_index = -1;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
  }

  getPendingTesting() {
    this.service.get('qc/water.php?type=getPendingTesting').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.isView = true;
  }

  viewTest(index) {
    let tests = this.selectedPlan['tests'];
    this.selectedTest = tests[index];
     this.test_index = index;
    this.isTest = true;
  }

  saveTest(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.selectedTest['result'] = temp['result'];
    this.selectedTest['remark'] = temp['remark'];
    this.selectedTest['start_time'] = temp['start_time'];
    this.selectedTest['end_time'] = temp['end_time'];

    if (this.selectedTest['limit'] == 'Limits') {
      if (+this.selectedTest['lower_limit'] <= +this.selectedTest['result'] && +this.selectedTest['result'] <= +this.selectedTest['upper_limit']) {
        this.selectedTest['observation'] = 'pass';
      } else {
        this.selectedTest['observation'] = 'failed';
      }
    }
    this.selectedTest['status'] = 'done';

    let tests = this.selectedPlan['tests'];
    tests[this.test_index] = this.selectedTest;

    this.service.post('qc/water.php?type=saveTest&id=' + this.selectedPlan['sampling_no'] + '&test=' + this.test_index, JSON.stringify(tests)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Test has been saved successfully');
        this.isTest = false;
        this.isView = false;
        this.getPendingTesting();
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
