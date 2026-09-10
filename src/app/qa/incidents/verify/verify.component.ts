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
  qa_comment='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
  }

  getInprocessIncidents(){
    this.service.get('qms/incident.php?type=getCheckedIncidents').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  update(value) {
    this.service.get('qms/incident.php?type=incidentVerification&status=' + value+'&incident_no='+this.selectedDev['incident_no']+'&qa_comment='+this.qa_comment).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessIncidents();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
