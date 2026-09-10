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
    this.getInprocessReceivings();
  }

  getInprocessReceivings(){
    this.service.get('store/packing.php?type=getInprocessReceivings').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  updateReceiving(status) {
    this.service.get('store/packing.php?type=updateReceiving&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/coa/' + link);
  }
  

}
