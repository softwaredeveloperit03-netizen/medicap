import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-standard',
  templateUrl: './standard.component.html',
  styleUrls: ['./standard.component.css']
})
export class StandardComponent implements OnInit {

  isNew = false;
  isEdit = false;

  designations;

  list = [];

  selectedResult = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getDesignations();
  }

  getDesignations() {
    this.service.get('hr/responsibility.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  view(index) {
    this.selectedResult = this.designations[index];
    this.isEdit = true;
  }

  addDesignation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['responsibilities'] = this.list;
    this.service.post('hrDepartment.php?type=newDesignation', JSON.stringify(temp)).subscribe(response => {
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

  addEdit(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let responsibilities = this.selectedResult['responsibilities'];
    responsibilities[responsibilities.length] = data.value;
    this.selectedResult['responsibilities'] = responsibilities;
    data.resetForm();
  }

  delEdit(index) {
    let responsibilities = this.selectedResult['responsibilities'];
    responsibilities.splice(index, 1);
    this.selectedResult['responsibilities'] = responsibilities;
  }

  update() {
    this.service.post('hrDepartment.php?type=updateDesignation', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isEdit = false;
        this.getDesignations();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
