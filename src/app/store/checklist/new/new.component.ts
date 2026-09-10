import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  appraisal_type: any;
  addData(_t125: any) {
    throw new Error('Method not implemented.');
  }

  checklistList = [];
  evualation_parameter: any;
  results;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getLog();
  }

  getLog() {
    this.service
      .get('store/master_checklist.php?type=gatemaster_checklist')
      .subscribe((response) => {
        this.results = response;
      });
  }

  saveChecklist(data) {
    this.service
      .post(
        'store/master_checklist.php?type=savechecklist',
        JSON.stringify(this.checklistList)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success(' Records saved successfully...');
          this.checklistList = [];
          this.router.navigate(['/store/master_checklist']);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  adddata(data) {
    if (!data.valid) {
      alertify.error('All Field are required');
      return;
    }
    let temp = data.value;
    this.checklistList[this.checklistList.length] = temp;
    data.reset();
  }
  // adddata(data) {
  //   if (!data.valid) {
  //     alertify.error("All fields are required");
  //     return;
  //   }

  //   let newItem = data.value;
  //   this.checklistList.push(newItem);
  //   data.reset();

  // }
  delData(index) {
    this.checklistList.splice(index, 1);
  }
}
