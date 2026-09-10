import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  department_name = localStorage.getItem('department');
  isView = false;
  results;
  capas = [];
  selectedFile: File;
  isUpload = 0;
  selectedFile1: File;
  isUpload1 = 0;
  selectedFile2: File;
  isUpload2 = 0;
  selectedReport = [];
  remark = '';
  departments;
   incident_status = '';

  plant_id: any;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getPendingIncidents();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  categories = [
    { origin: 'Customer Complaint' },
    { origin: 'Internal Audits' },
    { origin: 'Trending the Data' },
    { origin: 'Out of Specification' },
    { origin: 'Regulatory Inspection' },
    { origin: 'Risk Assessment' },
    { origin: 'Deviation/Incident' },
    { origin: 'Management Review' },
    { origin: 'Staff Observation' },
    { origin: 'Process Performance Monitoring' },
    { origin: 'Other' },
  ];

  selectedCategory: string = ''; // Stores the selected value



  getPendingIncidents() {
    this.service
      .get('qms/incident.php?type=getPendingIncidents')
      .subscribe((response) => {
        this.results = response;
      });
  }

  viewIncident(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }



  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
  temp['department'] = localStorage.getItem('department');
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
      console.log(uploadData);
    }
    if (this.selectedFile1 !== undefined) {
      uploadData.append(
        'document1',
        this.selectedFile1,
        this.selectedFile1.name
      );
      console.log(uploadData);
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append(
        'document2',
        this.selectedFile2,
        this.selectedFile2.name
      );
      console.log(uploadData);
    }

    // uploadData.append('plan',JSON.stringify(this.capas));
    this.service
      .post('/qms/capa2.php?type=saveMehaCAPA', uploadData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          data.resetForm();
          this.router.navigate(['qa/qms/capa/dashboard']);
          alertify.success('Successfully Saved');
        } else {
          alertify.error('An error has occurred, please try again');
        }
      });
  }
}
