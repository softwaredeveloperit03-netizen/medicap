import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingMaintenances();
  }

  getPendingMaintenances(){
    this.service.get('engineering/maintenance.php?type=getPendingMaintenances').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status){
    this.service.get('engineering/maintenance.php?type=checkMaintenance&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data updated Successfully!');
        this.isView = false;
        this.getPendingMaintenances();
      }else{
        alert('Failed an error occured,please try again!');
      }
    });
  }

}
