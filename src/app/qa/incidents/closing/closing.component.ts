import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-closing',
  templateUrl: './closing.component.html',
  styleUrls: ['./closing.component.css']
})
export class ClosingComponent implements OnInit {


  isView = false;
  results;
  selectedFile:File;

  selectedDev = [];
  incident_extension='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('qms/incident.php?type=getCompletedIncident').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }

  add(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('attachment', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('qms/incident.php?type=uploadAttachment&incident_no='+this.selectedDev['incident_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getIncidentDetails();
        alertify.success('Attachment Uploaded Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getIncidentDetails() {
    this.service.get('qms/incident.php?type=getIncidentDetails&incident_no='+this.selectedDev['incident_no']).subscribe((response: any) => {
      this.selectedDev = response;
    });
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/incident/' + link);
  }

  closeIncident() {
    this.service.get('qms/incident.php?type=closeIncident&incident_no='+this.selectedDev['incident_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.getPendingReview();
        alertify.success('Incident Closed Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
