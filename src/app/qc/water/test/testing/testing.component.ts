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

  selectedPlan: any = {};
  remark = '';
  isTest = false;
  selectedTest: any = {};
  test_index = -1;
  sample_qty = 0;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
    // this.sample_qty= +this.results['chemical_qty'] + +this.results['microbiology_qty'];
    // console.log('rqty',+this.results['chemical_qty'] + +this.results['microbiology_qty']);
  }

  getPendingTesting() {
    this.service.get('qc/water.php?type=getPendingTestings').subscribe(response => {
      this.results = response;
    });
  }
  view(index) {
    this.selectedPlan = this.results[index];
    this.spec_tests = Array.isArray(this.selectedPlan['tests'])
      ? this.selectedPlan['tests'].map((t: any) => ({
          ...t,
          status: t.status || 'pending',
          result: t.result || '',
        }))
      : [];
    this.remark = this.selectedPlan['testing_remark'] || '';
    this.isView = true;
  }

  viewTest(index) {
    let tests = this.selectedPlan['tests'];
    this.selectedTest = tests[index];
    this.test_index = index;
    this.isTest = true;
  }
  save(){
    const pending = (this.spec_tests || []).some((x: any) => !x.status || x.status === 'pending');
    if (pending) {
      alertify.error('Complete all tests before save');
      return;
    }
    
    const temp: any = {};
    temp['id'] = this.selectedPlan['id'];
    temp['water_point_id'] = this.selectedPlan['water_point_id'];
    temp['remark'] = this.remark;
    temp['tests'] = this.spec_tests;
    
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

  selected_test_idx: number;
  isTestStart =false;
  selected_test: any = {};
  limit_type = '';
  result: any;
  status: any;
  testing_date: any;
  start_time: any;
  end_time: any;
  performTest(idx) { 
    this.selected_test_idx = idx;
    this.selected_test = this.spec_tests[idx];
    this.limit_type = this.selected_test['limit_type'];
    this.isTestStart = true;
  }
  spec_tests: any[] = [];

  updateResult(data) {
    if (!data.valid) {
      alertify.error('All Fields Are Required');
      return;
    }

    this.spec_tests[this.selected_test_idx]['result'] = data.value['result'];
    this.spec_tests[this.selected_test_idx]['testing_date'] = data.value['testing_date'];
    this.spec_tests[this.selected_test_idx]['start_time'] = data.value['start_time'];
    this.spec_tests[this.selected_test_idx]['end_time'] = data.value['end_time'];
    this.spec_tests[this.selected_test_idx]['tested_by'] = localStorage.getItem('emp_id');
    if ( this.limit_type   == 'Range') {
      if (Number(data.value['result']) < Number(this.spec_tests[this.selected_test_idx]['lower_limit']) ||
      Number(data.value['result']) > Number(this.spec_tests[this.selected_test_idx]['upper_limit'])) {
        this.spec_tests[this.selected_test_idx]['status'] = "Non Complies";
      } else {
        this.spec_tests[this.selected_test_idx]['status'] = "Complies";
      }
    }else {
      this.spec_tests[this.selected_test_idx]['status'] = data.value['status'];
     
    }
    
    this.spec_tests[this.selected_test_idx]['lab_report_no'] = data.value['lab_report_no'];
    data.resetForm();
    this.isTestStart = false;
  }

  // saveTest(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.selectedTest['result'] = temp['result'];
  //   this.selectedTest['remark'] = temp['remark'];
  //   this.selectedTest['start_time'] = temp['start_time'];
  //   this.selectedTest['end_time'] = temp['end_time'];

  //   if (this.selectedTest['limit'] == 'Limits') {
  //     if (+this.selectedTest['lower_limit'] <= +this.selectedTest['result'] && +this.selectedTest['result'] <= +this.selectedTest['upper_limit']) {
  //       this.selectedTest['observation'] = 'pass';
  //     } else {
  //       this.selectedTest['observation'] = 'failed';
  //     }
  //   }
  //   this.selectedTest['status'] = 'done';

  //   let tests = this.selectedPlan['tests'];
  //   tests[this.test_index] = this.selectedTest;

  //   this.service.post('qc/water.php?type=saveTest&id=' + this.selectedPlan['sampling_no'] + '&test=' + this.test_index, JSON.stringify(tests)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success('Test has been saved successfully');
  //       this.isTest = false;
  //       this.isView = false;
  //       this.getPendingTesting();
  //     } else {
  //       alertify.error('An error occured, please try again!');
  //     }
  //   });
  // }

}
