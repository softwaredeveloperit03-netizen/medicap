import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-distribution',
  templateUrl: './distribution.component.html',
  styleUrls: ['./distribution.component.css']
})
export class DistributionComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSOPImplementation();
  }

  getPendingSOPImplementation() {
    this.service.get('sops.php?type=getPendingSOPDistribution').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  save() {
    this.service.post('sops.php?type=saveDistribution', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('SOP Distributed successfully');
        this.isView = false;
        this.getPendingSOPImplementation();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
