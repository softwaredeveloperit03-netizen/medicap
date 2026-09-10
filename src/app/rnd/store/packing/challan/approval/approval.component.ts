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

  selectedResult = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingChallans();
  }

  getPendingChallans(){
    this.service.get('store/packing.php?type=getPendingChallans').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  updateChallan(status) {
    this.service.get('store/packing.php?type=updateChallan&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingChallans();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
