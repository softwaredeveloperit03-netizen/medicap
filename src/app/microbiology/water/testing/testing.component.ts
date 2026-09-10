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
  remark='';
  isTest = false;
  selectedTest = [];
  test_index = -1;
  sample_qty=0;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
    this.sample_qty= +this.results['chemical_qty'] + +this.results['microbiology_qty'];
    console.log('rqty',+this.results['chemical_qty'] + +this.results['microbiology_qty']);
  }

  getPendingTesting() {
    this.service.get('qc/water.php?type=getPendingTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.sample_qty= +this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty'];
    console.log('qty',+this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty']);
    this.isView = true;
  }

  viewTest(index) {
    let tests = this.selectedPlan['tests'];
    this.selectedTest = tests[index];
    this.test_index = index;
    this.isTest = true;
  }

  save(){
    let temp=this.selectedPlan;
    temp['remark']=this.remark;
    this.service.post('qc/water.php?type=saveTestingReport',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.getPendingTesting();
        this.isView=false;
      }else{
        alertify.error('some error occured!please try again');
      }
    });
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
