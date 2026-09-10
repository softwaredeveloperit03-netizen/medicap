import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  storage_condition;
  isStorage=false;
  storages;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getStorageCondition();
  }

  
  addStorage(){
    if(this.storage_condition=='Add New'){
      this.isStorage=true;
    }else{
      this.isStorage=false;
    }
  }

  
  getStorageCondition(){
    this.service.get('master/storage.php?type=getStorageCondition').subscribe(response => {
      this.storages= response;
    })
  }
  saveStorageCondition(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp=data.value;
    this.service.post('master/storage.php?type=saveStorageCondition', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getStorageCondition();
        this.isStorage=false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }

}
