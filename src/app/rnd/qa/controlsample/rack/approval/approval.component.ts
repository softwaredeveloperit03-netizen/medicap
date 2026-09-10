import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

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

  ngOnInit(): void {
    this.getPendingRacks();
  }

  getPendingRacks(){
    this.service.get('qa/controlsample.php?type=getPendingRacks').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  updateRack(status){
    this.service.get('qa/controlsample.php?type=updateRack&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Rack Updated Successfully!');
        this.isView = false;
        this.getPendingRacks();
      }else{
        alertify.error('An error has occurred, please try again!');
      }
    });
  }

}
