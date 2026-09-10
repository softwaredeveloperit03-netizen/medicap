import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormGroup, FormBuilder, Validators, RequiredValidator } from '@angular/forms';

@Component({
  selector: 'app-transport-management',
  templateUrl: './transport-management.component.html'
})
export class TransportManagementComponent implements OnInit {

  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  list;
  reactiveForm : FormGroup ;
  constructor(private service: DataAccessService, private router: Router, private fb:FormBuilder) {
    this.reactiveForm = this.fb.group({
      vendor_company : ['', [Validators.required]],
      person : ['', [Validators.required]],
      address : ['',[Validators.required]],
      pincode: ['',[Validators.required]],
      phone_no: ['',[Validators.required]],
      email: ['',[Validators.required]],
      isgoods: [false, Validators.requiredTrue],
      ispassenger: [false, Validators.requiredTrue],

    });
  }

  ngOnInit() {
    this.getTransportorslist();
  }

  viewEntry(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank')
  }

  getTransportorslist() {
    this.service.get('admin.php?type=getTransportorslist').subscribe(response => {
      this.list = response;
    });
  }

   saveForm() {
      const formData = new FormData();
      formData.append('vendor_company', this.reactiveForm.value.vendor_company);
      formData.append('person', this.reactiveForm.value.person);
      formData.append('address', this.reactiveForm.value.address);
      formData.append('pincode', this.reactiveForm.value.pincode);
      formData.append('phone_no', this.reactiveForm.value.phone_no);
      formData.append('email', this.reactiveForm.value.email);
      formData.append('isgoods', this.reactiveForm.value.isgoods);
      formData.append('ispassenger', this.reactiveForm.value.ispassenger);
      this.service.post('admin.php?type=AddTransportor', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.reactiveForm.reset();
          this.getTransportorslist();
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
    this.router.navigate(['/transport-dashboard']);
  }

}

