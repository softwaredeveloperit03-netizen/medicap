import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-medicalcheckup',
  templateUrl: './medicalcheckup.component.html',
  styleUrls: ['./medicalcheckup.component.css']
})
export class MedicalcheckupComponent implements OnInit {
  employees;
  phisicians;

  entries;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedPhisicians();
    this.getApprovedEmployees();

    this.getMedicalCheckupReport();
  }

  getMedicalCheckupReport() {
    this.service.get('qa.php?type=getMedicalCheckupReport').subscribe(response => {
      this.entries = response;
    });
  }

  getApprovedPhisicians() {
    this.service.get('hrDepartment.php?type=getApprovedPhisicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }

  getApprovedEmployees() {
    this.service.get('qa.php?type=getPendingMedicalEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }

  addMedicalRecord(data) {
    if (!data.valid) {
      alert('All fileds are required');
      return;
    }
    this.service.post('qa.php?type=saveMedicalCheckup', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Medical Check Record sent to Phisician');
        data.resetForm();
        this.getApprovedEmployees();
      } else {
        alert(response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
