import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isNew = false;
  results;
  stages=[];

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPlan();
  }


  getPlan(){
    this.service.get('planning/micro.php?type=getPendingPlans').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.stages=JSON.parse(this.selectedResult['stages']);
    this.isNew = true;
  }

  approve(status) {
    this.service.get('planning/micro.php?type=updatePlan&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isNew = false;
        this.getPlan();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
