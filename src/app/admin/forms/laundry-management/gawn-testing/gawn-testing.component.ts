import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-gawn-testing',
  templateUrl: './gawn-testing.component.html'
})
export class GawnTestingComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;
  file: File;
  file_certificate: File;
  certificate;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getTestingData();
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.certificate = this.service.url + this.selectedEntry['file_certificate'];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getTestingData() {
    this.service.get('admin.php?type=getTestingData').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event, name) {
    if (name == 'certificate') {
      this.file_certificate = $event.target.files[0];
    }
   }

   saveForm(data) {
      const formData = new FormData();
      formData.append('testing_date', data.value.testing_date);
      formData.append('gown_type', data.value.gown_type);
      formData.append('lab_name', data.value.lab_name);
      formData.append('address', data.value.address);
      formData.append('contact_no', data.value.contact_no);
      formData.append('file_certificate', this.file_certificate, this.file_certificate.name);

      this.service.post('admin.php?type=AddGownTestingRecord', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getTestingData();
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

  close() {
    this.router.navigate(['/laundry-dashboard']);
  }

}

