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
  selectedReport = [];

  comment_by_approver = '';

  isApprover;
  isChecker;
  constructor(private service: DataAccessService) { }


  ngOnInit(): void {
    this.getCheckedIncident();
  }

  getCheckedIncident() {
    this.service.get('qa/incident.php?type=getCheckedIncident').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }


  updateIncident(data,status) {
    if (!data.valid) {
      alertify.error('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    temp['inc_no'] = this.selectedReport['inc_no'];
    temp['status'] = status;
    temp['department'] = this.selectedReport['department'];
    temp['related_to'] = this.selectedReport['related_to'];
    this.service.post('qa/incident.php?type=saveQAApproval&id=' + this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success("Incident Updated Successfully");
        this.isView = false;
        this.getCheckedIncident();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
