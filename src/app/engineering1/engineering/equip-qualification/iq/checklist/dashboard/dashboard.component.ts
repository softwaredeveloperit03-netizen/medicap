import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  checkList=[];
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  addData(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.checkList[this.checkList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.checkList.splice(index, 1);
  }

  download(){
    this.service.open('');
   }
}
