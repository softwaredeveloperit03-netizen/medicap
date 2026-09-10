import { Component, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, ValidationErrors, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  countryPhoneRequiredValidators,
  formatCountryPhone,
  getCountryPhoneExample,
  getCountryPhonePlaceholder,
  isValidCountryPhone,
  stripPhoneDigitsForCountry,
} from 'src/app/shared/validators/country-phone.validator';
declare let alertify;

/** Indian IFSC: 4 letters + 0 + 6 alphanumeric; empty allowed */
const IFSC_PATTERN = /^$|^[A-Z]{4}0[A-Z0-9]{6}$/;
/** Canada / international bank routing or SWIFT-style id; empty allowed */
const ROUTING_OR_SWIFT_PATTERN = /^$|^[A-Z0-9\s-]{5,25}$/;
/** Bank account: 9–18 digits; empty allowed */
const ACCOUNT_PATTERN = /^$|^[0-9]{9,18}$/;
/** Indian pincode */
const PINCODE_IN_PATTERN = /^[0-9]{6}$/;
/** Canadian postal code A1A 1A1 or A1A1A1 */
const POSTAL_CA_PATTERN = /^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$/;

/** Intl postal / ZIP: 3–16 chars (trimmed); letters/digits with optional spaces or hyphens between. */
function globalPostalIntlValidator(control: AbstractControl): ValidationErrors | null {
  const raw = control.value;
  if (raw == null || String(raw).trim() === '') {
    return null;
  }
  const v = String(raw).trim();
  if (v.length < 3 || v.length > 16 || !/^[A-Za-z0-9][A-Za-z0-9 \-]{1,14}[A-Za-z0-9]$/.test(v)) {
    return { globalPostal: true };
  }
  return null;
}

@Component({
  selector: 'app-registration',
  templateUrl: './registration.component.html',
  styleUrls: ['./registration.component.css']
})
export class RegistrationComponent implements OnInit {

  vendorForm: FormGroup;
  vendor_Is = 'Vendor';
  vendorFor = 'Domestic';
  sameaddress = false;
  other_contact: Array<{ contactType: string; cPersonName: string; cPersonMob: string; cPersonEmail: string }> = [];
  selectedCurrencies = [{ code: 'CAD' }];
  currency = '';
  contactType = '';
  cPersonName = '';
  cPersonMob = '';
  cPersonEmail = '';
  parentVendor = '';
  selectedVendor: any = {};
  existingVendors: any[];
  allVendors: any[] = [];
  clients: any[];
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
  /** Canadian provinces & territories */
  provincesCanada: string[] = [
    'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
    'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
    'Quebec', 'Saskatchewan', 'Yukon'
  ];
  tradingCurrency = [
    { code: 'USD', name: 'US Dollar' }, { code: 'INR', name: 'Indian Rupee' }, { code: 'CAD', name: 'Canadian Dollar' } 
  ];

  constructor(
    private fb: FormBuilder,
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.buildForm();
    this.getClients();
    this.getApprovedVendorsWithoutDivision();
    this.getAllVendorsForDuplicateCheck();
    this.setupConditionalValidators();
    this.setupDuplicateChecks();
    this.updateConditionalValidators();
    this.vendorForm.get('country').updateValueAndValidity({ emitEvent: true });
  }

  get isSaveDisabled(): boolean {
    if (!this.vendorForm) {
      return true;
    }
    const nameCtrl = this.vendorForm.get('vendor_name');
    const phoneCtrl = this.vendorForm.get('contact_number');
    return (
      this.vendorForm.invalid ||
      !!nameCtrl?.hasError('duplicate') ||
      !!phoneCtrl?.hasError('duplicate')
    );
  }

  private buildForm(): void {
    this.vendorForm = this.fb.group({
      vendor_Is: ['Vendor', Validators.required],
      parentVendor: [''],
      vendor_type: ['', Validators.required],
      material_type: ['', Validators.required],
      vendor_name: ['', [Validators.required, Validators.minLength(2)]],
      contact_person: ['', [Validators.required, Validators.minLength(2)]],
      
      contact_email: ['', [Validators.required, Validators.email]],
      contact_number: ['', countryPhoneRequiredValidators('Canada')],
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
      c_mobile_no: ['', countryPhoneRequiredValidators('Canada')],
      c_gst_applicable: [''],
      c_scode: [''],
      c_gst_no: [''],
      c_panNo: [''],
      bank_name: [''],
      branch_address: [''],
      account_holder: [''],
      account_number: ['', Validators.pattern(ACCOUNT_PATTERN)],
      ifsc_code: ['', Validators.pattern(ROUTING_OR_SWIFT_PATTERN)],
      payment_mode: ['']
    });
  }

