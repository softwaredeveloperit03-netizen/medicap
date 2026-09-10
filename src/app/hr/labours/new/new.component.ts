import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

const POSTAL_CA_PATTERN = /^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$/;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  dob: string;
  maxDate: string;
  front_side: File;
  back_side: File;
  photo: File;
  list;

  birthdate;
  street_address = '';
  city = '';
  province = '';
  postal_code = '';
  country = 'Canada';

  readonly postalCaHtmlPattern = '^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$';

  canadianProvinces: string[] = [
    'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
    'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
    'Quebec', 'Saskatchewan', 'Yukon'
  ];

  constructor(private service: DataAccessService, private router: Router) {
    const today = new Date();
    const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    this.maxDate = eighteenYearsAgo.toISOString().slice(0, 10);
  }

  checkAge() {
    const selectedDate = new Date(this.dob);
    const eighteenYearsAgo = new Date();
    eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
    if (selectedDate > eighteenYearsAgo) {
      alert('Date of birth must be at least 18 years ago.');
      this.dob = null;
      this.birthdate = null;
      return;
    }
    this.calculateAge(this.dob);
  }

  ngOnInit() {
    this.getConractor();
  }

  formatPostalInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (!el?.value) {
      return;
    }
    const v = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    let formatted = el.value;
    if (v.length >= 6) {
      formatted = `${v.slice(0, 3)} ${v.slice(3, 6)}`.trim();
    } else if (v.length > 3) {
      formatted = `${v.slice(0, 3)} ${v.slice(3)}`.trim();
    } else {
      formatted = v;
    }
    this.postal_code = formatted;
    if (el.value !== formatted) {
      el.value = formatted;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  onFileChanged1(event) {
    if (event.target.files.length !== 0) {
      this.front_side = event.target.files[0];
    }
  }

  onFileChanged2(event) {
    if (event.target.files.length !== 0) {
      this.back_side = event.target.files[0];
    }
  }

  onFileChanged3(event) {
    if (event.target.files.length !== 0) {
      this.photo = event.target.files[0];
    }
  }

  saveLabour(data) {
    if (!data.valid) {
      alertify.error('All required fields must be filled');
      return;
    }

    if (this.birthdate != null && this.birthdate < 18) {
      alertify.error('Labour must be at least 18 years old');
      return;
    }

    if (!POSTAL_CA_PATTERN.test((this.postal_code || '').trim())) {
      alertify.error('Enter a valid Canadian postal code (e.g. K1A 0A6)');
      return;
    }

    const formData = new FormData();
    const temp = { ...data.value };

    temp['address'] = [
      temp['street_address'],
      temp['city'],
      temp['province'],
      temp['postal_code'],
      temp['country'] || 'Canada'
    ].filter((part) => part != null && String(part).trim() !== '').join(', ');

    delete temp['street_address'];
    delete temp['city'];
    delete temp['province'];
    delete temp['postal_code'];
    delete temp['country'];

    for (const key in temp) {
      formData.append(key, temp[key]);
    }

    if (this.front_side !== undefined) {
      formData.append('front_side', this.front_side, this.front_side.name);
    } else {
      alertify.error('Government ID upload is required');
      return;
    }

    if (this.photo !== undefined) {
      formData.append('photo', this.photo, this.photo.name);
    } else {
      alertify.error('Photo upload is required');
      return;
    }

    this.service.post('hr/labour.php?type=saveLabour', formData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Labour registered successfully');
        this.router.navigate(['/hr/labours']);
        data.resetForm();
      } else {
        alertify.error('Please try again');
      }
    });
  }

  calculateAge(birthday) {
    birthday = new Date(birthday);
    if (!isNaN(birthday)) {
      const ageDifMs = Date.now() - birthday.getTime();
      const ageDate = new Date(ageDifMs);
      if (Math.abs(ageDate.getUTCFullYear() - 1970) >= 18) {
      } else {
        alertify.error('Labour must be at least 18 years old');
      }
      this.birthdate = Math.abs(ageDate.getUTCFullYear() - 1970);
    }
  }

  getConractor() {
    this.service.get('admin.php?type=getApprovedContractor').subscribe(response => {
      this.list = response;
    });
  }
}
