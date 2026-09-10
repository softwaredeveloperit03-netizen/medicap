import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-test-checking',
  templateUrl: './test-checking.component.html',
  styleUrls: ['./test-checking.component.css']
})
export class TestCheckingComponent implements OnInit {

  isView = false;
  results;
  selectedPlan = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTestings();
  }

  getPendingTestings() {
    this.service.get('qc/water.php?type=getPendingTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.isView = true;
  }

  updateTesting(status) {
    this.service.get('qc/water.php?type=updateTesting&status=' + status + '&id=' + this.selectedPlan['sampling_no']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getPendingTestings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
