import { Component, OnInit, OnDestroy, ViewChild, AfterViewInit, HostListener } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, of } from 'rxjs';
import { debounceTime, takeUntil, catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { FormDraftService } from 'src/app/form-draft.service';
import {
  DATE_DISPLAY_HTML_PATTERN,
  formatIsoToDisplay,
  maskDateDisplayInput,
  parseDisplayToIso,
  parseToLocalDate,
  toIsoDate,
} from 'src/app/shared/date-format.util';
import {
  formatCanadianPhone,
  isValidCanadianPhone,
  stripToCanadianPhoneDigits,
} from 'src/app/shared/validators/canadian-phone.validator';
import {
  CANADIAN_BANK_ADD_NEW,
  DEFAULT_CANADIAN_BANKS,
} from 'src/app/shared/canadian-banks';
declare let alertify;

const FORM_ID_DRAFT = 'hr_emp_form';

/** Fields the PHP saveEmployee INSERT expects; empty defaults avoid undefined-index server errors. */
const EMPLOYEE_SAVE_FIELD_DEFAULTS: Record<string, string> = {
  emp_id: '',
  employee_type: '',
  emp_work_type: '',
  firstname: '',
  middlename: '',
  lastname: '',
  contact_no: '',
  emp_email: '',
  department: '',
  designation: '',
  operator_category: 'Staff',
  joining_status: '',
  trainee_period: '',
  probation_period: '',
  gender: '',
  birthdate: '',
  joining_date: '',
  emp_level: '',
  adhar: '',
  pan: '',
  qualification: '',
  experience: '',
  nationality: '',
  marital_status: '',
  blood_group: '',
  isinduction: '',
  epf_app: 'No',
  pf_no: '',
  esic_app: 'No',
  esic_no: '',
  referenceName: '',
  emergency_contact_name: '',
  emergency_contact_relation: '',
  emergency_contact_email: '',
  doctor_contact_no: '',
  permanent_flat: '',
  permanent_country: '',
  permanent_state: '',
  permanent_city: '',
  permanent_pincode: '',
  tempflat_no: '',
  temp_country: '',
  temp_state: '',
  temp_city: '',
  temp_pincode: '',
  acc_no: '',
  bank_name: '',
  branch_name: '',
  ifsc_neft: '',
  acc_type: '',
  academics: '[]',
  languages: '[]',
  employeement: '[]',
  handicap: '',
};

/** Canadian postal code A1A 1A1 or A1A1A1 */
const POSTAL_CA_PATTERN = /^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$/;
/** Indian PIN */
const PINCODE_IN_PATTERN = /^[0-9]{6}$/;

@Component({
  selector: 'app-employee-form',
  templateUrl: './employee-form.component.html',
  styleUrls: ['./employee-form.component.css'],
})
export class EmployeeFormComponent implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild('employeeRegistration') employeeFormRef: NgForm;

  draftRestored = false;
  draftSavedAt: string | null = null;
  submitAttempted = false;
  private destroy$ = new Subject<void>();
  private draftKey!: string;

  readonly postalCaHtmlPattern = '^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$';
  readonly pinInHtmlPattern = '^[0-9]{6}$';

  employement_type = 'Existing_Employee';
  joining_status = 'Trainee';
  emp_level = '';
  induction = 'Yes';
  epf_app = 'No';
  esic_app = 'No';
  selected_candidate = '';
  gender = 'Male';
  handicap = '';
  employee_type = '';
  employee_category = 'Technical';
  firstname = '';
  middlename = '';
  lastname = '';
  contact_no = '';
  emergency_contact = '';
  emergency_contact_name = '';
  emergency_contact_relation = '';
  emergency_contact_email = '';
  doctor_contact_no = '';
  nationality = '';
  marital_status = '';
  referenceName = '';
  emp_email = '';
  designation = '';
  operator_category = '';
  joining_date = '';
  birthdateDisplay = '';
  joiningDateDisplay = '';
  readonly dateDisplayPattern = DATE_DISPLAY_HTML_PATTERN;
  adhar = '';
  pan = '';
  sin = '';
  blood_group = '';
  photo: any = null;
  pf_no = '';
  esic_no = '';
  acc_no = '';
  acc_type = 'Saving Account';
  bank_name = '';
  transit_no = '';
  institution_no = '';
  canadianBanks: string[] = [...DEFAULT_CANADIAN_BANKS];
  readonly addNewBankOption = CANADIAN_BANK_ADD_NEW;
  showAddBankModal = false;
  newBankName = '';

  readonly emergencyRelationOptions = [
    'Spouse',
    'Parent',
    'Sibling',
    'Child',
    'Friend',
    'Relative',
    'Guardian',
    'Other',
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private formDraft: FormDraftService
  ) {}

  ngOnInit() {
    this.draftKey = this.formDraft.getKey(
      FORM_ID_DRAFT,
      localStorage.getItem('emp_id') || localStorage.getItem('loger_id'),
      localStorage.getItem('plant_id') || undefined
    );
    this.get_rights();
    this.getLastEmployee();
    this.getDepartments();
    this.tryRestoreDraft();
    this.loadCanadianBanks();
  }

  ngAfterViewInit(): void {
    if (this.employeeFormRef && this.employeeFormRef.form) {
      this.employeeFormRef.form.valueChanges
        .pipe(debounceTime(400), takeUntil(this.destroy$))
        .subscribe(() => this.saveDraft());
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  @HostListener('window:beforeunload')
  onBeforeUnload(): void {
    this.saveDraft();
  }

  private tryRestoreDraft(): void {
    setTimeout(() => {
      const draft = this.formDraft.getDraft(this.draftKey);
      if (!draft || !draft.value) return;
      const v = draft.value as any;
      if (v.formValue && this.employeeFormRef && this.employeeFormRef.form) {
        const fv = { ...v.formValue };
        if (fv.birthdate) {
          this.birthdateDisplay = formatIsoToDisplay(fv.birthdate);
          fv.birthdate = this.birthdateDisplay;
        }
        if (fv.joining_date) {
          this.joiningDateDisplay = formatIsoToDisplay(fv.joining_date);
          fv.joining_date = this.joiningDateDisplay;
        }
        this.employeeFormRef.form.patchValue(fv);
      }
      this.draftRestored = true;
      this.draftSavedAt = draft.savedAt;
    }, 400);
  }

  getDraftPayload(): object {
    const formValue = this.employeeFormRef && this.employeeFormRef.form
      ? this.employeeFormRef.form.value
      : {};
    return {
      formValue,
    };
  }

  saveDraft(): void {
    this.formDraft.saveDraft(this.draftKey, this.getDraftPayload(), FORM_ID_DRAFT);
    this.draftSavedAt = new Date().toISOString();
  }

  discardDraft(): void {
    this.formDraft.clearDraft(this.draftKey);
    this.draftRestored = false;
    this.draftSavedAt = null;
    this.employeeFormRef?.form?.reset();
    alertify.message('Draft discarded');
  }

  isuser = 'No';
  isapprover = 'No';
  rights;

  get_rights() {
    this.service
      .get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + localStorage.getItem('department'))
      .pipe(catchError(() => of([])))
      .subscribe((response: any) => {
        this.rights = Array.isArray(response) ? response : [];
        const first = this.rights[0];
        if (first && typeof first === 'object') {
          this.isuser = first.isuser != null ? first.isuser : 'No';
          this.isapprover = first.isapprover != null ? first.isapprover : 'No';
        }
      });
  }





  private readonly ALLOWED_DEPARTMENTS = [
    'Administration',
    'Product Development',
    'Production',
    'Quality Assurance and Compliance',
    'Quality Control',
    'Regulatory Affairs',
    'Business Development',
    'Material Management',
    'Facilities',
    'IT',
  ];

  departments: any[] = [];
  allDesignations: any[] = [];
  getDepartments() {
    this.service
      .get('hr/employee.php?type=get_department_by_designation')
      .pipe(catchError(() => of([])))
      .subscribe((response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.departments = this.ALLOWED_DEPARTMENTS.map((name) =>
          list.find((d: any) => d?.department_name === name) || { id: '', department_name: name, designations: [] }
        );
      });
    this.loadDesignationsFromMaster();
  }

  /** Same source as HR > Masters > Position (`/hr/master/designation`). */
  loadDesignationsFromMaster() {
    this.service
      .get('hr/designation.php?type=getDesignations')
      .pipe(catchError(() => of([])))
      .subscribe((response: any) => {
        this.allDesignations = Array.isArray(response) ? response : [];
        if (this.department) {
          this.applyDesignationsForDepartment();
        }
      });
  }

  designations: any[] = [];
  department = '';
  getDesignation() {
    this.designation = '';
    if (!this.department || String(this.department).trim() === '') {
      this.designations = [];
      return;
    }
    if (this.allDesignations.length === 0) {
      this.loadDesignationsFromMaster();
      return;
    }
    this.applyDesignationsForDepartment();
  }

  private applyDesignationsForDepartment() {
    this.designations = [];
    const deptName = String(this.department || '').trim().toLowerCase();
    if (!deptName) {
      return;
    }
    const deptRow = this.departments.find(
      (d: any) => String(d?.department_name || '').trim().toLowerCase() === deptName
    );
    const deptId = deptRow?.id != null ? String(deptRow.id).trim() : '';

    const seen = new Set<string>();
    this.designations = this.allDesignations.filter((row: any) => {
      const status = String(row?.status || 'active').trim().toLowerCase();
      if (status === 'inactive' || status === 'disabled' || status === 'reject' || status === 'rejected') {
        return false;
      }
      const rowDeptName = String(row?.department_name || '').trim().toLowerCase();
      const rowDeptId = String(row?.dept_id ?? '').trim();
      const matchesDept =
        (rowDeptName !== '' && rowDeptName === deptName) ||
        (deptId !== '' && rowDeptId === deptId);
      if (!matchesDept) {
        return false;
      }
      const name = String(row?.designation || '').trim();
      if (!name) {
        return false;
      }
      const key = name.toLowerCase();
      if (seen.has(key)) {
        return false;
      }
      seen.add(key);
      return true;
    });

    if (this.designations.length === 0 && typeof alertify !== 'undefined') {
      alertify.warning(
        'No positions for this department. Add in HR > Masters > Position Master (select same department).'
      );
    }
  }

  setCandidate(selectedIndex: number) {
    if (this.pending_employees && selectedIndex > 0 && this.pending_employees[selectedIndex - 1]) {
      const cand = this.pending_employees[selectedIndex - 1];
      this.selected_candidate = cand.candidate_name || '';
    }
  }

  onEmploymentTypeChange() {
    this.selected_candidate = '';
  }

 
 
  checkLevel(value){
    if(value == 'Level 1' || value == 'Level 2'){
      this.induction =  'Yes';
    }else{
      this.induction =  'No';
    }
  }

  /** Format contact number as (416) 555-1234 while typing. */
  formatContactNoInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').substring(0, 10);
    let formatted = '';
    if (digits.length > 6) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3, 6)}-${digits.substring(6)}`;
    } else if (digits.length > 3) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3)}`;
    } else if (digits.length > 0) {
      formatted = `(${digits}`;
    }
    input.value = formatted;
    this.contact_no = formatted;
  }

  isContactNoValid(): boolean {
    return isValidCanadianPhone(this.contact_no);
  }

  formatEmergencyContactInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').substring(0, 10);
    let formatted = '';
    if (digits.length > 6) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3, 6)}-${digits.substring(6)}`;
    } else if (digits.length > 3) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3)}`;
    } else if (digits.length > 0) {
      formatted = `(${digits}`;
    }
    input.value = formatted;
    this.emergency_contact = formatted;
  }

  isEmergencyContactValid(): boolean {
    return isValidCanadianPhone(this.emergency_contact);
  }

  formatDoctorContactInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').substring(0, 10);
    let formatted = '';
    if (digits.length > 6) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3, 6)}-${digits.substring(6)}`;
    } else if (digits.length > 3) {
      formatted = `(${digits.substring(0, 3)}) ${digits.substring(3)}`;
    } else if (digits.length > 0) {
      formatted = `(${digits}`;
    }
    input.value = formatted;
    this.doctor_contact_no = formatted;
  }

  isDoctorContactValid(): boolean {
    return !this.doctor_contact_no || isValidCanadianPhone(this.doctor_contact_no);
  }

  /** Allow only digits 0-9; optionally enforce max length. Call from (keypress). */
  onlyNumeric(event: KeyboardEvent, maxLength?: number): void {
    const el = event.target as HTMLInputElement;
    const key = event.key;
    if (['Backspace', 'Tab', 'End', 'Home', 'ArrowLeft', 'ArrowRight', 'Delete'].indexOf(key) !== -1) return;
    if (event.ctrlKey || event.metaKey) {
      if (key === 'a' || key === 'c' || key === 'v' || key === 'x') return;
    }
    if (!/^\d$/.test(key)) {
      event.preventDefault();
      return;
    }
    if (maxLength != null && el && (el.value?.length || 0) >= maxLength) {
      event.preventDefault();
    }
  }

  /** Allow only letters and single spaces. Call from (keypress). */
  onlyLettersAndSpaces(event: KeyboardEvent): void {
    const key = event.key;
    if (['Backspace', 'Tab', 'End', 'Home', 'ArrowLeft', 'ArrowRight', 'Delete', ' '].indexOf(key) !== -1) return;
    if (event.ctrlKey || event.metaKey) return;
    if (!/^[a-zA-Z]$/.test(key)) event.preventDefault();
  }

  /** Allow only A–Z, a–z, 0–9. Call from (keypress). */
  onlyAlphanumeric(event: KeyboardEvent, maxLength?: number): void {
    const el = event.target as HTMLInputElement;
    const key = event.key;
    if (['Backspace', 'Tab', 'End', 'Home', 'ArrowLeft', 'ArrowRight', 'Delete'].indexOf(key) !== -1) return;
    if (event.ctrlKey || event.metaKey) return;
    if (!/^[a-zA-Z0-9]$/.test(key)) {
      event.preventDefault();
      return;
    }
    if (maxLength != null && el && (el.value?.length || 0) >= maxLength) event.preventDefault();
  }

  /** Restrict paste to digits only; optional maxLength. Dispatches input so ngModel updates. */
  pasteOnlyNumeric(event: ClipboardEvent, maxLength?: number): void {
    const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '');
    const el = event.target as HTMLInputElement;
    if (!el) return;
    const current = el.value || '';
    const start = el.selectionStart ?? current.length;
    const end = el.selectionEnd ?? current.length;
    const insert = maxLength != null ? pasted.slice(0, Math.max(0, maxLength - current.length + (end - start))) : pasted;
    const newVal = (current.slice(0, start) + insert + current.slice(end)).replace(/\D/g, '');
    const final = maxLength != null ? newVal.slice(0, maxLength) : newVal;
    event.preventDefault();
    el.value = final;
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /** Uppercase for PAN. */
  toUpperCase(event: Event, field: 'pan'): void {
    const el = event.target as HTMLInputElement;
    if (!el || !el.value) return;
    const upper = el.value.toUpperCase();
    if (el.value !== upper) {
      el.value = upper;
      if (field === 'pan') this.pan = upper;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  formatPostalInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (!el?.value) return;
    const v = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    let formatted = el.value;
    if (v.length >= 6) {
      formatted = `${v.slice(0, 3)} ${v.slice(3, 6)}`.trim();
    } else if (v.length > 3) {
      formatted = `${v.slice(0, 3)} ${v.slice(3)}`.trim();
    } else {
      formatted = v;
    }
    this.permanent_pincode = formatted;
    if (el.value !== formatted) {
      el.value = formatted;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  isCanadaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'Canada';
  }

  isIndiaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'India';
  }

  /** Postal-code HTML pattern by country: Canada -> CA postal, India -> 6-digit PIN, others -> lenient (1–12 alnum/space/dash). */
  getPincodePattern(): string {
    if (this.isCanadaCountry(this.permanent_country)) {
      return this.postalCaHtmlPattern;
    }
    if (this.isIndiaCountry(this.permanent_country)) {
      return this.pinInHtmlPattern;
    }
    return '^[A-Za-z0-9][A-Za-z0-9 -]{0,11}$';
  }

  onPermanentCountryChange(): void {
    if (this.isCanadaCountry(this.permanent_country)) {
      this.permanent_state = '';
      if (this.epf_app === 'Yes' || this.esic_app === 'Yes') {
        this.epf_app = 'No';
        this.esic_app = 'No';
        this.pf_no = '';
        this.esic_no = '';
      }
      this.pan = '';
    }
  }

  last_Emp_code = '';
  getLastEmployee() {
    this.service
      .get('/hr/employee.php?type=get_last_emp_code')
      .pipe(catchError(() => of({})))
      .subscribe((response: any) => {
        this.last_Emp_code = (response && response['emp_id']) != null ? String(response['emp_id']) : '';
      });
  }

  pending_employees: any[] = [];
  getCandidates() {
    return new Promise((res) => {
      this.service
        .get('hr/candidate.php?type=getPendingJoiningCandidates&department_name=')
        .pipe(catchError(() => of([])))
        .subscribe((response: any) => {
          this.pending_employees = Array.isArray(response) ? response : [];
          res(this.pending_employees);
        });
    });
  }


  docfile: File | null = null;
  resumeFile: File | null = null;
  idCopyFile: File | null = null;

  onFileChanged8(event: Event) {
    const input = (event.target as HTMLInputElement);
    this.docfile = input?.files?.[0] ?? null;
  }

  onResumeFileChanged(event: Event) {
    const input = event.target as HTMLInputElement;
    this.resumeFile = input?.files?.[0] ?? null;
  }

  onIdCopyFileChanged(event: Event) {
    const input = event.target as HTMLInputElement;
    this.idCopyFile = input?.files?.[0] ?? null;
  }

  uploadPresetDocument(documentName: string, file: File | null) {
    if (!this.emp_id) {
      alertify.error('Please enter Employee Code before uploading documents.');
      return;
    }
    if (!file) {
      alertify.error('Please select a file to upload.');
      return;
    }

    const uploadData = new FormData();
    uploadData.append('documentName', documentName);
    uploadData.append('emp_id', this.emp_id);
    uploadData.append('doc', file, file.name);

    this.service.postForm('hr/employee.php?type=add_doc', uploadData).subscribe({
      next: (httpResponse) => {
        const parsed = this.parseJsonStatus(httpResponse.body || '');
        if (parsed.status === 'success') {
          alertify.success(documentName + ' uploaded successfully');
          if (documentName === 'Resume') {
            this.resumeFile = null;
          } else if (documentName === 'ID Copy') {
            this.idCopyFile = null;
          }
          this.getUploadedDocByEmpId(this.emp_id);
        } else {
          alertify.error(parsed.status || 'Document upload failed');
        }
      },
      error: () => {
        alertify.error('Document upload failed');
      }
    });
  }

  emp_id = '';

  documents: any[] = [];

    addDocuments(documentForm: NgForm) {
      if (!this.emp_id) {
        alertify.error('Please enter Employee Code before uploading documents.');
        return;
      }
      if (!documentForm.valid) {
        alertify.error('All fields are required!');
        return;
      }
      if (!this.docfile) {
        alertify.error('Please select a file to upload.');
        return;
      }

      const temp = documentForm.value;
      const uploadData = new FormData();
      uploadData.append('documentName', temp['documentName']);
      uploadData.append('emp_id', this.emp_id);
      uploadData.append('doc', this.docfile, this.docfile.name);

      this.service.postForm('hr/employee.php?type=add_doc', uploadData).subscribe({
        next: (httpResponse) => {
          const parsed = this.parseJsonStatus(httpResponse.body || '');
          if (parsed.status === 'success') {
            documentForm.resetForm();
            this.docfile = null;
            alertify.success('Doc Added Successfully');
            this.getUploadedDocByEmpId(this.emp_id);
          } else {
            alertify.error(parsed.status || 'Document upload failed');
          }
        },
        error: () => {
          alertify.error('Document upload failed');
        }
      });
    }


  getUploadedDocByEmpId(emp_id: string) {
    this.service
      .get('hr/employee.php?type=getUploadedDocByEmpId&emp_idForDoc=' + encodeURIComponent(emp_id || ''))
      .pipe(catchError(() => of([])))
      .subscribe((response: any) => {
        this.documents = Array.isArray(response) ? response : [];
      });
  }
 

  deleteDoc(index) {
    this.documents.splice(index, 1);
  }

  selectedResult =[];
  
  viewDoc(url) {
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }
 
  Qualifications: any[] = [];
  getQualifications() {
    this.service
      .get('common.php?type=getQualifications')
      .pipe(catchError(() => of([])))
      .subscribe((response: any) => {
        this.Qualifications = Array.isArray(response) ? response : [];
      });
  }

  
  academics = [];
  addAcademic(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    const temp = { ...data.value };
    temp.year_from = toIsoDate(temp.year_from) || temp.year_from;
    temp.year_to = toIsoDate(temp.year_to) || temp.year_to;
    this.academics.push(temp);
    data.resetForm();
  }
  delAcademic(index) {
    this.academics.splice(index, 1);
  }



  employeement =[];
  addEmployment(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    const temp = { ...data.value };
    temp.year_from = toIsoDate(temp.year_from) || temp.year_from;
    temp.year_to = toIsoDate(temp.year_to) || temp.year_to;
    this.employeement.push(temp);
    data.resetForm();
  }

  delEmployment(index) {
    this.employeement.splice(index, 1);
  }

 
  languages = [];
  addLanguage(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.languages.push(temp);
    data.resetForm();
  }

  delLanguage(index) {
    this.languages.splice(index, 1);
  }

 
  private readonly REQUIRED_FIELD_LABELS: { [key: string]: string } = {
    employement_type: 'Employment Source',
    emp_id: 'Employee Code',
    selected_candidate: 'Select Candidate',
    employee_type: 'Employee Type',
    employee_category: 'Employee Category',
    firstname: 'First Name',
    lastname: 'Last Name',
    contact_no: 'Contact No',
    emergency_contact: 'Emergency Contact Number',
    emergency_contact_name: 'Emergency Contact Name',
    emergency_contact_relation: 'Emergency Contact Relation',
    department: 'Department',
    designation: 'Designation',
    joining_status: 'Joining Status',
    gender: 'Gender',
    birthdate: 'Date Of Birth',
    joining_date: 'Joining Date',
    emp_level: 'Employee Level',
    adhar: 'Passport / Driver\'s License No',
    sin: 'SIN',
    handicap: 'Handicap',
    isinduction: 'Induction Training',
    epf_app: 'EPF Applicable',
    pf_no: 'UAN No',
    esic_app: 'ESIC Applicable',
    esic_no: 'ESIC No',
    permanent_flat: 'Permanent Address - Flat/House No/Area',
    permanent_country: 'Permanent Address - Country',
    permanent_state: 'Permanent Address - Province',
    permanent_city: 'Permanent Address - City',
    permanent_pincode: 'Permanent Address - Postal Code',
    acc_no: 'Account #',
    acc_type: 'Account Type',
    bank_name: 'Bank Name',
    transit_no: 'Transit #',
    institution_no: 'Institution #',
  };

  private getFormFieldValue(form: NgForm, name: string): string {
    const control = form.form?.controls?.[name];
    if (control?.value != null && String(control.value).trim() !== '') {
      return String(control.value).trim();
    }
    const componentVal = (this as Record<string, unknown>)[name];
    return componentVal != null ? String(componentVal).trim() : '';
  }

  private isBankRequired(form: NgForm): boolean {
    const country = (form.value?.permanent_country ?? this.permanent_country ?? 'Canada') as string;
    return this.isCanadaCountry(country) || this.isBank;
  }

  getMissingRequiredFields(form: NgForm): string[] {
    const missing: string[] = [];
    const controls = form.form?.controls;
    if (!controls) return missing;
    form.form.markAllAsTouched();

    const country = (form.value?.permanent_country ?? this.permanent_country ?? 'Canada') as string;
    const canada = this.isCanadaCountry(country);
    const bankRequired = this.isBankRequired(form);
    const indiaPayrollFields = ['epf_app', 'esic_app', 'pf_no', 'esic_no'];

    Object.keys(this.REQUIRED_FIELD_LABELS).forEach(name => {
      if (canada && indiaPayrollFields.indexOf(name) !== -1) {
        return;
      }
      if (!canada && name === 'sin') {
        return;
      }
      const c = controls[name];
      const label = this.REQUIRED_FIELD_LABELS[name];
      if (name === 'contact_no') {
        const phone = String(c?.value ?? this.contact_no ?? '').trim();
        if (!phone) {
          missing.push(label);
        } else if (!isValidCanadianPhone(phone)) {
          missing.push('Contact No (valid Canadian phone, e.g. (416) 555-1234)');
        }
        return;
      }
      if (name === 'emergency_contact') {
        const phone = String(c?.value ?? this.emergency_contact ?? '').trim();
        if (!phone) {
          missing.push(label);
        } else if (!isValidCanadianPhone(phone)) {
          missing.push('Emergency Contact Number (valid Canadian phone, e.g. (416) 555-1234)');
        }
        return;
      }
      if (name === 'doctor_contact_no') {
        const phone = String(c?.value ?? this.doctor_contact_no ?? '').trim();
        if (phone && !isValidCanadianPhone(phone)) {
          missing.push('Doctor Contact No (valid Canadian phone, e.g. (416) 555-1234)');
        }
        return;
      }
      if (name === 'emergency_contact_email') {
        if (c?.invalid && c.errors?.['pattern']) {
          missing.push('Emergency Contact Email (valid email address)');
        }
        return;
      }
      if (name === 'sin') {
        if (canada) {
          const sinVal = String(c?.value ?? this.sin ?? '').trim();
          if (!sinVal) {
            missing.push(label);
          } else if (!/^[0-9]{9}$/.test(sinVal)) {
            missing.push('SIN (exactly 9 digits)');
          }
        }
        return;
      }
      if (name === 'permanent_pincode') {
        const pc = String(c?.value ?? this.permanent_pincode ?? '').trim();
        const india = this.isIndiaCountry(country);
        if (!pc) {
          missing.push(label);
        } else if (canada && !POSTAL_CA_PATTERN.test(pc)) {
          missing.push(label);
        } else if (india && !PINCODE_IN_PATTERN.test(pc)) {
          missing.push(label);
        }
        // Other countries: any non-empty postal/ZIP code is accepted.
        return;
      }
      if (name === 'selected_candidate') {
        return;
      }
      if (name === 'pf_no') {
        if (!canada && this.epf_app === 'Yes' && (!c || c.invalid)) missing.push(label);
        return;
      }
      if (name === 'esic_no') {
        if (!canada && this.esic_app === 'Yes' && (!c || c.invalid)) missing.push(label);
        return;
      }
      if (['acc_no', 'acc_type', 'bank_name', 'transit_no', 'institution_no'].indexOf(name) !== -1) {
        if (!bankRequired) {
          return;
        }
        const val = this.getFormFieldValue(form, name);
        if (name === 'bank_name') {
          if (!val || val === CANADIAN_BANK_ADD_NEW) {
            missing.push(label);
          }
          return;
        }
        if (name === 'acc_no') {
          if (!val) {
            missing.push(label);
          } else if (!/^[0-9]{5,20}$/.test(val)) {
            missing.push('Account # (5–20 digits)');
          }
          return;
        }
        if (!val) {
          missing.push(label);
        }
        return;
      }
      if (name === 'birthdate' || name === 'joining_date') {
        const display = name === 'birthdate' ? this.birthdateDisplay : this.joiningDateDisplay;
        if (!parseDisplayToIso(display)) {
          missing.push(label);
        } else if (c?.errors?.['underAge']) {
          missing.push(label);
        } else if (c?.errors?.['futureDate']) {
          missing.push(label);
        }
        return;
      }
      if (c && c.invalid && (c.errors?.['required'] || c.errors?.['minlength'] || c.errors?.['maxlength'] || c.errors?.['pattern'] || c.errors?.['underAge'] || c.errors?.['max'] || c.errors?.['futureDate'])) {
        missing.push(label);
      }
    });

    return missing;
  }

  private parseJsonStatus(text: string): { status?: string } {
    try {
      const jsonStart = text.indexOf('{');
      return JSON.parse(jsonStart >= 0 ? text.substring(jsonStart) : text);
    } catch {
      return { status: (text || 'empty response').substring(0, 300) };
    }
  }

  saveEmployee(form: NgForm) {
    this.submitAttempted = true;
    const missing = this.getMissingRequiredFields(form);
    if (missing.length > 0) {
      const list = missing.join(', ');
      alertify.error('Please fill the following required field(s): ' + list);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    const uploadData = new FormData();
    const temp = { ...form.value };
    temp['birthdate'] = toIsoDate(this.birthdateDisplay || temp['birthdate']);
    temp['joining_date'] = toIsoDate(this.joiningDateDisplay || temp['joining_date']);
    temp['operator_category'] = 'Staff';
    temp['emp_work_type'] = temp['employee_type'] || this.employee_type;
    temp['employee_type'] = temp['employee_category'] || this.employee_category;
    delete temp['employee_category'];
    const country = temp['permanent_country'] ?? this.permanent_country;
    const emerg = formatCanadianPhone(temp['emergency_contact'] ?? this.emergency_contact);
    if (emerg) {
      temp['branch_name'] = emerg;
    }
    temp['emergency_contact_name'] = temp['emergency_contact_name'] || this.emergency_contact_name;
    temp['emergency_contact_relation'] = temp['emergency_contact_relation'] || this.emergency_contact_relation;
    temp['emergency_contact_email'] = temp['emergency_contact_email'] || this.emergency_contact_email;
    const doctorPhone = formatCanadianPhone(temp['doctor_contact_no'] ?? this.doctor_contact_no);
    if (doctorPhone) {
      temp['doctor_contact_no'] = doctorPhone;
    }
    if (this.isCanadaCountry(country)) {
      // Server WAF blocks parentheses in contact_no — store digits only (e.g. 4165551234).
      temp['contact_no'] = stripToCanadianPhoneDigits(temp['contact_no'] ?? this.contact_no);
      if (this.sin) {
        temp['pan'] = this.sin;
      }
    }
    if (this.isBankRequired(form)) {
      const bankName = (temp['bank_name'] || this.bank_name || '').trim();
      temp['bank_name'] = bankName === CANADIAN_BANK_ADD_NEW ? '' : bankName;
      temp['acc_no'] = temp['acc_no'] || this.acc_no;
      temp['acc_type'] = temp['acc_type'] || this.acc_type || 'Saving Account';
      const transit = String(temp['transit_no'] || this.transit_no || '').replace(/\D/g, '').slice(0, 5);
      const institution = String(temp['institution_no'] || this.institution_no || '').replace(/\D/g, '').slice(0, 3);
      if (transit && institution) {
        temp['ifsc_neft'] = (`${transit}-${institution}`).slice(0, 20);
      }
    }
    delete temp['emergency_contact'];
    delete temp['transit_no'];
    delete temp['institution_no'];

    Object.keys(EMPLOYEE_SAVE_FIELD_DEFAULTS).forEach((key) => {
      const value = temp[key];
      uploadData.set(key, value != null && value !== '' ? String(value) : EMPLOYEE_SAVE_FIELD_DEFAULTS[key]);
    });

    this.service.postForm('hr/employee.php?type=saveEmployee', uploadData)
        .subscribe({
          next: (httpResponse) => {
            const text = httpResponse.body || '';
            let response: { status?: string };
            try {
              const jsonStart = text.indexOf('{');
              response = JSON.parse(jsonStart >= 0 ? text.substring(jsonStart) : text);
            } catch {
              alertify.error('Server error: ' + (text || 'empty response').substring(0, 300));
              return;
            }
            if (response['status'] === 'success') {
              this.formDraft.clearDraft(this.draftKey);
              this.draftRestored = false;
              this.draftSavedAt = null;
              form.resetForm();
              alertify.success('Record Added Successfully');
              this.router.navigate(['/hr']);
            } else {
              alertify.error('Error: ' + response['status']);
            }
          },
          error: (err) => {
            const status = err?.status || '';
            let detail = '';
            if (typeof err?.error === 'string' && err.error.trim()) {
              detail = err.error.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
            } else if (err?.message) {
              detail = err.message;
            }
            alertify.error('Save failed (' + status + '): ' + detail.substring(0, 400));
          },
        });
    }








  // birthdate ;
  // calculateAge(birthday) {
  //   birthday = new Date(birthday);
  //   if (!isNaN(birthday)) {
  //     var ageDifMs = Date.now() - birthday.getTime();
  //     var ageDate = new Date(ageDifMs); // miliseconds from epoch
  //     if (Math.abs(ageDate.getUTCFullYear() - 1970) >= 18) {
  //     } else {
  //       alertify.error('Under 18 Age Employee Not Acceptable!');
  //     }
  //     this.birthdate = Math.abs(ageDate.getUTCFullYear() - 1970);
  //   }
  // }
  birthdate: string | null = null;
  age: number | null = null;

  onDateDisplayInput(event: Event, field?: 'birthdate' | 'joining_date'): void {
    const el = event.target as HTMLInputElement;
    const masked = maskDateDisplayInput(el.value);
    if (field === 'birthdate') {
      this.birthdateDisplay = masked;
    } else if (field === 'joining_date') {
      this.joiningDateDisplay = masked;
    }
    if (el.value !== masked) {
      el.value = masked;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  onBirthdateDisplayBlur(): void {
    this.birthdateDisplay = formatIsoToDisplay(this.birthdateDisplay);
    this.calculateAge(this.birthdateDisplay);
    this.validateDateNotFuture('birthdate', this.birthdateDisplay);
  }

  onJoiningDateDisplayBlur(): void {
    this.joiningDateDisplay = formatIsoToDisplay(this.joiningDateDisplay);
    this.validateDateNotFuture('joining_date', this.joiningDateDisplay);
  }

  private validateDateNotFuture(controlName: 'birthdate' | 'joining_date', display: string): void {
    const ctrl = this.employeeFormRef?.form?.controls?.[controlName];
    if (!ctrl) {
      return;
    }
    const iso = parseDisplayToIso(display);
    const err = { ...(ctrl.errors || {}) };
    delete err['futureDate'];
    if (iso) {
      const d = parseToLocalDate(iso);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (d && d > today) {
        err['futureDate'] = true;
      }
    }
    ctrl.setErrors(Object.keys(err).length ? err : null);
  }

  calculateAge(birthdayDisplay: string | null) {
    const ctrl = this.employeeFormRef?.form?.controls?.['birthdate'];
    const iso = parseDisplayToIso(birthdayDisplay);
    this.birthdate = iso;
    if (!iso) {
      this.age = null;
      if (ctrl?.errors?.['underAge']) {
        const err = { ...ctrl.errors };
        delete err['underAge'];
        ctrl.setErrors(Object.keys(err).length ? err : null);
      }
      return;
    }

    const birthDate = parseToLocalDate(iso);
    if (!birthDate) {
      return;
    }
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }

    this.age = age;

    if (ctrl) {
      const err = { ...(ctrl.errors || {}) };
      delete err['underAge'];
      if (age < 18) {
        err['underAge'] = true;
        alertify.error('Date Of Birth not accepted: Employee must have completed 18 years of age.');
      }
      ctrl.setErrors(Object.keys(err).length ? err : null);
    }
  }

  formatAcademicDate(value: unknown): string {
    return formatIsoToDisplay(value);
  }


 
  selectedFile2: File | null = null;
  onFileChanged3(event: Event) {
    const input = (event.target as HTMLInputElement);
    this.selectedFile2 = input?.files?.[0] ?? null;
  }
 

  permanent_flat = '';
  permanent_country = 'Canada';
  permanent_state = '';
  permanent_city = '';
  permanent_pincode = '';

  isBank: boolean = true;
  isacadamic: boolean = false;  
  isLanguage: boolean= false;
  isEmpHistory: boolean= false;
  isDocuments: boolean= false;


  showBank() {
    this.isBank = !this.isBank;
  }

  private getBankStorageKey(): string {
    const plantId = localStorage.getItem('plant_id') || 'default';
    return `hr_canadian_banks_custom_${plantId}`;
  }

  loadCanadianBanks(): void {
    let custom: string[] = [];
    try {
      const raw = localStorage.getItem(this.getBankStorageKey());
      const parsed = raw ? JSON.parse(raw) : [];
      custom = Array.isArray(parsed) ? parsed.filter((b) => typeof b === 'string' && b.trim()) : [];
    } catch {
      custom = [];
    }
    this.canadianBanks = Array.from(new Set([...DEFAULT_CANADIAN_BANKS, ...custom]))
      .sort((a, b) => a.localeCompare(b));
  }

  onBankChange(value: string): void {
    if (value === CANADIAN_BANK_ADD_NEW) {
      this.bank_name = '';
      this.newBankName = '';
      this.showAddBankModal = true;
    }
  }

  closeAddBankModal(): void {
    this.showAddBankModal = false;
    this.newBankName = '';
  }

  saveNewBank(): void {
    const name = (this.newBankName || '').trim();
    if (!name) {
      alertify.error('Bank name is required.');
      return;
    }
    const exists = this.canadianBanks.some((b) => b.toLowerCase() === name.toLowerCase());
    if (exists) {
      this.bank_name = this.canadianBanks.find((b) => b.toLowerCase() === name.toLowerCase()) || name;
      this.closeAddBankModal();
      return;
    }
    try {
      const key = this.getBankStorageKey();
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      const custom: string[] = Array.isArray(parsed) ? parsed : [];
      custom.push(name);
      localStorage.setItem(key, JSON.stringify(custom));
    } catch {
      // ignore storage errors
    }
    this.loadCanadianBanks();
    this.bank_name = name;
    this.closeAddBankModal();
    alertify.success('Bank added successfully');
  }

  acadamicShow() {
    this.isacadamic = !this.isacadamic;
  }

  showLanguage() {
    this.isLanguage = !this.isLanguage;
  }

  showEmpHistory() {
    this.isEmpHistory = !this.isEmpHistory;
  }

  documentsShow() {
    this.isDocuments = !this.isDocuments;
  }
 


  countries: string[] = [
    "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
    "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia",
    "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas",
    "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium",
    "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia",
    "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
    "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
    "Chile", "China", "Colombia", "Costa Rica", "Croatia",
    "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic",
    "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia",
    "Fiji", "Finland", "France", "Germany", "Greece",
    "Hong Kong", "Hungary", "Iceland", "India", "Indonesia",
    "Iran", "Iraq", "Ireland", "Israel", "Italy",
    "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
    "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg",
    "Malaysia", "Maldives", "Malta", "Mexico", "Monaco",
    "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan",
    "Panama", "Peru", "Philippines", "Poland", "Portugal",
    "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore",
    "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan",
    "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey",
    "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America",
    "Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
  ];



  canadianProvinces: string[] = [
    'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
    'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
    'Quebec', 'Saskatchewan', 'Yukon'
  ];

  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam",
    "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi",
    "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir",
    "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram",
    "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
    "Uttar Pradesh","Uttarakhand","West Bengal"
  ];

 

}



