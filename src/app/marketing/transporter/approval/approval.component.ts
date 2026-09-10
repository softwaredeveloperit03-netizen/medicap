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
  status;
  legal_name;
  selectedResult = [];


  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPending()
  }
  getPending() {
    this.service.get('marketing/transporter.php?type=getPendingTransporters&legal_name='+this.legal_name+'&status='+this.status).subscribe((response: any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('marketing/transporter.php?type=updateTransporter&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] =='success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getPending();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  


}
