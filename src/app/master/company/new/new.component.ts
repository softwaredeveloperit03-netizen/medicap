import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { buildCompanyCountryOptions } from '../merge-countries';

declare let alertify: { success: (m: string) => void; error: (m: string) => void };

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  countries: { country?: string }[] = [];
  branches: unknown[] = [];
  isDiv = false;

  country = '';
  present_state = '';

  readonly indianStates: string[] = [
    'Andhra Pradesh',
    'Andaman and Nicobar Islands',
    'Arunachal Pradesh',
    'Assam',
    'Bihar',
    'Chandigarh',
    'Chhattisgarh',
    'Dadar and Nagar Haveli',
    'Daman and Diu',
    'Delhi',
    'Lakshadweep',
    'Puducherry',
    'Goa',
    'Gujarat',
    'Haryana',
    'Himachal Pradesh',
    'Jammu and Kashmir',
    'Jharkhand',
    'Karnataka',
    'Kerala',
    'Madhya Pradesh',
    'Maharashtra',
    'Manipur',
    'Meghalaya',
    'Mizoram',
    'Nagaland',
    'Odisha',
    'Punjab',
    'Rajasthan',
    'Sikkim',
    'Tamil Nadu',
    'Telangana',
    'Tripura',
    'Uttar Pradesh',
    'Uttarakhand',
    'West Bengal',
  ];

  constructor(private service: DataAccessService, private router: Router) {}

  get isIndia(): boolean {
    return (this.country || '').toString().trim().toLowerCase() === 'india';
  }

  ngOnInit(): void {
    this.getCountries();
  }

  getCountries(): void {
    this.service.get('master/country.php?type=getCountries').subscribe((response) => {
      const api = Array.isArray(response) ? (response as { country?: string }[]) : [];
      this.countries = buildCompanyCountryOptions(api);
    });
  }

  onCountryChange(value: string): void {
    this.present_state = '';
  }

  submit(data: NgForm): void {
    if (!data.valid) {
      alertify.error('Please fill all required fields.');
      return;
    }
    const temp: Record<string, unknown> = { ...data.value };
    temp['branches'] = this.branches;
    temp['plant_id'] = localStorage.getItem('plant_id') || '';
    temp['gst_registered'] = '';
    temp['gst_no'] = '';
    temp['c_state_code'] = '';
    this.service
      .post('master/company.php?type=saveCompanyDetails', JSON.stringify(temp))
      .subscribe((response: { status?: string }) => {
        if (response['status'] === 'success') {
          alertify.success('Record inserted successfully');
          data.resetForm();
          this.country = '';
          this.present_state = '';
          this.branches = [];
          this.router.navigate(['/master/company']);
        } else {
          alertify.error(response['status'] || 'Please try again');
        }
      });
  }
}
