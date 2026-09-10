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

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedBatchPlan();
  }


  getCheckedBatchPlan(){
    this.service.get('production/plan.php?type=getCheckedBatchPlan').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  approveBMRPlan(status) {
    this.service.get('production/plan.php?type=approveBMRPlan&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isNew = false;
        this.getCheckedBatchPlan();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
