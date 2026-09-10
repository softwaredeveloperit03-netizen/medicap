import { Component, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

import {
  canadianPhoneRequiredValidators,
  isValidCanadianPhone,
} from 'src/app/shared/validators/canadian-phone.validator';

const PINCODE_IN_PATTERN = /^[0-9]{6}$/;
const POSTAL_CA_PATTERN = /^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$/;
const POSTAL_INTL_PATTERN = /^[\s\S]{3,12}$/;

@Component({
  selector: 'app-vendor-for-editing',
  templateUrl: './for-editing.component.html',
  styleUrls: ['./for-editing.component.css'],
})
export class ForEditingComponent implements OnInit {
  vendorForm: FormGroup;
  vendorFor = 'Domestic';
  other_contact: Array<{ contactType: string; cPersonName: string; cPersonMob: string; cPersonEmail: string }> = [];
  selectedCurrencies: Array<{ code: string }> = [{ code: 'CAD' }];
  currency = '';
  contactType = '';
  cPersonName = '';
  cPersonMob = '';
  cPersonEmail = '';
  selectedVendor: any = {};
  existingVendors: any[] = [];
  clients: any[] = [];
  countries: string[] = [
    'Afghanistan', 'Albania', 'Algeria', 'American Samoa', 'Andorra',
    'Angola', 'Anguilla', 'Antigua & Barbuda', 'Argentina', 'Armenia',
    'Aruba', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas',
    'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium',
    'Belize', 'Benin', 'Bermuda', 'Bhutan', 'Bolivia',
    'Bosnia & Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria',
    'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada',
    'Chile', 'China', 'Colombia', 'Costa Rica', 'Croatia',
    'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 'Dominican Republic',
    'Ecuador', 'Egypt', 'El Salvador', 'Estonia', 'Ethiopia',
    'Fiji', 'Finland', 'France', 'Germany', 'Greece',
    'Hong Kong', 'Hungary', 'Iceland', 'India', 'Indonesia',
    'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy',
    'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya',
    'Kuwait', 'Latvia', 'Lebanon', 'Lithuania', 'Luxembourg',
    'Malaysia', 'Maldives', 'Malta', 'Mexico', 'Monaco',
    'Mongolia', 'Morocco', 'Myanmar', 'Nepal', 'Netherlands',
    'New Zealand', 'Nigeria', 'Norway', 'Oman', 'Pakistan',
    'Panama', 'Peru', 'Philippines', 'Poland', 'Portugal',
    'Qatar', 'Romania', 'Russia', 'Saudi Arabia', 'Singapore',
    'Slovakia', 'Slovenia', 'South Africa', 'South Korea', 'Spain',
    'Sri Lanka', 'Sweden', 'Switzerland', 'Syria', 'Taiwan',
    'Tanzania', 'Thailand', 'Trinidad & Tobago', 'Tunisia', 'Turkey',
    'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States of America',
    'Uruguay', 'Uzbekistan', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
  ];
  statesIndia: string[] = [
    'Andhra Pradesh', 'Andaman and Nicobar Islands', 'Arunachal Pradesh', 'Assam',
    'Bihar', 'Chandigarh', 'Chhattisgarh', 'Dadra and Nagar Haveli', 'Daman and Diu', 'Delhi',
    'Lakshadweep', 'Puducherry', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir',
    'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
    'Uttar Pradesh', 'Uttarakhand', 'West Bengal'
  ];
  provincesCanada: string[] = [
    'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
    'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
    'Quebec', 'Saskatchewan', 'Yukon'
  ];
  tradingCurrency = [
    { code: 'USD', name: 'US Dollar' }, { code: 'INR', name: 'Indian Rupee' }, { code: 'CAD', name: 'Canadian Dollar' }
  ];

  isListMode = true;
  results: any[] = [];
  loading = false;
  searchQuery = '';
  editingId: string | number | null = null;
  qaStatusRemark = '';

  constructor(
    private fb: FormBuilder,
    private service: DataAccessService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.buildForm();
    this.getClients();
    this.getApprovedVendorsWithoutDivision();
    this.setupConditionalValidators();
    this.updateConditionalValidators();
    this.vendorForm.get('country')?.updateValueAndValidity({ emitEvent: true });
    this.loadReturnedList();
  }

  private buildForm(): void {
    this.vendorForm = this.fb.group({
      vendor_Is: ['Vendor', Validators.required],
      parentVendor: [''],
      vendor_type: ['', Validators.required],
      material_type: ['', Validators.required],
      vendor_name: ['', [Validators.required, Validators.minLength(2)]],
      contact_person: ['', [Validators.required, Validators.minLength(2)]],
      contact_number: ['', canadianPhoneRequiredValidators()],
      contact_email: ['', [Validators.required, Validators.email]],
      address: ['', [Validators.required, Validators.minLength(5)]],
      country: ['Canada', Validators.required],
      permanent_state: ['', Validators.required],
      city: ['', [Validators.required, Validators.minLength(2)]],
      pincode: ['', [Validators.required, Validators.pattern(POSTAL_CA_PATTERN)]],
      qualifiedBy: ['Own', Validators.required],
      client_code: [''],
      gst_applicable: [''],
      scode: [''],
      gst_no: [''],
      panNo: [''],
      vendorFor: ['Domestic', Validators.required],
      sameaddress: [false],
      c_unit_name: ['', Validators.required],
      c_address: ['', Validators.required],
      c_country: ['Canada', Validators.required],
      c_state: ['', Validators.required],
      c_city: ['', Validators.required],
      c_pincode: ['', [Validators.required, Validators.pattern(POSTAL_CA_PATTERN)]],
      c_mobile_no: ['', canadianPhoneRequiredValidators()],
      c_gst_applicable: [''],
      c_scode: [''],
      c_gst_no: [''],
      c_panNo: ['']
    });
  }

  private setupConditionalValidators(): void {
    this.vendorForm.get('country')?.valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('vendor_Is')?.valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('qualifiedBy')?.valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('c_country')?.valueChanges.subscribe(() => this.updateConditionalValidators());
  }

  private updateConditionalValidators(): void {
    const country = this.vendorForm.get('country')?.value;
    const vendorIs = this.vendorForm.get('vendor_Is')?.value;
    const qualifiedBy = this.vendorForm.get('qualifiedBy')?.value;
    const cCountry = this.vendorForm.get('c_country')?.value;

    const parentVendor = this.vendorForm.get('parentVendor');
    if (vendorIs === 'Division') {
      parentVendor?.setValidators(Validators.required);
    } else {
      parentVendor?.clearValidators();
    }
    parentVendor?.updateValueAndValidity();

    this.applyAddressFieldValidators(country, {
      pin: this.vendorForm.get('pincode')!,
      mobile: this.vendorForm.get('contact_number')!,
    });
    this.applyAddressFieldValidators(cCountry, {
      pin: this.vendorForm.get('c_pincode')!,
      mobile: this.vendorForm.get('c_mobile_no')!,
    });

    const clientCode = this.vendorForm.get('client_code');
    if (qualifiedBy === 'Client') {
      clientCode?.setValidators(Validators.required);
    } else {
      clientCode?.clearValidators();
    }
    clientCode?.updateValueAndValidity();
  }

  private applyAddressFieldValidators(
    country: string,
    ctrls: { pin: AbstractControl; mobile: AbstractControl }
  ): void {
    const { pin, mobile } = ctrls;
    if (country === 'Canada') {
      pin.setValidators([Validators.required, Validators.pattern(POSTAL_CA_PATTERN)]);
      mobile.setValidators(canadianPhoneRequiredValidators());
    } else if (country === 'India') {
      pin.setValidators([Validators.required, Validators.pattern(PINCODE_IN_PATTERN)]);
      mobile.setValidators(canadianPhoneRequiredValidators());
    } else {
      pin.setValidators([Validators.required, Validators.pattern(POSTAL_INTL_PATTERN)]);
      mobile.setValidators(canadianPhoneRequiredValidators());
    }
    pin.updateValueAndValidity({ emitEvent: false });
    mobile.updateValueAndValidity({ emitEvent: false });
  }

  loadReturnedList(): void {
    this.loading = true;
    this.service.get('purchase/vendor.php?type=getVendorsReturnedByQa').subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.loading = false;
      }
    );
  }

  get filteredRows(): any[] {
    if (!this.results?.length) {
      return [];
    }
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.results;
    }
    return this.results.filter((r) =>
      Object.entries(r).some(([k, v]) => {
        if (v == null || k === 'other_contact' || k === 'selectedCurrencies') {
          return false;
        }
        return String(v).toLowerCase().includes(q);
      })
    );
  }

  startEdit(row: any): void {
    this.editingId = row.id;
    this.qaStatusRemark = row.status_remark || '';
    this.other_contact = this.parseContactArray(row.other_contact);
    this.selectedCurrencies = this.parseCurrencyArray(row.selectedCurrencies);
    if (!this.selectedCurrencies.length) {
      this.selectedCurrencies = [{ code: 'CAD' }];
    }
    this.vendorForm.patchValue({
      vendor_Is: row.vendor_Is || 'Vendor',
      parentVendor: row.parentVendor || '',
      vendor_type: row.vendor_type || '',
      material_type: row.material_type || '',
      vendor_name: row.vendor_name || '',
      contact_person: row.contact_person || '',
      contact_number: row.contact_number || '',
      contact_email: row.contact_email || '',
      address: row.address || '',
      country: row.country || 'Canada',
      permanent_state: row.permanent_state || '',
      city: row.city || '',
      pincode: row.pincode || '',
      qualifiedBy: row.qualifiedBy || 'Own',
      client_code: row.client_code || '',
      gst_applicable: row.gst_applicable || '',
      scode: row.scode || '',
      gst_no: row.gst_no || '',
      panNo: row.panNo || '',
      vendorFor: row.vendorFor || 'Domestic',
      sameaddress: false,
      c_unit_name: row.c_unit_name || '',
      c_address: row.c_address || '',
      c_country: row.c_country || 'Canada',
      c_state: row.c_state || '',
      c_city: row.c_city || '',
      c_pincode: row.c_pincode || '',
      c_mobile_no: row.c_mobile_no || '',
      c_gst_applicable: row.c_gst_applicable || '',
      c_scode: row.c_scode || '',
      c_gst_no: row.c_gst_no || '',
      c_panNo: row.c_panNo || '',
    });
    this.vendorFor = this.vendorForm.get('vendorFor')?.value;
    this.updateConditionalValidators();
    this.isListMode = false;
  }

  private parseContactArray(value: any): Array<{ contactType: string; cPersonName: string; cPersonMob: string; cPersonEmail: string }> {
    if (Array.isArray(value)) {
      return value.filter(Boolean);
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const p = JSON.parse(value);
        return Array.isArray(p) ? p : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  private parseCurrencyArray(value: any): Array<{ code: string }> {
    if (Array.isArray(value)) {
      return value.map((x: any) => (typeof x === 'string' ? { code: x } : { code: x.code || '' })).filter((x) => x.code);
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const p = JSON.parse(value);
        if (Array.isArray(p)) {
          return p.map((x: any) => (typeof x === 'string' ? { code: x } : { code: x.code || '' })).filter((x) => x.code);
        }
      } catch {
        return [];
      }
    }
    return [];
  }

  backToList(): void {
    this.isListMode = true;
    this.editingId = null;
    this.qaStatusRemark = '';
    this.loadReturnedList();
  }

  toggleTax(value: string): void {
    const domestic = value === 'Canada' || value === 'India';
    this.vendorForm.get('vendorFor')?.setValue(domestic ? 'Domestic' : 'Import');
    this.vendorFor = this.vendorForm.get('vendorFor')?.value;
  }

  getClients(): void {
    this.service.get('common.php?type=getClients').subscribe((response: any) => {
      this.clients = Array.isArray(response) ? response : [];
    });
  }

  getApprovedVendorsWithoutDivision(): void {
    this.service.get('common.php?type=getApprovedVendorsWithoutDivision').subscribe((response: any) => {
      this.existingVendors = Array.isArray(response) ? response : [];
    });
  }

  onCheckboxChange(): void {
    const same = this.vendorForm.get('sameaddress')?.value;
    if (same) {
      this.vendorForm.patchValue({
        c_unit_name: this.vendorForm.get('vendor_name')?.value,
        c_address: this.vendorForm.get('address')?.value,
        c_country: this.vendorForm.get('country')?.value,
        c_state: this.vendorForm.get('permanent_state')?.value,
        c_city: this.vendorForm.get('city')?.value,
        c_pincode: this.vendorForm.get('pincode')?.value,
        c_mobile_no: this.vendorForm.get('contact_number')?.value,
        c_gst_applicable: '',
        c_scode: '',
        c_gst_no: '',
        c_panNo: ''
      });
    } else {
      this.vendorForm.patchValue({
        c_unit_name: '', c_address: '', c_country: 'Canada', c_state: '', c_city: '',
        c_pincode: '', c_mobile_no: '', c_gst_applicable: '', c_scode: '', c_gst_no: '', c_panNo: ''
      });
    }
  }

  addCperson(): void {
    if (!this.contactType || !this.cPersonName || !this.cPersonMob || !this.cPersonEmail) {
      alert('All contact fields are required.');
      return;
    }
    const mobCa = isValidCanadianPhone(this.cPersonMob);
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(this.cPersonEmail).trim());
    if (!mobCa) {
      alert('Invalid Canadian phone number (10 digits, e.g. 416-555-1234).');
      return;
    }
    if (!emailValid) {
      alert('Invalid email address.');
      return;
    }
    this.other_contact.push({
      contactType: this.contactType,
      cPersonName: this.cPersonName,
      cPersonMob: this.cPersonMob,
      cPersonEmail: this.cPersonEmail
    });
    this.contactType = '';
    this.cPersonName = '';
    this.cPersonMob = '';
    this.cPersonEmail = '';
  }

  copyDetails(): void {
    const ven = this.existingVendors?.find(v => v.vendor_no === this.vendorForm.get('parentVendor')?.value);
    if (!ven) {
      return;
    }
    this.selectedVendor = ven;
    this.vendorForm.patchValue({
      c_unit_name: ven.c_unit_name,
      c_address: ven.c_address,
      country: ven.country,
      c_state: ven.c_state,
      c_city: ven.c_city,
      c_pincode: ven.c_pincode,
      c_mobile_no: ven.c_mobile_no,
      qualifiedBy: ven.qualifiedBy,
      c_gst_applicable: '',
      c_scode: '',
      c_gst_no: '',
      c_panNo: '',
      vendorFor: ven.vendorFor
    });
    this.vendorFor = ven.vendorFor;
    this.updateConditionalValidators();
  }

  addCurr(): void {
    if (!this.currency) {
      alert('Select a currency first.');
      return;
    }
    if (this.selectedCurrencies.some(c => c.code === this.currency)) {
      alert('Currency already added.');
      return;
    }
    this.selectedCurrencies.push({ code: this.currency });
    this.currency = '';
  }

  removeCurr(index: number): void {
    if (this.selectedCurrencies[index]?.code === 'CAD') {
      alert('CAD cannot be removed.');
      return;
    }
    this.selectedCurrencies.splice(index, 1);
  }

  saveAndResubmit(): void {
    if (this.editingId == null || this.editingId === '') {
      alertify.error('No vendor selected.');
      return;
    }
    this.vendorForm.markAllAsTouched();
    if (this.vendorForm.invalid) {
      alert('Please fix validation errors before submitting.');
      return;
    }
    const body = {
      id: this.editingId,
      ...this.vendorForm.value,
      other_contact: this.other_contact,
      selectedCurrencies: this.selectedCurrencies,
    };
    this.service.post('purchase/vendor.php?type=saveVendorQaReturn', JSON.stringify(body)).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          alertify.success('Vendor updated. Status is now Corrected Details — pending purchase approval.');
          this.router.navigate(['/purchase/vendor/approval']);
        } else if (response?.status === 'no rows updated') {
          alertify.error('This vendor is no longer in the QA return queue. Refresh the list.');
          this.backToList();
        } else {
          alertify.error(typeof response?.status === 'string' ? response.status : 'Save failed.');
        }
      },
      () => alertify.error('Save failed.')
    );
  }
}
