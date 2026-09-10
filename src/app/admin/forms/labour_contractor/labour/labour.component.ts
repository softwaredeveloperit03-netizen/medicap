import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-labour',
  templateUrl: './labour.component.html',
  styleUrls: ['./labour.component.css']
})
export class LabourComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  getLabourList;
  newUnit = false;
  selectedFile: File;
  selectedFile1: File;
  isUploadAadharcard = 0;
  isUploadPhoto = 0;
  isApprover;
  isChecker;
  isUser;
  birthdate;
  list;

  ngOnInit() {
    this.getLabourDetails();
    this.getConractor();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

     if (localStorage.getItem('checker') === 'true') {
      this.isChecker= true;
     } else {
      this.isChecker= false;
     }

     if (localStorage.getItem('user') === 'true') {
      this.isUser= true;
     } else {
      this.isUser= false;
     }
}

getConractor() {
    this.service.get('admin.php?type=getApprovedContractor').subscribe(response=> {
    this.list =response;
    });
}

  getLabourDetails() {
    this.service.get('admin.php?type=getLabourDetails')
    .subscribe(response => {
      this.getLabourList = response;
    });
  }
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUploadAadharcard = 1;
  }

  onFileChanged1(event) {
    this.selectedFile1 = event.target.files[0];
    this.isUploadPhoto = 1;
  }

  Approve(value) {
    this.service.get('admin.php?type=updateLabour&id=' + value).subscribe(response => {
      if (response['status'] === 'success') {
        alert('Approved Successfully');
        this.getLabourDetails();
      }
    });
  }

  saveLabour(formData) {
    if (this.birthdate < 18) {
      alert('Under 18 Age Labour Not Acceptable!');
    } else {
      const uploadData = new FormData();
      if (this.isUploadAadharcard === 1) {
        uploadData.append('aadhar_card', this.selectedFile, this.selectedFile.name);
      }
      if (this.isUploadPhoto === 1) {
        uploadData.append('photo', this.selectedFile1, this.selectedFile1.name);
      }

    uploadData.append('labour_name', formData.value.labour_name);
    uploadData.append('dob', formData.value.dob);
    uploadData.append('address', formData.value.address);
    uploadData.append('contractor_name', formData.value.contractor_name);
    uploadData.append('category', formData.value.category);
    uploadData.append('daily_wages', formData.value.daily_wages);
    uploadData.append('gender', formData.value.gender);
    this.service.post('admin.php?type=saveLabours', uploadData)
      .subscribe(response => {
        if (response['status'] === 'success') {
          alert('Successfully Send For Approval');
          this.getLabourDetails();
          this.newUnit = false;
          formData.reset();
        } else {
          alert('Please Try Again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('error');
        }
      });
    }
  }

  calculateAge(birthday) { // birthday is a date
    birthday = new Date(birthday);
    if(!isNaN(birthday)) {
      var ageDifMs = Date.now() - birthday.getTime();
      var ageDate = new Date(ageDifMs); // miliseconds from epoch
      if (Math.abs(ageDate.getUTCFullYear() - 1970) >= 18) {
      } else {
        alert('Under 18 Age Labour Not Acceptable!');
      }
      this.birthdate = Math.abs(ageDate.getUTCFullYear() - 1970);
    }
}

}
