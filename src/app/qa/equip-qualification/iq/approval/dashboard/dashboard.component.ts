import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  abbreList=[];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }
  addData(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.abbreList[this.abbreList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.abbreList.splice(index, 1);
  }

  download(){
    this.service.open('');
   }
}
