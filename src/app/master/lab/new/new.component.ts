import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormGroup, FormBuilder, Validators, NgForm } from '@angular/forms';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  selectedFile: File;
  selectedFile2: File;
  isUploadLic = 0;
  isuploadcertificate = 0;
  fda_approved = '';
  branchList: any[] = [];
  /** Full branch draft (popup) — mirrors main lab address/contact fields. */
  branchDraft: Record<string, string> = this.emptyBranchDraft();
  isBranchModalOpen = false;

  emptyBranchDraft(): Record<string, string> {
    return {
      branch_name: '',
      contact_person: '',
      contact_no: '',
      email: '',
      address: '',
      country: '',
      ocountry: '',
      permanent_state: '',
      city: '',
      pincode: '',
      gst_applicable: 'Not Applicable',
      tax: '',
    };
  }

  openBranchModal(): void {
    this.branchDraft = this.emptyBranchDraft();
    this.isBranchModalOpen = true;
  }

  closeBranchModal(): void {
    this.isBranchModalOpen = false;
  }
  changeform: FormGroup;
  submitted = false;
  tax = '';
  gst_applicable = 'Not Applicable';
  flag: boolean;
  flag1: boolean;
  flag2 = false;
  flag3 = false;
  fdaFileUploadCount = [];
  fdaAttempted = false;

  lab_name = '';
  contact_person = '';
  country = '';
  ocountry = '';
  permanent_state = '';
  city = '';
  pincode = '';
  email = '';
  address = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private formBuilder: FormBuilder
  ) {}

  ngOnInit(): void {
    this.changeform = this.formBuilder.group({
      email: [
        '',
        [
          Validators.required,
          Validators.email,
          Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$'),
        ],
      ],
    });
    this.getGst();
  }

  gsts;

  getGst() {
    this.service.get('common.php?type=getGST').subscribe((response) => {
      this.gsts = response;
    });
  }

  contact_no: string;
  /** Strip non-digits; max 10 (template cannot use /regex/ literals). */
  onContactNoInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    this.contact_no = (el.value || '').replace(/\D/g, '').slice(0, 10);
  }

  onBranchDraftContactNoInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    this.branchDraft.contact_no = (el.value || '').replace(/\D/g, '').slice(0, 15);
  }

  onBranchPinInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (this.branchDraft.country === 'CANADA') {
      this.branchDraft.pincode = this.normalizeCanadianPostal(el.value || '');
      el.value = this.branchDraft.pincode;
    }
  }

  onBranchDraftEmailChange(v: string): void {
    const re = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    this.branchDraftEmailInvalid = v ? !re.test(String(v).trim()) : false;
  }

  branchDraftEmailInvalid = false;
  fda_lic_no: string;
  lic: any;

  getState(_value: string) {
    this.permanent_state = '';
  }

  onPinInput(event: Event) {
    const el = event.target as HTMLInputElement;
    if (this.country === 'CANADA') {
      this.pincode = this.normalizeCanadianPostal(el.value || '');
      el.value = this.pincode;
    }
  }

  private normalizeCanadianPostal(value: string): string {
    let v = String(value || '')
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, '')
      .slice(0, 6);
    if (v.length > 3) {
      v = v.slice(0, 3) + ' ' + v.slice(3);
    }
    return v;
  }

  numberOnly(value: string) {
    if (isNaN(Number(value))) {
      alertify.error('Only Numeric Value');
      this.contact_no = '';
      return;
    }
  }
  fdanolist = [];
  onFileChanged(event, data) {
    data.file = event.target.files[0];
  }
  uploadFdf(data: NgForm) {
    if (!data.valid) {
      alertify.error('Enter a valid FDA licence number before adding.');
      Object.keys(data.controls).forEach((k) => {
        const c = data.controls[k];
        c.markAsTouched();
      });
      return;
    }
    const temp = { ...data.value };
    this.fdanolist.push(temp);
    data.resetForm({ fda_lic_no: '' });
    this.fda_lic_no = '';
    alertify.success('FDA row added.');
  }

  get f() {
    return this.changeform.controls;
  }

  addBranchFromModal(): void {
    const b = this.branchDraft;
    if (!b.branch_name || String(b.branch_name).trim().length < 2) {
      alertify.error('Enter branch name (min 2 characters).');
      return;
    }
    if (!b.contact_person || String(b.contact_person).trim().length < 2) {
      alertify.error('Enter branch contact person.');
      return;
    }
    const phone = String(b.contact_no || '').replace(/\D/g, '');
    if (phone.length < 10 || phone.length > 15) {
      alertify.error('Branch contact number must be 10–15 digits.');
      return;
    }
    const emailRe = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    if (!emailRe.test(String(b.email || '').trim())) {
      alertify.error('Invalid branch email.');
      return;
    }
    if (!b.address || String(b.address).trim().length < 3) {
      alertify.error('Enter branch address.');
      return;
    }
    if (!b.country) {
      alertify.error('Select branch country.');
      return;
    }
    if (b.country === 'Other' && !String(b.ocountry || '').trim()) {
      alertify.error('Enter other country for branch.');
      return;
    }
    if (!String(b.permanent_state || '').trim()) {
      alertify.error('Enter state / province for branch.');
      return;
    }
    if (!String(b.city || '').trim()) {
      alertify.error('Enter branch city.');
      return;
    }
    if (b.country === 'CANADA') {
      if (!/^[A-Za-z]\d[A-Za-z][\s-]?\d[A-Za-z]\d$/.test(String(b.pincode || '').trim())) {
        alertify.error('Branch postal code must be in A1A 1A1 format.');
        return;
      }
    } else if (String(b.pincode || '').trim().length < 4) {
      alertify.error('Enter valid postal / ZIP code for branch.');
      return;
    }
    if (b.gst_applicable === 'Applicable' && !String(b.tax || '').trim()) {
      alertify.error('Select tax % for branch when GST is applicable.');
      return;
    }
    const row = {
      branch_name: String(b.branch_name).trim(),
      contact_person: String(b.contact_person).trim(),
      contact_no: phone,
      email: String(b.email).trim(),
      address: String(b.address).trim(),
      country: b.country === 'CANADA' ? 'CANADA' : String(b.ocountry || b.country).trim(),
      permanent_state: String(b.permanent_state).trim(),
      city: String(b.city).trim(),
      pincode: String(b.pincode).trim(),
      gst_applicable: b.gst_applicable,
      tax: b.gst_applicable === 'Applicable' ? String(b.tax) : '',
    };
    this.branchList.push(row);
    this.branchDraft = this.emptyBranchDraft();
    this.branchDraftEmailInvalid = false;
    this.isBranchModalOpen = false;
    alertify.success('Branch added.');
  }

  delData(index: number) {
    this.branchList.splice(index, 1);
  }

  onFileChanged2(event) {
    this.selectedFile2 = event.target.files[0];
    this.isuploadcertificate += 1;
    this.uploadSingleFile(event);
  }
  uploadSingleFile(event) {
    if (event.target.files.length === 1) {
      this.structureFile = event.target.files[0];
    }
    this.uploadData = new FormData();
    if (this.structureFile !== undefined) {
      this.uploadData.append('structure_file', this.structureFile, this.structureFile.name);
    }
  }

  private markFormTouched(form: NgForm) {
    if (!form?.controls) {
      return;
    }
    Object.keys(form.controls).forEach((k) => {
      form.controls[k].markAsTouched();
    });
  }

  saveform(formData: NgForm) {
    this.fdaAttempted = true;
    this.markFormTouched(formData);
    if (this.branchList.length === 0) {
      alertify.error('Add at least one Laboratory Branch.');
      return;
    }
    if (!formData.valid) {
      alertify.error('Please correct errors in the main lab form.');
      return;
    }
    if (this.flag3) {
      alertify.error('Please enter a valid email address.');
      return;
    }
    if (this.fda_approved === 'Yes' && this.fdanolist.length === 0) {
      alertify.error('FDA approved is Yes: add at least one FDA licence row.');
      return;
    }

    const temp = formData.value;
    const uploadData = new FormData();
    for (const key in temp) {
      if (Object.prototype.hasOwnProperty.call(temp, key)) {
        const value = temp[key];
        if (value !== undefined && value !== null) {
          uploadData.append(key, value);
        }
      }
    }

    if (this.gst_applicable === 'Not Applicable' || !this.tax) {
      uploadData.set('tax', this.gst_applicable === 'Applicable' && this.tax ? this.tax : '');
    } else {
      uploadData.set('tax', this.tax);
    }

    uploadData.append('acreditation', '');
    uploadData.append('lic_validity', '');
    uploadData.append('international_acreditation', '');
    const firstFda =
      this.fdanolist.length > 0 ? String(this.fdanolist[0]['fda_lic_no'] || '').trim() : '';
    uploadData.append('fda_lic_no', firstFda);

    if (this.selectedFile2 !== undefined) {
      uploadData.append('certificate', this.selectedFile2, this.selectedFile2.name);
    }

    uploadData.append('branch', JSON.stringify(this.branchList));

    this.service.post('qc/lab.php?type=saveLab', uploadData).subscribe(
      (response) => {
        if (response['status'] == 'success') {
          alertify.success('Successfully saved data');
          formData.resetForm();
          this.branchDraft = this.emptyBranchDraft();
          this.branchList = [];
          this.fdanolist = [];
          this.fdaAttempted = false;
        } else {
          alertify.error('Failed: duplicate entry for lab name or server error.');
        }
      },
      () => {
        alertify.error('Request failed. Check network and try again.');
      }
    );
  }
  structureFile: any;
  uploadData: any;

  number(value) {
    if (isNaN(value)) {
      alertify.error('Number 10 digit Only');
      return false;
    }
  }

  onValueChange1(newValue: string) {
    const hsnCodeRegex = /^\d{8}$/;
    const isValidHsnCode = hsnCodeRegex.test(newValue);

    if (isValidHsnCode) {
      this.flag1 = false;
    } else {
      this.flag1 = true;
    }
  }

  onValueChange2(newValue: string) {
    const hsnCodeRegex = /^[A-Za-z]+$/;
    const isValidHsnCode = hsnCodeRegex.test(newValue);

    if (isValidHsnCode) {
      this.flag2 = false;
    } else {
      this.flag2 = true;
    }
  }

  onValueChange3(newValue: string) {
    const re = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    this.flag3 = newValue ? !re.test(String(newValue).trim()) : false;
  }
}
