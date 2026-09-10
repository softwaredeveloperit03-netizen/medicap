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

  release_status = 'Approved';
  observation: string;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAQAApproval();
  }

  getPendingAQAApproval() {
    this.service.get('ipqc/finish.php?type=getPendingAQAApproval').subscribe(response => {
      this.results = response;
      console.log(this.results);
    });
  }
  specification=[];
  view(index) {
    this.selectedResult = this.results[index];
    this.specification=this.selectedResult['tests']
    this.isView = true;
  }

  save() { 
    this.service.post('ipqc/finish.php?type=saveReleaseStatus&id=' + this.selectedResult['id'] +'&status=' + this.release_status+'&specification_no='+this.selectedResult['specification_no']+'&release_status='+this.selectedResult['release_status']+'&observation='+this.selectedResult['observation'], JSON.stringify(this.selectedResult['tests'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(response['msg']);
        this.isView = false;
        this.getPendingAQAApproval();
      } else {
        alertify.error(response['msg']);
      }
    });
  }

}
