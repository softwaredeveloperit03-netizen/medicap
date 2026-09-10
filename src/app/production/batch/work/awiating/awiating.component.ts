import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awiating',
  templateUrl: './awiating.component.html',
  styleUrls: ['./awiating.component.css']
})
export class AwiatingComponent implements OnInit {

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
    this.getReadyBatchPlans();
    this.getOfficers();
    this.getOperators();
  }

  getReadyBatchPlans() {
    this.service.get('production/work.php?type=getReadyBatchPlans').subscribe(response => {
      this.results = response;
    });
  }

  getOfficers(){
    this.service.get('production/work.php?type=getOfficers').subscribe(response => {
      this.officers = response;
    });
  }

  getOperators(){
    this.service.get('production/work.php?type=getOperators').subscribe(response => {
      this.operators = response;
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

  newData(index){
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isView = true;
  }

  Add(data){
    if (data.valid) {
      this.selectedOfficer['bmr_no'] = this.selectedResult['id'];
      this.selectedOfficer['stage'] = this.selectedStage['stage'];
      this.officerList[Object.keys(this.officerList).length] = this.selectedOfficer;
      data.resetForm();
    }
  }

  getOfficersDetails(index){
    index = index  - 1;
    if(index !== -1){
      this.selectedOfficer = this.officers[index];
    }
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

  getOpretorDetails(index){
    index = index  - 1;
    if(index !== -1){
      this.selectedOperator = this.operators[index];
    }
  }

  work(id) {
    this.router.navigate(['/batch/work/' + id]);
  }

  stage(id, stage) {
    if (stage == 'DISPENSING') {
      this.router.navigate(['/batch/dispensing/' + id]);
    } else {
      this.router.navigate(['/batch/new/' + id]);
    }
  }

  save(){
    this.service.post('production/work.php?type=saveStageOfficers&id=' + this.selectedStage['bmr_no'],JSON.stringify(this.officerList)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Officers Allocated Successfully!');
        this.officerList = [];
        this.isView = false;
        this.isNew = false;
        this.getReadyBatchPlans();  
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

  saveData(){
    this.service.post('production/work.php?type=saveStageOperators&id=' + this.selectedStep['bmr_no'],JSON.stringify(this.operatorList)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Operator Allocated Successfully!');
        this.operatorList = [];
        this.isShow = false;
        this.isNew = false;
        this.getReadyBatchPlans();  
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

}
