import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-discripancy',
  templateUrl: './discripancy.component.html'
})
export class DiscripancyComponent implements OnInit {
  
  isView = false;
  results = [];

  selectedResult = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getCheckingBatchRelease();
  }
  getCheckingBatchRelease() {
    this.service.get('batch-release.php?type=getCheckingBatchRelease').subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('batch-release.php?type=updateBatchRelease&status=' + status + '&id=' + this.selectedResult['id'] + '&product_code=' + this.selectedResult['product_code'] + '&batch_no=' + this.selectedResult['batch_no']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Batch Release record updated successfully');
        this.isView = false;
        this.getCheckingBatchRelease();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}