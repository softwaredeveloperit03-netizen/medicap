import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-designation-master',
  templateUrl: './designation-master.component.html',
  styleUrls: ['./designation-master.component.css']
})
export class DesignationMasterComponent implements OnInit {
  isNew = false;

  designations;
  departments: any[] = [];

  list = [];
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getDesignations();
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  getDesignations() {
    this.service.get('hr/designation.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  addDesignation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['responsibilities'] = this.list;
    this.service.post('hr/designation.php?type=saveDesignation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Designation Added Successfully');
        this.isNew = false;
        data.resetForm();
        this.getDesignations();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.list[this.list.length] = data.value;
    data.resetForm();
  }

  del(index) {
    this.list.splice(index, 1);
  }
}
