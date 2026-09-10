import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-utilities-q',
  templateUrl: './utilities-q.component.html',
  styleUrls: ['./utilities-q.component.css'],
})
export class UtilitiesQComponent implements OnInit {
  results: any;
  constructor(private service: DataAccessService, private router: Router) {}
  utilityList = [];
  isView = false;
  selectedReport = [];
  ngOnInit(): void {
    this.getutility();
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.utilityList[this.utilityList.length] = temp;
    data.resetForm();
  }
  getutility() {
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
    this.isView = true;
  }
  delData(index) {
    this.utilityList.splice(index, 1);
  }
  download() {
    this.service.open('');
  }
  saveUtility(data) {
    let temp = data.value;
    temp['utilityList'] = this.utilityList;
    console.log(temp);
    this.service
      .post(
        'qa/qualification.php?type=saveUtility&id=' + this.selectedReport['id'],
        JSON.stringify(temp)
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
