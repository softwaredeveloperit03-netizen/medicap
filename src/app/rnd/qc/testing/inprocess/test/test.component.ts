import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-test',
  templateUrl: './test.component.html',
  styleUrls: ['./test.component.css']
})
export class TestComponent implements OnInit {

  isView = false;
  isTest = false;
  isStart = false;
  results;
  analysis_start_time = '';
  analysis_end_time = '';

  selectedResult = [];
  selectedTesting = [];
  tests = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingTestings();
  }

  getPendingTestings(){
    this.service.get('production/technical.php?type=getPendingTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.tests = this.selectedResult['tests']
    this.isView = true;
  }

  viewTest(index){
    this.selectedTesting = this.tests[index];
    this.isView = true;
    this.isTest = true;
  }

  getCurrentTime(value){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if(value == 'start'){
      this.analysis_start_time = h + ':' + m;
      this.analysis_start_time = new Date().toLocaleTimeString();;
      this.isStart = true;
    }if(value == 'stop'){
      this.analysis_end_time = h + ':' + m;
      this.analysis_end_time = new Date().toLocaleTimeString();;
    }
  }

  submitTest(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['test_no'] = this.selectedTesting["id"];
    temp['testing_no'] = this.selectedTesting["testing_no"];
    temp['limit_type'] = this.selectedTesting["limit_type"];
    temp['lower_limit'] = this.selectedTesting["lower_limit"];
    temp['upper_limit'] = this.selectedTesting["upper_limit"];
    temp['lessthan'] = this.selectedTesting["lessthan"];
    temp['morethan'] = this.selectedTesting["morethan"];
    
    let method_details = this.selectedTesting['method_details'];
    for (let i = 0; i < method_details.length; i++) {
      let description = method_details[i];

      if (description["option"] == "chemical") {
        let chemicals = description['list'];
        for (let j = 0; j < chemicals.length; j++) {
          let chemical = chemicals[j];
          chemical['batches'] = null;
          chemicals[j] = chemical;
        }
        description['list'] = chemicals;
      }
      method_details[i] = description;
    }
    temp['method_details'] = method_details;
    
    this.service.post('production/technical.php?type=saveTesting', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Test Successfully Send For Approval!');
        data.resetForm();
        this.getPendingTestings();
        this.isTest = false;
        this.isView = false;
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
