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

  ngOnInit() {
    this.getCheckedDispensingForm();
  }

  getCheckedDispensingForm() {
    this.service.get('store/dispensing.php?type=getCheckedDispensingForm').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.post('store/dispensing.php?type=approveDispensingForm&id=' + this.selectedResult['id'] + '&status=' + status, JSON.stringify(this.selectedResult['dispensing_details'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getCheckedDispensingForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
