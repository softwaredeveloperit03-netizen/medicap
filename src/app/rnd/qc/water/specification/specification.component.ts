import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-specification',
  templateUrl: './specification.component.html',
  styleUrls: ['./specification.component.css']
})
export class SpecificationComponent implements OnInit {

  isView = false;
  isNew = false;
  results;

  grades;

  isLessthan = false;
  isMorethan = false;
  isLimit = true;

  tests;
  subtests = [];

  testsList = [];
  revisionList = [];
  selectedSpecification = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSpecifications();
  }

  getSpecifications() {
    this.service.get('qc/water.php?type=getSpecifications').subscribe(response => {
      this.results = response;
    });
  }
  new() {
    this.getGrades();
    this.getTests();
    this.isNew = true;
  }

  getGrades() {
    this.service.get('qc/water.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getTests() {
    this.service.get('qc/water.php?type=getTests').subscribe(response => {
      this.tests = response;
    });
  }

  getSubtests(index) {
    index = index - 1;
    let test = this.tests[index];
    this.subtests = test['subtests'];
  }

  checkLimits(getval){
    if(getval == 'Limits'){
      this.isLessthan = false;
      this.isMorethan = false;
      this.isLimit = true;
    }else if(getval == 'LessThan'){
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = true;
    }else if(getval == 'MoreThan'){
      this.isLimit = false;
      this.isMorethan = true;
      this.isLessthan = false;
    } else {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = false;
    }
  }

  addTest(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.testsList[Object.keys(this.testsList).length] = temp;
    data.resetForm();
    this.isLessthan = false;
    this.isMorethan = false;
    this.isLimit = false;
    const element1 = document.getElementById('water_type') as HTMLElement;
    element1.focus();
  }

  addRevision(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('water_type') as HTMLElement;
    element1.focus();
  }

  deleteTest(index) {
    this.testsList.splice(index, 1);
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  saveSpecification(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['tests'] = this.testsList;
    temp['revisions'] = this.revisionList;
    this.service.post('qc/water.php?type=saveSpecification', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Specification Saved Successfully');
        this.testsList = [];
        this.revisionList = [];
        data.resetForm();
        this.isNew = false;
        this.getSpecifications();
      } else {
        alertify.error('Failed: All fields are required');
      }
    });
  }

  downloadReport(){
    this.service.open('pdf1/water.php?type=specificationReport');
  }
  view(index){
    this.selectedSpecification = this.results[index];
    this.isView = true;
  }

  downloadPDF(id, type){
    if(type == 'manual'){
      this.service.open('pdf1/water.php?type=specification&id='+id);
    }else{
      this.service.open('pdf1/water.php?type=specificationdigital&id='+id);
    }
  }
}
