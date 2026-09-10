import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-operation-input',
  templateUrl: './operation-input.component.html',
  styleUrls: ['./operation-input.component.css'],
})
export class OperationInputComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}
  reportList = [];
  isView = false;
  results;
  selectedReport = [];
  ngOnInit(): void {
    this.getoqchicklist();
  }

  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.reportList[this.reportList.length] = temp;
    data.resetForm();
  }
  getoqchicklist() {
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
    this.reportList.splice(index, 1);
  }
  // download() {
  //   this.service.open('');
  // }
  saveOperation(data) {
    let temp = data.value;
    temp['reportList'] = this.reportList;
    console.log(temp);
    this.service
      .post('qa/qualification.php?type=saveOperation&id=' + this.selectedReport['id'], JSON.stringify(temp))
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
