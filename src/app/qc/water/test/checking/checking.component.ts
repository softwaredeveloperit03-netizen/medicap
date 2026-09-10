import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;
  selectedPlan: any = {};
  sample_qty=0;
  qty=0;
  spec_tests;
  checking_remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTestings();
  }

  getPendingTestings() {
    this.service.get('qc/water.php?type=getTestingChecking').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.spec_tests = this.selectedPlan['tests'];
    this.checking_remark = this.selectedPlan['checking_remark'] || '';
 
    this.isView = true;

  }
  updateTesting(status) {
    const remark = encodeURIComponent(this.checking_remark || '');
    this.service.get('qc/water.php?type=checkTesting&status=' + status + '&id=' + this.selectedPlan['id'] + '&remark=' + remark).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Updated Successfully');
        this.isView = false;
        this.getPendingTestings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
