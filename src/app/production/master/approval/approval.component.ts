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
    this.getCheckedBatchFormula();
  }

  getCheckedBatchFormula() {
    this.service.get('production/master.php?type=getCheckedBatchFormula').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  update(status) {
    this.service.get('production/master.php?type=approveBatchFormula&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isNew = false;
        this.getCheckedBatchFormula();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
