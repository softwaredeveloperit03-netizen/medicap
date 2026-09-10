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

  selectedTesting = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingStabilityTestings();
  }

  getPendingStabilityTestings() {
    this.service.get('stability.php?type=getPendingApprovalStabilityTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTesting = this.results[index];
    this.isView = true;
  }

  updateStabilityTesting(action) {
    this.service.post('stability.php?type=updateStabilityTesting&action=' + action, JSON.stringify(this.selectedTesting)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stability Testing Updated Successfully');
        this.isView = false;
        this.getPendingStabilityTestings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
