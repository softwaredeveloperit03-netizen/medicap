import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  reference = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSOPs();
  }

  getCheckedSOPs() {
    this.service.get('sops.php?type=getCheckedSOPs').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.reference = this.selectedResult['reference']
    this.isView = true;
  }

  update(status) {
    this.service.get('sops.php?type=approveCreatedSOP&status=' + status + '&sop_no=' + this.selectedResult['sop_no']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated successfully');
        this.isView = false;
        this.getCheckedSOPs();
      } else {
        alert('Failed: AN error occured');
      }
    });
  }

}
