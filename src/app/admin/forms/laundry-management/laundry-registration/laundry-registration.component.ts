import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-laundry-registration',
  templateUrl: './laundry-registration.component.html'
})
export class LaundryRegistrationComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getLaundryRegistration();
  }

  viewEntry(index) {
  this.selectedEntry = this.list[index];
  this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getLaundryRegistration() {
    this.service.get('admin.php?type=getLaundryRegistration').subscribe(response => {
      this.list = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('vendor_name', data.value.vendor_name);
      formData.append('contact_person', data.value.contact_person);
      formData.append('address', data.value.address);
      formData.append('phone_no', data.value.phone_no);
      formData.append('gst_no', data.value.gst_no);

      this.service.post('admin.php?type=AddLaundryRegistration', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getLaundryRegistration();
          this.isNew = false;
          alert('Successfully Saved');
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

