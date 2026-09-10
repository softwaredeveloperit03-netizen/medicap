import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isNew = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingBatchPlan();
  }

  getPendingBatchPlan(){
    this.service.get('production/plan.php?type=getPendingBatchPlan').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  checkBMRPlan(status) {
    this.service.get('production/plan.php?type=checkBMRPlan&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isNew = false;
        this.getPendingBatchPlan();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
