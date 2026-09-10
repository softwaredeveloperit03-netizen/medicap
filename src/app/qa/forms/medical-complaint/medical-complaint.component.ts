import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-medical-complaint',
  templateUrl: './medical-complaint.component.html',
  styleUrls: ['./medical-complaint.component.css']
})
export class MedicalComplaintComponent implements OnInit {

  complaints;
  isHomepage = true;
  isInvestigationForm = false;
  isComplaintRegister = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMedicalComplaints();
  }

  getMedicalComplaints() {
    this.service.get('qaDepartment.php?type=getMedicalComplaints').subscribe(response => {
      this.complaints = response;
    });
  }

  savemarketComplaint(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('qaDepartment.php?type=savemedicalComplaint', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == "success") {
        data.resetForm();
        alert('Medical complaint saved successfully');
        this.getMedicalComplaints();
        this.closeForm();
      } else {
        alert('An error occured, please try again');
      }
    });
  }

  checkForm(value) {
    if (value === 'register') {
      this.isHomepage = false;
      this.isComplaintRegister = true;
    } else {
      this.isHomepage = false;
      this.isInvestigationForm = true;
    }
  }

  closeForm() {
    this.isInvestigationForm = false;
    this.isComplaintRegister = false;
    this.isHomepage = true;
  }

}