  private setupConditionalValidators(): void {
    this.vendorForm.get('country').valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('vendor_Is').valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('qualifiedBy').valueChanges.subscribe(() => this.updateConditionalValidators());
    this.vendorForm.get('c_country').valueChanges.subscribe(() => this.updateConditionalValidators());
  }

  private setupDuplicateChecks(): void {
    this.vendorForm.get('vendor_name').valueChanges.subscribe(() => this.checkDuplicateVendorName(false));
    this.vendorForm.get('contact_number').valueChanges.subscribe(() => this.checkDuplicateVendorPhone(false));
  }

  private updateConditionalValidators(): void {
    const country = this.vendorForm.get('country').value;
    const vendorIs = this.vendorForm.get('vendor_Is').value;
    const qualifiedBy = this.vendorForm.get('qualifiedBy').value;
    const cCountry = this.vendorForm.get('c_country').value;

    const parentVendor = this.vendorForm.get('parentVendor');
    if (vendorIs === 'Division') {
      parentVendor.setValidators(Validators.required);
    } else {
      parentVendor.clearValidators();
    }
    parentVendor.updateValueAndValidity();

    this.applyAddressFieldValidators(country, {
      pin: this.vendorForm.get('pincode'),
      mobile: this.vendorForm.get('contact_number'),
    });
    this.applyAddressFieldValidators(cCountry, {
      pin: this.vendorForm.get('c_pincode'),
      mobile: this.vendorForm.get('c_mobile_no'),
    });

    const ifsc = this.vendorForm.get('ifsc_code');
    if (cCountry === 'India') {
      ifsc.setValidators([Validators.pattern(IFSC_PATTERN)]);
    } else {
      ifsc.setValidators([Validators.pattern(ROUTING_OR_SWIFT_PATTERN)]);
    }
    ifsc.updateValueAndValidity();

    const clientCode = this.vendorForm.get('client_code');
    if (qualifiedBy === 'Client') {
      clientCode.setValidators(Validators.required);
    } else {
      clientCode.clearValidators();
    }
    clientCode.updateValueAndValidity();
  }

  private applyAddressFieldValidators(
    country: string,
    ctrls: { pin: AbstractControl; mobile: AbstractControl }
  ): void {
    const { pin, mobile } = ctrls;
    if (country === 'Canada') {
      pin.setValidators([Validators.required, Validators.pattern(POSTAL_CA_PATTERN)]);
      mobile.setValidators(countryPhoneRequiredValidators(country));
    } else if (country === 'India') {
      pin.setValidators([Validators.required, Validators.pattern(PINCODE_IN_PATTERN)]);
      mobile.setValidators(countryPhoneRequiredValidators(country));
    } else {
      pin.setValidators([Validators.required, globalPostalIntlValidator]);
      mobile.setValidators(countryPhoneRequiredValidators(country));
    }
    pin.updateValueAndValidity({ emitEvent: false });
    mobile.updateValueAndValidity({ emitEvent: false });
  }

