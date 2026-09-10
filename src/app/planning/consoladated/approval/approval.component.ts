import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingRequirements();
  }

  getPendingRequirements(){
    this.service.get('planning/consoladated.php?type=getPendingRequirements').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updateRequirement(status){
    this.service.get('planning/consoladated.php?type=updateRequirement&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('data Updated Successfully');
        this.isView = false;
        this.getPendingRequirements();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
