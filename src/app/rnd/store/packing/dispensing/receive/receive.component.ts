import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedDispensingForm();
  }

  getApprovedDispensingForm() {
    this.service.get('production/solution.php?type=getApprovedDispensingForm').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.post('production/solution.php?type=receiveDispensingForm&id=' + this.selectedResult['id'] + '&status=' + status, JSON.stringify(this.selectedResult['dispensing_details'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getApprovedDispensingForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