  toggleTax(value: string): void {
    const domestic = value === 'Canada' || value === 'India';
    this.vendorForm.get('vendorFor').setValue(domestic ? 'Domestic' : 'Import');
    this.vendorFor = this.vendorForm.get('vendorFor').value;
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

  getAllVendorsForDuplicateCheck(): void {
    this.service.get('vendor.php?type=getVendorLog').subscribe((response: any) => {
      this.allVendors = Array.isArray(response) ? response : [];
    });
  }

  private normalizeVendorName(name: string): string {
    return String(name || '').trim().toLowerCase();
  }

  checkDuplicateVendorName(showAlert = true): boolean {
    const ctrl = this.vendorForm.get('vendor_name');
    const name = this.normalizeVendorName(ctrl.value);
    if (!name) {
      this.clearDuplicateError(ctrl);
      return false;
    }
    const duplicate = this.allVendors.some(v => this.normalizeVendorName(v.vendor_name) === name);
    if (duplicate) {
      if (showAlert) {
        alertify.error('Vendor with this name already exists');
      }
      ctrl.setErrors({ ...(ctrl.errors || {}), duplicate: true });
    } else {
      this.clearDuplicateError(ctrl);
    }
    return duplicate;
  }

  checkDuplicateVendorPhone(showAlert = true): boolean {
    const ctrl = this.vendorForm.get('contact_number');
    const country = this.vendorForm.get('country').value;
    const phone = stripPhoneDigitsForCountry(ctrl.value, country);
    if (!phone) {
      this.clearDuplicateError(ctrl);
      return false;
    }
    const duplicate = this.allVendors.some(
      v => stripPhoneDigitsForCountry(v.contact_number, country) === phone
    );
    if (duplicate) {
      if (showAlert) {
        alertify.error('Vendor with this phone number already exists');
      }
      ctrl.setErrors({ ...(ctrl.errors || {}), duplicate: true });
    } else {
      this.clearDuplicateError(ctrl);
    }
    return duplicate;
  }

  private clearDuplicateError(ctrl: AbstractControl): void {
    if (!ctrl?.hasError('duplicate')) {
      return;
    }
    const errors = { ...ctrl.errors };
    delete errors.duplicate;
    ctrl.setErrors(Object.keys(errors).length ? errors : null);
  }

  onCheckboxChange(): void {
    this.sameaddress = this.vendorForm.get('sameaddress').value;
    if (this.sameaddress) {
      this.vendorForm.patchValue({
        c_unit_name: this.vendorForm.get('vendor_name').value,
        c_address: this.vendorForm.get('address').value,
        c_country: this.vendorForm.get('country').value,
        c_state: this.vendorForm.get('permanent_state').value,
        c_city: this.vendorForm.get('city').value,
        c_pincode: this.vendorForm.get('pincode').value,
        c_mobile_no: this.vendorForm.get('contact_number').value,
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

    const country = this.vendorForm.get('country').value;
    if (!isValidCountryPhone(this.cPersonMob, country)) {
      alert(`Enter a valid phone number for ${country} (e.g. ${getCountryPhoneExample(country)}).`);
      return;
    }

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(this.cPersonEmail).trim());

    if (!emailValid) {
      alert('Invalid email address.');
      return;
    }
    this.other_contact.push({
      contactType: this.contactType,
      cPersonName: this.cPersonName,
      cPersonMob: formatCountryPhone(this.cPersonMob, country),
      cPersonEmail: this.cPersonEmail
    });
    this.contactType = '';
    this.cPersonName = '';
    this.cPersonMob = '';
    this.cPersonEmail = '';
  }

  addVendor(): void {
    if (this.checkDuplicateVendorName() || this.checkDuplicateVendorPhone()) {
      return;
    }
    this.vendorForm.markAllAsTouched();
    if (this.vendorForm.invalid) {
      Object.keys(this.vendorForm.controls).forEach(key => {
        const c = this.vendorForm.get(key);
        if (c && c.invalid && c.errors) {
          console.warn(key, c.errors);
        }
      });
      alert('Please fix validation errors before submitting.');
      return;
    }
    const temp = { ...this.vendorForm.value, other_contact: this.other_contact, selectedCurrencies: this.selectedCurrencies };
    this.service.post('vendor.php?type=saveVendor', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Vendor Saved successfully');
        
        this.vendorForm.reset({
          vendor_Is: 'Vendor', country: 'Canada', vendorFor: 'Domestic', qualifiedBy: 'Own',
          c_country: 'Canada', sameaddress: false
        });
        this.other_contact = [];
        this.selectedCurrencies = [{ code: 'CAD' }];
        this.updateConditionalValidators();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  copyDetails(): void {
    const ven = this.existingVendors?.find(v => v.vendor_no === this.vendorForm.get('parentVendor').value);
    if (!ven) return;
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
    if (this.selectedCurrencies[index].code === 'CAD') {
      alert('CAD cannot be removed.');
      return;
    }
    this.selectedCurrencies.splice(index, 1);
  }

  getPhonePlaceholder(country: string): string {
    return getCountryPhonePlaceholder(country);
  }

  onPhoneBlur(field: 'contact_number' | 'c_mobile_no', country: string): void {
    const ctrl = this.vendorForm.get(field);
    if (!ctrl?.value) {
      return;
    }
    const formatted = formatCountryPhone(ctrl.value, country);
    if (formatted && formatted !== ctrl.value) {
      ctrl.setValue(formatted, { emitEvent: false });
    }
  }

  onOtherContactPhoneBlur(): void {
    const country = this.vendorForm.get('country').value;
    if (!this.cPersonMob) {
      return;
    }
    this.cPersonMob = formatCountryPhone(this.cPersonMob, country);
  }

  formatContactPhone(value: string): string {
    return formatCountryPhone(value, this.vendorForm.get('country').value);
  }
}
