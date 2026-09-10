import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  trialList = [];
  opertorList = [];
  selectedOption: string = 'NO';
  results: any;
  selectedReport = [];
  isView=false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getpqchicklist();
  }

  delData(index: number): void {
    this.opertorList.splice(index, 1);
  }

  getpqchicklist() {
    this.service
      .get('/qa/qualification.php?type=getpqchecklist&id=' + this.selectedReport['id'])
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
     this.isView = true;
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.opertorList[this.opertorList.length] = temp;
    console.log(this.opertorList);
    data.reset();
  }
  addData1(data): void {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.trialList.push(data.value);
    data.reset();
  }

  delData1(index: number): void {
    this.trialList.splice(index, 1);
  }

  // savechecklist(data): void {
  //   if (!data.valid) {
  //     alertify.error('All fields are required!');
  //     return;
  //   }
  //   // Handle form submission logic here
  //   console.log('Form data:', data.value);
  // }

  // download(): void {
  //   this.service.open('');
  // }
  savechecklist(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['opertorList'] = this.opertorList;
    temp['trialList'] = this.trialList;

    console.log('Data to be sent to server:', temp);
    this.service
      .post('qa/qualification.php?type=savechecklist&id=' + this.selectedReport['id'], JSON.stringify(temp))
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


