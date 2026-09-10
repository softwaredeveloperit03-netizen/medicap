import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;

@Component({
  selector: 'app-stereo-issuance',
  templateUrl: './stereo-issuance.component.html',
  styleUrls: ['./stereo-issuance.component.css']
})
export class StereoIssuanceComponent implements OnInit {

  entries;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getStereoIssue();
  }

  getStereoIssue() {
    this.service.get('vendor.php?type=getStereoIssue').subscribe(response => {
      this.entries = response;
    });
  }

}
