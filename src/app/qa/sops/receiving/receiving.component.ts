import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-receiving',
  templateUrl: './receiving.component.html',
  styleUrls: ['./receiving.component.css']
})
export class ReceivingComponent implements OnInit {

  results;
  loading;
  reports;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSOPDeptDistribution();
    this.getSOPDeptDistributionLog();
  }

  getPendingSOPDeptDistribution() {
    this.service.get('sops.php?type=getPendingSOPDeptDistribution').subscribe(response => {
      this.results = response;
    });
  }

  getSOPDeptDistributionLog() {
    this.service.get('sops.php?type=getSOPDeptDistributionLog').subscribe(response => {
      this.reports = response;
    });
  }

  update(status, id) {
    this.service.get('sops.php?type=receiveDistributionSOP&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Updated Successfully');
        this.getPendingSOPDeptDistribution();
        this.getSOPDeptDistributionLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
