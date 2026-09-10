import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isLessthan = false;
  isMorethan = false;
  isLimit = true;

  tests;
  subtests = [];

  testsList = [];
  revisionList = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getTests();
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
    const element1 = document.getElementById('test') as HTMLElement;
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
    const element1 = document.getElementById('spec_no') as HTMLElement;
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
    this.service.post('qc/specification/water.php?type=saveSpecification', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Specification Saved Successfully');
        this.testsList = [];
        this.revisionList = [];
        data.resetForm();
        this.router.navigate(['/specifications/water/']);
      } else {
        alertify.error('Failed: All fields are required');
      }
    });
  }

}
