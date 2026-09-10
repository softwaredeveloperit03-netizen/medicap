import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-rentagreement',
  templateUrl: './rentagreement.component.html',
  styleUrls: ['./rentagreement.component.css'],
})
export class RentagreementComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  entries: any[] = [];
  isView = false;
  isNew = false;
  isuser = 'No';

  selectedResult: any = {};
  selectedFile2: File | undefined;

  agreement_type: 'rent' | 'employee' | 'contract' | '' = '';

  readonly agreementTypeOptions = [
    { value: 'rent', label: 'Rent / Lease' },
    { value: 'employee', label: 'Employee' },
    { value: 'contract', label: 'Contract' },
  ] as const;

  agreement_id = '';
  agreement_title = '';
  entry_date = '';
  agreement_date = '';
  valid_date = ''; // ✅ NEW
  expiry_date = '';
  company_name = '';
  employee_name = '';
  description = '';
  legal_form = '';
  property_type = '';
  property_name = '';
  property_address = '';
  landlord = '';
  tenant = '';
  administrator = '';
  lease_terms = '';
  rent_amount = '';
  security_deposit = '';
  payment = '';

  ngOnInit(): void {
    this.get_rights();
    this.getAgreementDetails();
  }

  get_rights() {
    const empId = localStorage.getItem('emp_id');
    const dep = localStorage.getItem('department');
    this.service.get(`hr/employee.php?type=getrights&emp_id=${empId}&dep_name=${dep}`).subscribe({
      next: (response: any) => {
        if (response && response[0]) {
          this.isuser = response[0].isuser || 'No';
        }
      },
      error: () => {
        this.isuser = 'No';
      },
    });
  }

  onAgreementTypeChange(value: string) {
    this.agreement_type = value as 'rent' | 'employee' | 'contract' | '';
    console.log('[Agreement] category selected:', this.agreement_type);
  }

  openNew() {
    this.clearFormForNew();
    this.isNew = true;
    this.isView = false;
  }

  cancelNew() {
    this.isNew = false;
    this.clearFormForNew();
  }

  clearFormForNew() {
    this.agreement_type = '';
    this.agreement_id = '';
    this.agreement_title = '';
    this.entry_date = '';
    this.agreement_date = '';
    this.valid_date = ''; // ✅ NEW
    this.expiry_date = '';
    this.company_name = '';
    this.employee_name = '';
    this.description = '';
    this.legal_form = '';
    this.property_type = '';
    this.property_name = '';
    this.property_address = '';
    this.landlord = '';
    this.tenant = '';
    this.administrator = '';
    this.lease_terms = '';
    this.rent_amount = '';
    this.security_deposit = '';
    this.payment = '';
    this.selectedFile2 = undefined;
    this.selectedResult = {};
  }

  viewEntry(index: number) {
    this.isNew = false;
    this.selectedResult = this.entries[index];
    this.populateFormFields();
    this.isView = true;

    const agreementId =
      this.selectedResult['agreement_id'] || this.selectedResult['agreementId'] || this.selectedResult['id'];
    if (agreementId) {
      this.service.get(`admin.php?type=getAgreementDetails&agreement_id=${agreementId}`).subscribe({
        next: (fullDetails: any) => {
          if (fullDetails && typeof fullDetails === 'object') {
            this.selectedResult = { ...this.selectedResult, ...fullDetails };
            this.populateFormFields();
          }
        },
        error: () => {},
      });
    }
  }

  populateFormFields() {
    const getValue = (obj: any, ...keys: string[]): string => {
      for (const key of keys) {
        if (obj[key] !== null && obj[key] !== undefined && obj[key] !== '') {
          return String(obj[key]);
        }
      }
      return '';
    };

    this.agreement_type = getValue(this.selectedResult, 'agreement_type', 'agreementType', 'type') as any;
    this.agreement_id = getValue(this.selectedResult, 'agreement_id', 'agreementId', 'id');
    this.agreement_title = getValue(this.selectedResult, 'agreement_title', 'agreementTitle', 'title');
    this.entry_date = getValue(this.selectedResult, 'entry_date', 'entryDate', 'effective_date', 'effectiveDate');
    this.agreement_date = getValue(this.selectedResult, 'agreement_date', 'agreementDate', 'entry_date', 'entryDate');
    this.valid_date = getValue(this.selectedResult, 'valid_date', 'validDate'); // ✅ NEW
    this.expiry_date = getValue(this.selectedResult, 'expiry_date', 'expiryDate', 'expiry');
    this.company_name = getValue(this.selectedResult, 'company_name', 'companyName');
    this.employee_name = getValue(this.selectedResult, 'employee_name', 'employeeName');
    this.description = getValue(this.selectedResult, 'description', 'desc');
    this.legal_form = getValue(this.selectedResult, 'legal_form', 'legalForm');
    this.property_type = getValue(this.selectedResult, 'property_type', 'propertyType');
    this.property_name = getValue(this.selectedResult, 'property_name', 'propertyName', 'property_description', 'propertyDescription');
    this.property_address = getValue(this.selectedResult, 'property_address', 'propertyAddress', 'address');
    this.landlord = getValue(this.selectedResult, 'landlord', 'landlord_name', 'landlordName', 'lessor');
    this.tenant = getValue(this.selectedResult, 'tenant', 'tenant_name', 'tenantName', 'lessee');
    this.administrator = getValue(this.selectedResult, 'administrator', 'lease_administrator', 'leaseAdministrator', 'admin');
    this.lease_terms = getValue(this.selectedResult, 'lease_terms', 'leaseTerms', 'terms', 'terms_conditions');
    this.rent_amount = getValue(this.selectedResult, 'rent_amount', 'rentAmount', 'rent', 'amount');
    this.security_deposit = getValue(this.selectedResult, 'security_deposit', 'securityDeposit', 'deposit');
    this.payment = getValue(this.selectedResult, 'payment', 'payment_frequency', 'paymentFrequency', 'payment_type');

    const toYmd = (v: string) => {
      if (!v) return '';
      const d = new Date(v);
      if (!isNaN(d.getTime())) return d.toISOString().split('T')[0];
      return v;
    };

    this.entry_date = toYmd(this.entry_date) || this.entry_date;
    this.agreement_date = toYmd(this.agreement_date) || this.agreement_date;
    this.valid_date = toYmd(this.valid_date) || this.valid_date; // ✅ NEW
    this.expiry_date = toYmd(this.expiry_date) || this.expiry_date;

    this.selectedFile2 = undefined;
  }

  onFileChanged3(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.selectedFile2 = input.files[0];
    }
  }

  saveForm(data: any) {
    if (!data.valid) {
      alertify?.error?.('Please fill all required fields') || alert('Please fill all required fields');
      return;
    }

    const v = data.value;
    const formData = new FormData();

    formData.append('agreement_title', v.agreement_title ?? '');
    formData.append('company_name', v.company_name ?? '');
    formData.append('employee_name', v.employee_name ?? '');
    formData.append('description', v.description ?? '');
    formData.append('agreement_date', v.agreement_date ?? '');
    formData.append('valid_date', v.valid_date ?? ''); // ✅ NEW
    formData.append('legal_form', v.legal_form ?? '');

    if (this.agreement_type) formData.append('agreement_type', this.agreement_type);
    if (this.agreement_id) formData.append('agreement_id', this.agreement_id);

    if (this.selectedFile2) {
      formData.append('file_agreement', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('admin.php?type=saveEmployeeAgreement', formData).subscribe({
      next: (response: any) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getAgreementDetails();
          this.isNew = false;
          this.isView = false;
          this.clearFormForNew();
          alertify?.success?.(this.service.t?.('common.savedSuccess') || 'Saved Successfully') || alert('Saved Successfully');
        } else {
          alertify?.error?.('An error has occurred, please try again') || alert('An error has occurred, please try again');
        }
      },
      error: (error: any) => {
        const status = error?.status ?? error;
        alertify?.error?.('HTTP status: ' + status) || alert('An error has occurred, http status:' + status);
      },
    });
  }

  closeView() {
    this.isView = false;
    this.clearFormForNew();
  }

  close() {
    this.router.navigate(['/admin/aggremment']);
  }

  getAgreementDetails() {
    this.service.get('admin.php?type=getEmployeeAgreement').subscribe({
      next: (response: any) => {
        this.entries = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.entries = [];
      },
    });
  }
}