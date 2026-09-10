import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  grades;
  isLessthan = false;
  isMorethan = false;
  isLimit = true;
  isNewTest=false;
  isNewSubtest =false;
  tests;
  subtest='';
  subtests;
  test='';
  testsList = [];
  revisionList = [];
  selectedTest= [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    })
    this.getTests();
  }

  getTests() {
    this.service.get('common.php?type=getTests&test_type=water').subscribe(response => {
      this.tests = response;
    });
  }
  
  checkTest(event) {
    console.log("function Call");
    let flag = 0;
    this.tests.forEach(element => {
      if (element['test'].toUpperCase().includes(event['filter'].toUpperCase())) {
        flag = 1;
      }
    });
    if (flag == 0) {
      console.log("Change")
      this.isNewTest = true;
    }
  }

  getSubtest(event) {
    this.subtests = event.value.subtests;
  }

  
  checkSubtest(event) {
    console.log(this.selectedTest);
    let flag = 0;
    this.subtests.forEach(element => {
      if (element['subtest'].toUpperCase().includes(event['filter'].toUpperCase())) {
        flag = 1;
      }
    });
    if (flag == 0) {
      this.isNewSubtest = true;
    }
  }

  checkLimits(getval) {
    if (getval == 'Limits') {
      this.isLessthan = false;
      this.isMorethan = false;
      this.isLimit = true;
    } else if (getval == 'LessThan') {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = true;
    } else if (getval == 'MoreThan') {
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
    //element1.focus();
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
    // element1.focus();
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
    this.service.post('qc/water.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Specification Saved Successfully');
        this.testsList = [];
        this.revisionList = [];
        data.resetForm();
      } else {
        alertify.error('Failed: All fields are required');
      }
    });
  }

  saveTest(){
    let temp = {};
    temp['test'] =this.test;
    temp['test_type'] ="water";
    this.service.post('master/test.php?type=saveTest',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isNewTest = false;
        this.test = '';
        this.getTests();
        alertify.success('Test saved successfully');
      } else {
        alertify.error('Failed: An error occured');
      }
    });
  }

  saveSubtest(data){
    if(!data.valid){
      alertify.error("Plese Enter Subtest");
      return;
    }
      this.service.post('master/test.php?type=saveSubTest',JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] == 'success') {
          this.isNewSubtest = false;
          this.getSubTests();
          this.test = '';
          alertify.success('Test saved successfully');
        } else {
          alertify.error('Failed: An error occured');
        }
      });
  }

  getSubTests() {
    this.service.get('master/test.php?type=getSubTests&test_type=water&test=' + this.selectedTest['test']).subscribe(response => {
      this.subtests  = response;
    });
  }
}
