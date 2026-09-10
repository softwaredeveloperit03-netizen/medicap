import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  descriptionList = [];
  componentList = [];
  accessoriesList= [];
  item = 0;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  addData(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.descriptionList[this.descriptionList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.descriptionList.splice(index, 1);
  }

  addCom(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.componentList[this.componentList.length] = temp;
    data.resetForm();
  }

  delCom(index) {
    this.descriptionList.splice(index, 1);
  }

  addAcc(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.accessoriesList[this.accessoriesList.length] = temp;
    data.resetForm();
  }
  delAcc(index) {
    this.accessoriesList.splice(index, 1);
  }
}

