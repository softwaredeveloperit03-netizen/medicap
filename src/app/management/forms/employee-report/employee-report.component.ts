import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-employee-report',
  templateUrl: './employee-report.component.html'
})
export class EmployeeReportComponent implements OnInit {
  isInit = true;
  Qualifications;
  departments;
  sections;
  designations;
  isSpecialTerm;
  isTraining;
  isNewEmployee = false;
  employees;
  selectedFile2: File;
  isUploadPhoto;
  isUpdateEmployee = false;
  isViewEmployee = false;
  selectedEmployee;
  value = '12345';

  isSalaryAnnexture = false;
  list;
  isNew = false;
  ctc = 0;
  basic = 0;
  HRA = 0;
  Conveyance = 0;
  medical = 0;
  specialAllowance = 0;
  educationalAllowance = 0;
  PF = 0;
  ESIC = 0;
  PT = 0;
  Canteen = 0;
  Other = 0;
  bonus = 0;
  gross_total = 0;
  net_total = 0;
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';
  candidate_no;
  candidate_name;
  isSuspend = false;
  temp_id;
  search_employee;
  employees1 = [];
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getEmployees();
    this.getQualifications();
    this.getDepartments();
    this.getDesignation();
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployees')
    .subscribe(response => {
      this.employees = response;
      this.employees1 = this.employees;
    });
  }

  getQualifications() {
    this.service.get('hrDepartment.php?type=getApprovedQualifications')
    .subscribe(response => {
      this.Qualifications = response;
    });
  }
  getDepartments() {
    this.service.get('hrDepartment.php?type=getApprovedDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }
  getSection(department) {
    this.service.get('hrDepartment.php?type=getDepartmentSection&selectedDepartment='+department)
    .subscribe(response => {
      this.sections = response;
    });
  }
  getDesignation() {
    this.service.get('hrDepartment.php?type=getApprovedDesignations')
    .subscribe(response => {
      this.designations = response;
    });
  }

  saveEmployee(formData) {
    const temp = formData.value;
    if (temp['employee_name'].length <=2) {
      alert('Invalid Employee Name');
      let element1 = document.getElementById('employee_name') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('employee_name') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['joining_date'] === '') {
      alert('Joining Date is Required');
      return;
    }
    if (temp['mobile_no'].length <=10 && temp['mobile_no'].length >13) {
      alert('Invalid Contact No');
      let element1 = document.getElementById('joining_date') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('joining_date') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['email_id'] === '') {
      alert('Email ID is Required');
      let element1 = document.getElementById('email_id') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('email_id') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['department'] === '') {
      alert('Department is Required');
      let element1 = document.getElementById('department') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('department') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['designation'] === '') {
      alert('Section is Required');
      let element1 = document.getElementById('designation') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('designation') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['permanant_state'] === '') {
      alert('State is Required');
      let element1 = document.getElementById('permanant_state') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('permanant_state') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['permanant_district'] === '') {
      alert('District is Required');
      let element1 = document.getElementById('permanant_district') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('permanant_district') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['permanant_taluka'] === '') {
      alert('Taluka is Required');
      let element1 = document.getElementById('permanant_taluka') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('permanant_taluka') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    if (temp['address_permanent'] === '') {
      alert('Permanant Address is Required');
      let element1 = document.getElementById('address_permanent') as HTMLElement;
      element1.style.backgroundColor = "#e12200";
      element1.style.color = "#FFFFFF";
      return;
    } else {
      let element1 = document.getElementById('address_permanent') as HTMLElement;
      element1.style.backgroundColor = "#FFFFFF";
      element1.style.color = "#000000";
    }
    const uploadData = new FormData();
    if (this.isUploadPhoto === 1) {
      uploadData.append('userphoto', this.selectedFile2, this.selectedFile2.name);
    }
    uploadData.append('employee_name', formData.value.employee_name);
    uploadData.append('mobile_no', formData.value.mobile_no);
    uploadData.append('emergency_no', formData.value.emergency_no);
    uploadData.append('email_id', formData.value.email_id);
    uploadData.append('qualification', formData.value.qualification);
    uploadData.append('experience', formData.value.experience);
    uploadData.append('key_role', formData.value.key_role);
    uploadData.append('joining_date', formData.value.joining_date);
    uploadData.append('dob', formData.value.dob);
    uploadData.append('gender', formData.value.gender);
    uploadData.append('emergency_no', formData.value.emergency_no);
    uploadData.append('department', formData.value.department);
    uploadData.append('section', formData.value.section);
    uploadData.append('designation', formData.value.designation);
    uploadData.append('casual', formData.value.casual);
    uploadData.append('sick', formData.value.sick);
    uploadData.append('pl', formData.value.pl);
    uploadData.append('other', formData.value.other);
    uploadData.append('total', formData.value.total);
    uploadData.append('transport', formData.value.transport);
    uploadData.append('cantine', formData.value.cantine);
    uploadData.append('telephone', formData.value.telephone);
    uploadData.append('permanant_state', formData.value.permanant_state);
    uploadData.append('permanant_district', formData.value.permanant_district);
    uploadData.append('permanant_taluka', formData.value.permanant_taluka);
    uploadData.append('address_permanent', formData.value.address_permanent);
    uploadData.append('temporary_state', formData.value.temporary_state);
    uploadData.append('temporary_district', formData.value.temporary_district);
    uploadData.append('temporary_taluka', formData.value.temporary_taluka);
    uploadData.append('address_temporary', formData.value.address_temporary);
    uploadData.append('candidate_id', formData.value.candidate_id);
    
    this.service.post('hrDepartment.php?type=saveCurrentEmployee', uploadData)
    .subscribe(response => {
      if (response['status'] === 'success') {
        formData.resetForm();
        this.isNewEmployee = false;
        this.isSalaryAnnexture = true;
        this.isInit = false;
        this.candidate_no = response['emp_id'];
        this.getEmployees();
        alert('Successfully Added');
      } else {
        alert('An error has occurred, Please try again!');
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

  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
    this.isUploadPhoto = 1;
  }

  checkSelection(value) {
    if (value === 'select on Special Term') {
      this.isSpecialTerm = true;
    } else {
      this.isSpecialTerm = false;
    }
  }

  viewEmployee(index) {
    this.selectedEmployee = this.employees[index];
    this.isViewEmployee = true;
    this.isInit = false;
  }

  updateEmployees(index) {
    this.selectedEmployee = this.employees[index];
    this.isUpdateEmployee = true;
    this.isInit = false;
  }

  closeUpdateForm() {
    this.isViewEmployee = false;
    this.isUpdateEmployee = false;
    this.isInit = true;
  }

  submitUpdateEmployee(data) {
    this.service.post('hrDepartment.php?type=updateEmployee', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        data.reset();
        this.getEmployees();
        this.closeUpdateForm();
      } else {
        alert('An error has occurred, Please try again!');
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

  showSalaryAnnexture() {
    this.isSalaryAnnexture = true;
    this.isInit = false;
    this.candidate_no = this.selectedEmployee['emp_id'];
    this.candidate_name = this.selectedEmployee['emp_name'];
  }

  calculateSalary() {
    this.basic = parseInt(((this.ctc * 50) / 100).toFixed(2));
    if (this.isMetro === 'Non Metro') {
      this.HRA = parseInt(((this.basic * 40) / 100).toFixed(2));
    } else {
      this.HRA = parseInt(((this.basic * 50) / 100).toFixed(2));
    }
    this.Conveyance = parseInt(((this.basic * 10) / 100).toFixed(2));
    this.medical = parseInt(((this.basic * 8) / 100).toFixed(2));
    this.educationalAllowance = parseInt(((this.basic * 10) / 100).toFixed(2));
    if (this.isPF === 'Yes') {
      this.PF = parseInt(((this.basic * 12) / 100).toFixed(2));
    }
    if (this.isESIC === 'Yes') {
      this.ESIC = parseInt(((this.basic * 4.75) / 100).toFixed(2));
    }
    this.specialAllowance = this.ctc - (this.basic + this.HRA + this.Conveyance + this.medical + this.educationalAllowance + this.PF + this.ESIC);
    this.gross_total = this.ctc;
    this.calculateNetTotal();
  }

  saveSalary(data) {
    const temp = data.value;
    temp['basic'] = this.basic;
    temp['hra'] = this.HRA;
    temp['conveyance'] = this.Conveyance;
    temp['medical'] = this.medical;
    temp['specialallowance'] = this.specialAllowance;
    temp['educationalallowance'] = this.educationalAllowance;
    temp['pf'] = this.PF;
    temp['esic'] = this.ESIC;
    temp['gross_total'] = this.gross_total;
    temp['net_total'] = this.net_total;
    this.service.post('hrDepartment.php?type=updateEmployeeSalary', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        this.isViewEmployee = false;
        this.isUpdateEmployee = false;
        this.isSalaryAnnexture = false;
        this.isInit = true;
        alert('Salary Annexure has been created.');
      } else {
        alert('An error Occured, Please try again!');
      }
    });
  }

  calculateNetTotal() {
    this.net_total = this.basic + this.HRA + this.Conveyance + this.medical + this.specialAllowance + this.educationalAllowance - this.PF - this.PT + this.ESIC - this.Canteen - this.Other;
  }

  suspendAlert(index) {
    this.temp_id = this.employees[index].emp_id;
    this.isSuspend = true;
  }

  suspendEmployee() {
    this.service.get('hrDepartment.php?type=suspendEmployee&emp_code=' + this.temp_id).subscribe(response => {
      if (response['status'] === 'success') {
        this.getEmployees();
      } else {
        alert('An error Occured, Please try again!');
      }
    });
  }

  filterTable(value) {
    this.employees1 = [];
    let index = 0;
    let len = Object.keys(this.employees).length;
    for (let i = 0; i< len; i++) {
      let val = this.employees[i].emp_id + " " + this.employees[i].emp_name + " " + this.employees[i].department + " " + this.employees[i].designation + " " + this.employees[i].emp_email;
      if (val.includes(value)) {
        this.employees1[index] = this.employees[i];
      }
    }
  }

  approveEmployee(value) {
    this.service.get('hrDepartment.php?type=approveEmployee&emp_code=' + value).subscribe(() => {
      this.getEmployees();
    });
  }

}
