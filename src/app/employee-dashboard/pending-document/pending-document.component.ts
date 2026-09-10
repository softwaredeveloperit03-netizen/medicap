import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-pending-document',
  templateUrl: './pending-document.component.html',
  styleUrls: ['./pending-document.component.css']
})
export class PendingDocumentComponent implements OnInit {

  
  isView = false;
  results;
  selectedResult: [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingQuotations();
  }

  getPendingQuotations() {
    this.service.get('purchase/quotation.php?type=getPendingQuotations').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  updateQuotation(status) {
    this.service.get('purchase/quotation.php?type=updateQuotation&status=' + status +'&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingQuotations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  
  }

}
