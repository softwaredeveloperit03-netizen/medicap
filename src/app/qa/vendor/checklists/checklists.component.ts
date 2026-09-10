import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-checklists',
  templateUrl: './checklists.component.html',
  styleUrls: ['./checklists.component.css'],
})
export class ChecklistsComponent implements OnInit {


  perticularType = 'Header';

  isNew = false;
 
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getChecklistByLog();
  }


  checkListData;
  getChecklistByLog() {
    this.service.get('qa/audit.php?type=getChecklistByLog').subscribe((response) => {
        this.checkListData = response;
    });
  }


  isChecklist = false;
  selectedCheck = {};
  view(data){
    this.selectedCheck = data;
    this.isChecklist = true;
  }

 

  check_heading = 'Raw Material';
  checklistList = [];
  addData(data) {
    
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.check_heading == '') {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
    temp['check_heading'] = this.check_heading;
    this.checklistList.push(temp);
    data.resetForm();
  }

  saveChecklist(data) {
    console.log(data.value);

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = {};
    temp['checklistList'] = this.checklistList;
    this.service.post('qa/audit.php?type=Save_Qualification_Questionnaire', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('checklist Saved Successfully');
          this.getChecklistByLog();
          data.resetForm();
          this.isNew = false;
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
  }


}

