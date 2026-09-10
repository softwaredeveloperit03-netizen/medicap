import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-pending',
  templateUrl: './pending.component.html',
  styleUrls: ['./pending.component.css']
})
export class PendingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  list = [];
  trainings = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingEmployees();
  }

  getPendingEmployees() {
    this.service.get('hr/responsibility.php?type=getPendingEmployees').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    if (temp['accountability'] == '') {
      temp['accountability'] = false;
    }
    this.list[this.list.length] = data.value;
    data.resetForm();
  }

  del(index) {
    this.list.splice(index, 1);
  }

  save() {

    let accountability = [];
    let standards = this.selectedResult['responsibilities'];
    for (let i = 0; i < standards.length; i++) {
      let standard = standards[i];
      if (standard['accountability'] == true) {
        accountability[accountability.length] = standard;
      }
    }

    for (let i = 0; i < this.list.length; i++) {
      let standard = this.list[i];
      if (standard['accountability'] == true) {
        accountability[accountability.length] = standard;
      }
    }

    let temp = {};
    temp['standard'] = this.selectedResult['responsibilities'];
    temp['additional'] = this.list;
    temp['accountability'] = accountability;
    temp['trainings'] = this.trainings;
    this.service.post('hr/responsibility.php?type=saveResponsibilities&emp_code=' + this.selectedResult['emp_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        this.list = [];
        this.trainings = [];
        this.isView = false;
        this.getPendingEmployees();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  addTraining(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.trainings[this.trainings.length] = data.value;
    data.resetForm();
  }

  delTraining(index) {
    this.trainings.splice(index, 1);
  }

}
