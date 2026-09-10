import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ipqc',
  templateUrl: './ipqc.component.html',
  styleUrls: ['./ipqc.component.css']
})
export class IpqcComponent implements OnInit {
  department;
  checklistList = [];
  lists= [];
  departments;
  evaluation_parameter;
  checkListData: any;
  loading;
module: any;

  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.service.observableDepartment.subscribe(response =>{
      this.departments = response;
    });
    this.getCheckListData();
    this.getStages();
  }
  selectedChecks=[];
  steps(index){
    this.selectedChecks=this.Stages[index-1]
    console.log(this.selectedChecks['step'])
  }
  Stages;
  getStages(){
    this.service.get('bmr/process.php?type=get_stage_step').subscribe(response => {
      this.Stages = response;
    });
  }
  getCheckListData(){



    this.service.get('master/checklist.php?type=getMastercheckList_inprocess_checks').subscribe(response => {
      this.checkListData = response;
    });
  }
  checklist_heading;
  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
temp['checklist_heading']=this.checklist_heading;
   
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }

  saveChecklist(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['checklistList']=this.checklistList;

    this.service.post('master/checklist.php?type=SaveMastercheckList_inprocess', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.checklistList = [];
        this.router.navigate(['/checklist']);
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
    this.checkListData.splice(index, 1);
  }

}
