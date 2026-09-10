import { Component, OnInit, ViewChild } from '@angular/core';
import { NgForm, NgModel } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { DatePipe } from '@angular/common';
import {
  getCountryPhoneDialExample,
  isValidCountryPhoneWithDialCode,
} from 'src/app/shared/validators/country-phone.validator';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  @ViewChild('contactNo') contactNoField: NgModel;
  isView = false;
  isNew = false;
  

  birthdate: string = ''; // stores actual date of birth
  age: number | null = null; // stores calculated age
  today: string = '';


  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDepartments();
    this.getCandidate();
    this.get_rights();
    this.today = new Date().toISOString().split('T')[0];

  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights;
 
  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')  ).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }
  

  departments: any[] = [];
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }


  results;
  getCandidate() {
    this.service.get('hr/candidate.php?type=getCandidateForInterviewScheduleAndAllocation').subscribe((response) => {
        this.results = response;
    });
  }

  designations: any[] = [];
  department = '';
  interviewerDesignation = '';
  interviewerId = '';

  getDesignationLabel(item: any): string {
    if (!item) {
      return '';
    }
    if (typeof item === 'string') {
      return item.trim();
    }
    return String(item.designation || item.designation_heading || '').trim();
  }

  loadDesignationsForDepartment(department: string): void {
    this.designations = [];
    if (!department || String(department).trim() === '') {
      return;
    }
    this.service
      .get('hr/employee.php?type=getDesignationsByDepartment&department_name=' + encodeURIComponent(department))
      .subscribe((response: any) => {
        this.designations = Array.isArray(response) ? response : [];
        if (this.designations.length === 0 && Array.isArray(this.departments)) {
          const dept = this.departments.find((d: any) => d?.department_name === department);
          this.designations = dept && Array.isArray(dept.designations) ? dept.designations : [];
        }
        if (this.designations.length === 0) {
          this.loadDesignationsFromEmployees(department);
        }
      });
  }

  private loadDesignationsFromEmployees(department: string): void {
    this.service
      .get('hr/employee.php?type=getDepartmentEmployees&department_name=' + encodeURIComponent(department))
      .subscribe((response: any) => {
        const emps = Array.isArray(response) ? response : [];
        const labels = new Set<string>();
        emps.forEach((emp: any) => {
          const label = String(emp?.designation || '').trim();
          if (label) {
            labels.add(label);
          }
        });
        this.designations = Array.from(labels).map((designation) => ({ designation }));
      });
  }

  getDesignation(department: string) {
    this.loadDesignationsForDepartment(department);
  }

  onInterviewerDeptChange(): void {
    this.interviewerDesignation = '';
    this.interviewerId = '';
    this.selectedEmployee = null;
    this.loadDesignationsForDepartment(this.interviewerDept);
    this.loadEmployeesForDepartment(this.interviewerDept);
  }

  onInterviewerDesignationChange(designation: string): void {
    this.interviewerId = '';
    this.selectedEmployee = null;
    this.loadEmployeesForDepartment(this.interviewerDept, designation);
  }
 

  employees: any[] = [];
  interviewerDept = '';

  loadEmployeesForDepartment(department: string, designation?: string): void {
    if (!department || String(department).trim() === '') {
      this.employees = [];
      return;
    }
    let url =
      'hr/employee.php?type=getDepartmentEmployees&department_name=' +
      encodeURIComponent(department);
    if (designation && String(designation).trim() !== '') {
      url += '&designation=' + encodeURIComponent(designation);
    }
    this.service.get(url).subscribe((response: any) => {
      this.employees = Array.isArray(response) ? response : [];
    });
  }

  getEmployees(designation: string) {
    this.loadEmployeesForDepartment(this.interviewerDept, designation);
  }

 
  calculateAge(birthday: string) {
    if (!birthday) return;

    const dob = new Date(birthday);
    if (isNaN(dob.getTime())) return;

    const diff = Date.now() - dob.getTime();
    const ageDt = new Date(diff);

    this.age = Math.abs(ageDt.getUTCFullYear() - 1970);

    if (this.age < 18) {
      alertify.error('Under 18 Age Employee Not Acceptable!');
      this.birthdate = ''; // clear invalid date
      this.age = null;
    }
  }
 
  resume: File;
  contact_no = '';
  onFileChanged3(event) {
    this.resume = event.target.files[0];
  }
 
  permanent_flat = '';
  permanent_country = 'India';
  permanent_state = '';
  permanent_city = '';
  permanent_pincode = '';

  contactPhoneHint(): string {
    return 'e.g. ' + this.contactPhoneExample();
  }

  contactPhoneExample(): string {
    return getCountryPhoneDialExample(this.permanent_country);
  }

  isContactPhoneValid(): boolean {
    return isValidCountryPhoneWithDialCode(this.contact_no, this.permanent_country);
  }

  onCountryChange(): void {
    this.permanent_state = '';
    this.syncContactPhoneValidity();
  }

  syncContactPhoneValidity(): void {
    const ctrl = this.contactNoField?.control;
    if (!ctrl) {
      return;
    }
    const raw = String(this.contact_no || '').trim();
    if (!raw) {
      if (ctrl.errors?.['countryPhone']) {
        const next = { ...ctrl.errors };
        delete next['countryPhone'];
        ctrl.setErrors(Object.keys(next).length ? next : null);
      }
      return;
    }
    if (this.isContactPhoneValid()) {
      if (ctrl.errors?.['countryPhone']) {
        const next = { ...ctrl.errors };
        delete next['countryPhone'];
        ctrl.setErrors(Object.keys(next).length ? next : null);
      }
      return;
    }
    ctrl.setErrors({ ...(ctrl.errors || {}), countryPhone: true });
    ctrl.markAsTouched();
  }

  countries: string[] = [ "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Chile", "China", "Colombia", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia", "Fiji", "Finland", "France", "Germany", "Greece", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg", "Malaysia", "Maldives", "Malta", "Mexico", "Monaco", "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands", "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan", "Panama", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore", "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain", "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan", "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America","Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"];
  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam", "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi", "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir", "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram", "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura", "Uttar Pradesh","Uttarakhand","West Bengal" ];

  private readonly fieldLabels: Record<string, string> = {
    firstname: 'First Name',
    lastname: 'Last Name',
    contact_no: 'Contact No',
    emp_email: 'Email Id',
    qualification: 'Qualification',
    department: 'Department Applied For',
    gender: 'Gender',
    birthdate: 'Date Of Birth',
    resume: 'Upload Resume',
    permanent_flat: 'Address',
    permanent_city: 'City',
    permanent_pincode: 'Postal Code',
    permanent_country: 'Country',
    permanent_state: 'Province',
    interview_date: 'Interview Date',
    interview_time_from: 'Interview Time (From)',
    interview_time_to: 'Interview Time (To)',
    venue: 'Place / Venue',
    venue_other: 'Place Name',
    designation: 'Designation',
    interviewerDept: 'Interviewer Department',
    interviewerDesignation: 'Interviewer Designation',
    interviewerId: 'Interviewer Employee',
    primaryROundRemark: 'Remark',
  };

  private fieldLabel(name: string): string {
    const evalMatch = name.match(/^evalParameter(\d+)$/);
    if (evalMatch) {
      return 'Evaluation Rating (row ' + (Number(evalMatch[1]) + 1) + ')';
    }
    if (this.fieldLabels[name]) {
      return this.fieldLabels[name];
    }
    return name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  }

  private getFieldErrorLabel(name: string, control: any): string {
    const label = this.fieldLabel(name);
    const errors = control?.errors;
    if (!errors) {
      return label;
    }
    if (errors['pattern'] && name === 'emp_email') {
      return label + ' (enter a valid email)';
    }
    if (errors['pattern']) {
      return label + ' (invalid format)';
    }
    if (errors['minlength'] || errors['maxlength']) {
      return label + ' (invalid length)';
    }
    if (name === 'contact_no') {
      return label + ' (enter a valid number for ' + (this.permanent_country || 'the selected country') + ')';
    }
    if (errors['max']) {
      return label + ' (cannot be in the future)';
    }
    return label;
  }

  private getInvalidFields(form: NgForm, extraMissing: string[] = []): string[] {
    if (form?.form) {
      form.form.markAllAsTouched();
    }
    const missing = [...extraMissing];
    const controls = form?.controls || {};
    Object.keys(controls).forEach((name) => {
      const control = controls[name];
      if (control?.invalid) {
        missing.push(this.getFieldErrorLabel(name, control));
      }
    });
    return Array.from(new Set(missing));
  }

  private showFormValidationErrors(form: NgForm, extraMissing: string[] = []): boolean {
    const missing = this.getInvalidFields(form, extraMissing);
    if (missing.length > 0) {
      alertify.error('Please fill the following field(s): ' + missing.join(', '));
      return true;
    }
    return false;
  }
 
 
  saveForm(data: NgForm) {
    const extraMissing: string[] = [];
    if (!this.resume) {
      extraMissing.push('Upload Resume');
    }
    if (this.contact_no && !this.isContactPhoneValid()) {
      extraMissing.push('Contact No (valid number for ' + (this.permanent_country || 'the selected country') + ')');
    }
    if (this.showFormValidationErrors(data, extraMissing)) {
      return;
    }

    let temp = data.value;

    const formData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      formData.append(key, value);
    }

    formData.set('blood_group', '');
    formData.set('tempflat_no', temp['permanent_flat'] || '');
    formData.set('temp_country', temp['permanent_country'] || '');
    formData.set('temp_state', temp['permanent_state'] || '');
    formData.set('temp_city', temp['permanent_city'] || '');
    formData.set('temp_pincode', temp['permanent_pincode'] || '');

    if (this.resume !== undefined) {
      formData.append('resume', this.resume, this.resume.name);
    }
    
    this.service.post('hr/candidate.php?type=saveCandidate', formData).subscribe((response) => {
        if (response['status'] === 'success') {
          data.resetForm();
          this.getCandidate();
          this.selectedCandidate = [];
          alertify.success('Candidate Registration has been successful');
          this.isNew = false;
        } else {
          alertify.error(response['status']);
        }
      });
  }
 

  isScheduleInterview = false;
  selectedCandidate = [];

  scheduleInterview(data){
    this.selectedCandidate = data;
    this.isScheduleInterview = true;
  }


  isInterviewAllocated = false;
 
  allocateInterviewer(data){
    this.selectedCandidate = data;
    this.isInterviewAllocated = true;
    this.interviewerDept = '';
    this.interviewerDesignation = '';
    this.interviewerId = '';
    this.designations = [];
    this.employees = [];
    this.interviewers = [];
    this.selectedEmployee = null;
  }

  isPrimaryRoundHr = false;
 
  primaryRoundHr(data){
    this.selectedCandidate = data;
    this.isPrimaryRoundHr = true;
    this.interviewChecklistByType('Primary Round HR');
  }


  isTechnicalRound = false;
 
  viewTechnicalDetails(data){
    this.selectedCandidate = data;
    this.isTechnicalRound = true;
  }
 

  checklistData;
  interviewChecklistByType(checklistType){
    this.service.get('hr/candidate.php?type=interviewChecklistByType&checklistType='+checklistType).subscribe(Response=>{
      this.checklistData=Response;
    })
  }
 
  saveScheduleInterview(data: NgForm) {

    if (this.showFormValidationErrors(data)) {
      return;
    }
 
    let temp = data.value;
    temp['candidate_id'] = this.selectedCandidate['id'];

    if(temp['venue'] == 'Other'){
      temp['venue'] = temp['venue_other'] 
    }
 
    this.service.post('hr/candidate.php?type=saveScheduleInterview',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Candidate Interview Scedule Successfully');
          this.isScheduleInterview = false;
          data.resetForm();
          this.getCandidate();
          this.selectedCandidate = [];
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }


  interviewers = [];
 

  addInterview(data: NgForm){

    if (this.showFormValidationErrors(data)) {
      return;
    }

    if (!this.selectedEmployee) {
      alertify.error('Please select an employee from the list');
      return;
    }
    if (!this.interviewerDept) {
      alertify.error('Please select a department');
      return;
    }

    let temp = data.value;
    temp['interview_date'] = this.selectedCandidate['interview_date'];
    temp['interview_time'] = this.selectedCandidate['interview_time_from']+' - '+this.selectedCandidate['interview_time_to'];
    temp['interviewerName'] = this.selectedEmployee['firstname']+' '+this.selectedEmployee['lastname']+' '+'( '+this.selectedEmployee['emp_id']+' )';
    temp['interviewerDept'] = this.interviewerDept || temp['interviewerDept'];
    temp['interviewerDesignation'] = this.interviewerDesignation || temp['interviewerDesignation'];
    temp['venue'] = this.selectedCandidate['venue'];
    this.interviewers.push(temp);
    data.reset();
    this.interviewerDept = '';
    this.interviewerDesignation = '';
    this.interviewerId = '';
    this.designations = [];
    this.employees = [];
    this.selectedEmployee = null;
  }

 
  delInterviewer(ind){
    this.interviewers.splice(ind,1);
  }


  selectedEmployee: any = null;
  getSelectedEmployee(emp_id){
    if (!emp_id) {
      this.selectedEmployee = null;
      return;
    }
    const employee = (this.employees || []).find(emp => String(emp.emp_id) === String(emp_id));
    this.selectedEmployee = employee || null;
  }



  saveInterviewers() {

    if (this.interviewers?.length == 0){
      alertify.error('Add Interviewers!!!!');
      return;
    }
 
    let temp = {};
    temp['candidate_id'] = this.selectedCandidate['id'];
    temp['interviewers'] = this.interviewers;
 
    this.service.post('hr/candidate.php?type=saveInterviewers',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Candidate Interview Scedule Successfully');
          this.isInterviewAllocated = false;
          this.getCandidate();
          this.selectedCandidate = [];
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }

 
  savePrimaryRoundInterView(data: NgForm,status) {

    if (this.showFormValidationErrors(data)) {
      return;
    }
 
    let temp = data.value;
    temp['candidate_id'] = this.selectedCandidate['id'];
    temp['status'] = status;
    temp['primaryRoundChecklist'] = this.checklistData;
 
    this.service.post('hr/candidate.php?type=savePrimaryRoundInterView',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Candidate Interview Scedule Successfully');
          this.isPrimaryRoundHr = false;
          this.getCandidate();
          this.selectedCandidate = [];
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }


 
  viewResume(url) {
    url = this.service.url + '../..' + url;
    window.open(url, '_blank');
  }
 

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
 
    
 
  
 
 



}
