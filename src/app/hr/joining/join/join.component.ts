import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-join',
  templateUrl: './join.component.html',
  styleUrls: ['./join.component.css']
})
export class JoinComponent implements OnInit {

  isSpecialTerm = false;
  employees: any;
  candidates;
  experience = 0;
  selectedFile: File;
  isUploadResume = 0;
  isShow;
  selectedFile2: File;
  isUploadPhoto;

  permanant_state = '';
  permanant_district = '';
  permanant_taluka = '';
  address_permanent = '';

  temporary_state = '';
  temporary_district = '';
  temporary_taluka = '';
  address_temporary = '';

  selectedCandidate;
  isNewForm = false;
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getCandidates();
  }

  getCandidates() {
    this.service.get('hr/candidate.php?type=getPendingJoiningCandidates').subscribe(response => {
      this.employees = response;
    });
  }

  saveEmployee(formData) {
    if (!formData.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = formData.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      // Use `key` and `value`
      uploadData.append(key, value);
    }

    uploadData.append("candidate_id", this.selectedCandidate['id']);
    uploadData.append("employee_name", this.selectedCandidate['candidate_name']);
    uploadData.append("department", this.selectedCandidate['finaldepartment']);
    uploadData.append("designation", this.selectedCandidate['finaldesignation']);
    uploadData.append("gender", this.selectedCandidate['gender']);
    uploadData.append("qualification", this.selectedCandidate['qualification']);
    uploadData.append("mobile_no", this.selectedCandidate['mobile_no']);
    uploadData.append("email_id", this.selectedCandidate['email_id']);
    uploadData.append("reporting_to", "");

    if (this.selectedFile2 !== undefined) {
      uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append('resume', this.selectedFile, this.selectedFile.name);
    }
    this.service.post('hr/candidate.php?type=saveEmployee', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        formData.resetForm();
        alertify.success('Record Added Successfully');
       this.router.navigate(['/joining']);
      } else {
        alertify.error(response['status']);
      }
    });
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUploadResume = 1;
  }

  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
    this.isUploadPhoto = 1;
  }

  checkSelection(value) {
    if (value === 'select on Special Term') {
      this.isSpecialTerm = true;
    } else {
      this.isSpecialTerm = false;
    }
  }

  selectCandidate(index) {
    this.selectedCandidate = this.employees[index];
    this.isNewForm = true;
  }

  checkAddress(value) {
    if (value) {
      this.temporary_state = this.permanant_state;
      this.temporary_district = this.permanant_district;
      this.temporary_taluka = this.permanant_taluka;
      this.address_temporary = this.address_permanent;
    } else {
      this.temporary_state = '';
      this.temporary_district = '';
      this.temporary_taluka = '';
      this.address_temporary = '';
    }
  }
}
