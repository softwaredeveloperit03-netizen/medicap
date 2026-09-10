import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-add-checklist',
  templateUrl: './add-checklist.component.html',
  styleUrls: ['./add-checklist.component.css']
})
export class AddChecklistComponent implements OnInit {
  checlistData = [];

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
  }
  addChecklist(data) {
    const invalid = [];
    const controls = data.controls;
    for (const name in controls) {
      if (controls[name].invalid) {
        invalid.push(name);
      }
    }
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.checlistData.push(data.value);
  }

  saveChecklist(form) {
    const invalid = [];
    const controls = form.controls;
    for (const name in controls) {
      if (controls[name].invalid) {
        invalid.push(name);
      }
    }
    if (!form.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.checlistData.length == 0) {
      alertify.error('Please enter checklist details');
      return;
    }
 
    
    let data={
      "department":"QC",
      "checklist_heading":form.value['checklist_heading'],
      "version_no":form.value['version_no'],
      "form_type":form.value['form_type'],
      "form_data" : this.checlistData
    };
    console.log(JSON.stringify(data));
    this.service.post('master/master_checklist.php?type=SaveCheckList', JSON.stringify(data)).subscribe(response => {
      if (response['status'] === 'success') {

        alertify.success("Data saved Successfully");
      } else {
        alertify.error("Failed, An error occured, please try again!");
      }
    });
  }

}
