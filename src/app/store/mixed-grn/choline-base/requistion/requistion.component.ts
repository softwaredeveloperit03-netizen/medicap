import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-requistion',
  templateUrl: './requistion.component.html',
  styleUrls: ['./requistion.component.css']
})
export class RequistionComponent implements OnInit {
  isView = false;
  results;
  remark='';
  selectedResult=[];
  constructor(private service:DataAccessService) { }

 
  ngOnInit() {
    this.getDispensingActivities();
  }

  getDispensingActivities(){
    this.service.get('store/cholinebase.php?type=getPendingRequisitions').subscribe(response => {
      this.results = response;
    });
  }

  update(status, id) {
    this.service.get('store/cholinebase.php?type=saveRequest&id=' + id + '&status=' + status).subscribe(response => {
      if (response['status']  == 'success') {
        alertify.success(response['msg']);
        this.getDispensingActivities();
      } else {
        alertify.error(response['msg']);
      }
    });
  }
}
