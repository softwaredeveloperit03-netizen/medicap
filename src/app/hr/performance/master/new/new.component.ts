import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  appraisal_type='1st Level(Employee Feedback)';

  checklistList = [];
  departments;
  designations;
  results;



  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.service.observableDepartment.subscribe(response =>{
      this.departments = response;
    });
    // this.getCheckListData();
  }

  // getCheckListData(){
  //   this.service.get('hr/appraisalchecklist.php?type=getMastercheckList').subscribe(response => {
  //     this.results  = response;
  //   });
  // }
  getDesignation(index){
     let obj = this.departments[index];
     this.designations = obj['designations'];
  }

  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
    switch(temp.evualation_parameter){
        case "Remarks":{
          //logic for Remarks will go here
          break;
        }
        case "Multiple Choice":{
          //logic for Multiple Choice will go here
          tempData['question_title'] = temp.question_title;
          tempData['options'] = [];
          tempData['options'][0] = temp.option_one;
          tempData['options'][1] = temp.option_two;
          tempData['options'][2] = temp.option_three;
          tempData['options'][3] = temp.option_four;
          // console.log(tempData);
          temp.multipleChoiceQnOptions = [];
          temp.multipleChoiceQnOptions = tempData;
          break;
        }
        case "Q&A":{
          //logic for Q&A will go here
          // temp.qnaQuestion = '';
          // temp.qnaQuestion = temp.question_title;
          break;
        }
        case "Yes/No":{
          //logic for Yes/No will go here
          break;
        }
        case "Applicable/Not Applicable":{
          //logic for Applicable/Not Applicable will go here
          break;
        }
    }
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }
  
  // saveChecklist(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   temp['details']=this.checklistList;
  //   this.service.post('hr/appraisalchecklist.php?type=saveChecklist', JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alert('All checklist Saved Successfully');
  //       this.checklistList = [];
  //       this.router.navigate(['/hr/performance/master']);
  //     } else {
  //       console.log(response);
  //       alert('Failed: An error occured, please try again!');
  //     }
  //   });
  // }
  saveChecklist(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['checklistList']=this.checklistList;

    this.service.post('hr/appraisalchecklist.php?type=saveChecklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.checklistList = [];
        this.router.navigate(['/hr/performance/master']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  
  delData(index) {
    this.checklistList.splice(index, 1);
  }

  del(index) {
    this.results.splice(index, 1);
  }



}


