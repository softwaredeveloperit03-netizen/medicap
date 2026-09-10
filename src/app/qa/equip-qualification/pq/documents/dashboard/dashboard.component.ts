import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  materialList = [];
  isView = false;
  results;
  selectedReport = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
 this.getMaterialpq();

  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }
  getMaterialpq() {
    this.service
      .get('/qa/qualification.php?type=getpqchecklist&id=' +
          this.selectedReport['id']
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  delData(index) {
    this.materialList.splice(index, 1);
  }
  download() {
    this.service.open('/qa/qualification.php?type=downloadpqchecklist');
  }
  saveDoc(data) {
    let temp = data.value;
    temp['materialList'] = this.materialList;
    console.log(temp);
    this.service
      .post(
        'qa/qualification.php?type=saveDoc&id=' + this.selectedReport['id'], JSON.stringify(temp)
      )
      .subscribe((response) => {
        console.log('Response from server:', response);
        if (response['status'] == 'success') {
          alert('saved successfully');
          data.resetForm();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
}
