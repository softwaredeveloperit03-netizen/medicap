import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-clearance-approval',
  templateUrl: './clearance-approval.component.html',
  styleUrls: ['./clearance-approval.component.css']
})
export class ClearanceApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingLineClearance();
  }

  getPendingLineClearance() {
    this.service.get('store/dispensing.php?type=getPendingLineClearance').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  approveLineClearance() {
    let clearance = this.selectedResult['line_clearance'];
    this.service.post('store/dispensing.php?type=approveLineClearance&id=' + this.selectedResult['id'], JSON.stringify(clearance)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        this.isView = false;
        this.getPendingLineClearance();
      }
    });
  }

}
