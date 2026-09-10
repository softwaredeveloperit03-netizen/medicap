import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare var swal: any;

@Component({
  selector: 'app-employee-agreement',
  templateUrl: './employee-agreement.component.html',
  styleUrls: ['./employee-agreement.component.css']
})
export class EmployeeAgreementComponent implements OnInit {
  isNew = false;
  entries;
  selectedEntry;
  isApprover;
  isChecker;
  isView = false;
  file: File;
  file_agreement: File;
  agreement;

  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getAgreementDetails();
  }

  open(url) {
    window.open(url, '_blank');
  }

  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.agreement = this.service.url + this.selectedEntry['file_agreement'];
    this.isView = true;
  }

  onFileChange($event, name) {
    if (name == 'agreement') {
      this.file_agreement = $event.target.files[0];
    }
   }

  saveForm(data) {
    const formData = new FormData();

    formData.append('agreement_title', data.value.agreement_title);
    formData.append('company_name', data.value.company_name);
    formData.append('employee_name', data.value.employee_name);
    formData.append('description', data.value.description);
    formData.append('agreement_date', data.value.agreement_date);
    formData.append('legal_form',data.value.legal_form);
    formData.append('file_agreement', this.file_agreement, this.file_agreement.name);

    this.service.post('admin.php?type=saveEmployeeAgreement', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        this.getAgreementDetails();
        this.isNew = false;
        alert('Saved Successfully');
      } else {
        alert('An error has occurred, please try again');
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

  getAgreementDetails() {
    this.service.get('admin.php?type=getEmployeeAgreement').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/agreement-dashboard']);
  }

}
