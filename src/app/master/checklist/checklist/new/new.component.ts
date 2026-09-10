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

  checklistList = [];
  lists= [];
  departments;
  evaluation_parameter;
  checkListData: any;

  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.service.observableDepartment.subscribe(response =>{
      this.departments = response;
    });
    this.getCheckListData();
  }

  getCheckListData(){

    this.service.get('master/checklist.php?type=getMastercheckList').subscribe(response => {
      this.checkListData = response;
    });
  }

  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
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

    this.service.post('master/checklist.php?type=SaveMastercheckList', JSON.stringify(temp)).subscribe(response => {
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
    this.lists.splice(index, 1);
  }

}
