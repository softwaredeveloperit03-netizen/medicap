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
  constructor() {}
  CheckList=[];
  ngOnInit(): void {}
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.CheckList[this.CheckList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.CheckList.splice(index, 1);
  }
  // download() {
  //   this.service.open('');
  // }
}
