import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  products;
  results;
  officers;
  operators;
  selectedResult = [];
  isNew = false;
  isView = false;
  isShow = false;
  officerList = [];
  operatorList = [];
  selectedOfficer = [];
  selectedOperator = [];

  selectedDone = [];
  isDone = false;

  data = [];

  selectedStage = [];
  selectedStep = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getWorkAllocationLog();
  }

  getWorkAllocationLog() {
    this.service.get('production/work.php?type=getWorkAllocationLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    //this.data = JSON.parse(this.selectedBMR['data']);
    this.isNew = true;
  }

  done(index){
    let stages = this.selectedResult['stages'];
    this.selectedDone = stages[index];
    this.isDone = true;
  }

  oldData(index){
    let stages = this.selectedResult['stages'];
    this.selectedStep = stages[index];
    this.isShow = true;
  }

  Adddata(data){
    if (data.valid) {
      this.selectedOperator['bmr_no'] = this.selectedResult['id'];
      this.selectedOperator['stage'] = this.selectedStep['stage'];
      this.selectedOperator['emp_id'] = this.selectedStep['labour_no'];
      this.operatorList[Object.keys(this.operatorList).length] = this.selectedOperator;
      data.resetForm();
    }
  }

}
