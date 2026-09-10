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
  reporptList = [];
  results;
  isview = false;
  selectedReport = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getPerformanceReport();
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.reporptList[this.reporptList.length] = temp;
    data.resetForm();
  }
  getPerformanceReport() {
    this.service
      .get(
        '/qa/qualification.php?type=getpqchecklist&id=' +
          this.selectedReport['id']
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isview = true;
  }

  delData(index) {
    this.reporptList.splice(index, 1);
  }
  download() {
    this.service.open('');
  }

  saveReport(data) {
    let temp = data.value;
    temp['reporptList'] = this.reporptList;
    console.log(temp);
    this.service
      .post(
        'qa/qualification.php?type=saveReport&id=' + this.selectedReport['id'],JSON.stringify(temp)
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
