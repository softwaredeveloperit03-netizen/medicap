import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-auditcheck',
  templateUrl: './auditcheck.component.html',
  styleUrls: ['./auditcheck.component.css']
})
export class AuditcheckComponent implements OnInit {
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
    this.getchecklist();
  }

  getchecklist(){



    this.service.get('qa/audit.php?type=getaudtcheckList&department1='+this.department).subscribe(response => {
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
   

    this.service.post('qa/audit.php?type=save_audit_checklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.getchecklist();
      data.resetForm();
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
