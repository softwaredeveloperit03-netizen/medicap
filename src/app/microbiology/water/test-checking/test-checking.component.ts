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
  sample_qty=0;
  qty=0;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTestings();
  }

  getPendingTestings() {
    this.service.get('qc/water.php?type=getActiveTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.sample_qty= +this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty'];
    console.log('qty',+this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty']);
    this.isView = true;
  }

  updateTesting(status) {
    this.service.get('qc/water.php?type=checkTesting&status=' + status + '&id=' + this.selectedPlan['id']).subscribe(response => {
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
