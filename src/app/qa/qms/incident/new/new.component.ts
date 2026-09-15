import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  saveBtn = true;
  Name_of_Department;

  incident_relateds = [
    { origin: 'Procedure', value: false },
    { origin: 'Process', value: false },
    { origin: 'Equipment', value: false },
    { origin: 'Standard', value: false },
    { origin: 'Batch Size', value: false },
    { origin: 'Others', value: false },
  ];

  classification_inr = [
    { origin: 'Critical', value: false },
    { origin: 'Major', value: false },
    { origin: 'Minor', value: false },
  ];

  potential_impact = [
    { origin: 'Quality', value: false },
    { origin: 'Yield', value: false },
    { origin: 'GMPs', value: false },
    { origin: 'Manufacturing Process', value: false },
    { origin: 'Other', value: false },
  ];

  selectedIncidentRelated = '';
  selectedClassificationInr = '';
  selectedPotentialImpact = '';

  selectedFile: File;
  selectedFile1: File;
  selectedFile2: File;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.Name_of_Department = localStorage.getItem('department');
  }

  onFileChanged(event): void {
    this.selectedFile = event.target.files[0];
  }

  onFileChanged1(event): void {
    this.selectedFile1 = event.target.files[0];
  }

  onFileChanged2(event): void {
    this.selectedFile2 = event.target.files[0];
  }

  save(data): void {
    if (!data.valid) {
      alertify.error('All fields are required!');
      this.saveBtn = true;
      return;
    }

    this.saveBtn = false;
    const temp = data.value;
    const uploadData = new FormData();

    for (const key in temp) {
      if (Object.prototype.hasOwnProperty.call(temp, key)) {
        uploadData.append(key, temp[key] == null ? '' : temp[key]);
      }
    }

    if (this.selectedFile) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
    if (this.selectedFile1) {
      uploadData.append('document1', this.selectedFile1, this.selectedFile1.name);
    }
    if (this.selectedFile2) {
      uploadData.append('document2', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qms/newIncident.php?type=saveIncident', uploadData).subscribe(
      (response) => {
        this.saveBtn = true;
        if (response['status'] === 'success') {
          alertify.success('data save Successfuly');
          data.resetForm();
          this.selectedFile = undefined;
          this.selectedFile1 = undefined;
          this.selectedFile2 = undefined;
          this.selectedIncidentRelated = '';
          this.selectedClassificationInr = '';
          this.selectedPotentialImpact = '';
          this.Name_of_Department = localStorage.getItem('department');
          this.router.navigate(['/qa/qms/incident']);
        } else {
          alertify.error(response['status'] || 'Error Occured');
        }
      },
      () => {
        this.saveBtn = true;
        alertify.error('Error Occured');
      }
    );
  }
}
