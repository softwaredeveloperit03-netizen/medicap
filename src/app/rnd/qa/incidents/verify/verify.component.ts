import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-verify',
  templateUrl: './verify.component.html',
  styleUrls: ['./verify.component.css']
})
export class VerifyComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
  }

  getInprocessIncidents(){
    this.service.get('qa/incident.php?type=getInprocessIncidents').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  update(value) {
    this.service.get('qa/incident.php?type=verifyIncident&status=' + value + '&id=' + this.selectedDev['id'] + '&remark=' + this.remark).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Incident Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getInprocessIncidents();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
