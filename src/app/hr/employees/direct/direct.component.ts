import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-direct',
  templateUrl: './direct.component.html',
  styleUrls: ['./direct.component.css']
})
export class DirectComponent implements OnInit {

  employees;
  Qualifications;
  departments;
  sections;
  designations;
  isSpecialTerm;
  isTraining;
  isNewEmployee = false;
  selectedFile: File;
  selectedFile2: File;
  selectedDocsFile: File;
  selectedPaySlipFile: File;
  value = '12345';

  present_state = '';
  present_country = 'India';
  present_city = '';
  permanent_address = '';

  state_name = '';
  temporary_country = '';
  temporary_address = '';
  city = '';
  areas;
  states;
  cities;
  countries;
  isState = false;
  familyList = [];
  academics = [];
  employeement = []
  length;
  amtList = [];
  qualiList = [];
  permanent_flat = '';
  permanent_country = '';
  permanent_state = '';
  permanent_city = '';
  permanent_pincode = '';
  permanent_mobile_no = '';
  permanent_telephone = '';
  permanent_contact = '';
  tempflat_no = '';
  temp_country = '';
  temp_state = '';
  plants;
  temp_city = '';
  temp_pincode = '';
  temp_telephone = '';
  temp_mobile = '';
  temp_contact = '';
  min_dob;
   
  result = [
    { id: 1, particular: 'Transport ' },
    { id: 2, particular: 'Cantine' },
    { id: 3, particular: 'Telephone' },

  ];
  birthdate: number;
  isdiv1 = false;
  isreq1 = false;
  documents = [];
  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }
  ngOnInit() {
    this.getQualifications();
    this.getDepartments();
    this.getDesignation();
    this.getCountries();
    this.getArea();
    this.getPlants();
    // this.getsections(index) ;
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
 
  addDocuments(documentForm) {
    let obj = documentForm.value;
    obj['document'] = this.selectedDocsFile;
    this.documents.push(obj);
    documentForm.resetForm();
  }
  deleteDoc(index) {
    this.documents.splice(index, 1);
  }
  getPlants() {
    this.service.getData('http://gmpsoftwareindia.com/admin/api/clients/client_data_without_token.php?type=get_client_data&client_code=' + this.service.getPlantConfigFields('client_id')).subscribe(response => {
      this.plants = response;
    });
    
  }


  getQualifications() {
    this.service.get('common.php?type=getQualifications')
      .subscribe(response => {
        this.Qualifications = response;
      });
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments')
      .subscribe(response => {
        this.departments = response;
      });
  }

  getSection(index) {
    index = index - 1;
    let department = this.departments[index];
    this.sections = department['sections'];
  }

  getEmployees(value) {
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  getDesignation() {
    this.service.get('common.php?type=getDesignations')
      .subscribe(response => {
        this.designations = response;
      });
  }

  getState(value) {
    let obj = this.countries;
    let country_id = obj.filter(e => e.country.includes(value))
      .sort((a, b) => a.country.includes(value) &&
        !b.country.includes(value) ? -1 : b.country.includes(value) && !a.country.includes(value) ? 1 : 0);;

    this.service.get('master/state.php?type=getStateBycountry&country=' + country_id).subscribe(response => {
      this.states = response;
    })
  }


  getArea() {
    this.service.get('master/area.php?type=getArea').subscribe(response => {
      this.areas = response;
    })
  }


  getCity(value) {
    this.service.get('master/area.php?type=getCityByStateName&state_name=' + value).subscribe(response => {
      this.cities = response;
    })
  }


  getCountries() {
    this.service.get('master/country.php?type=getCountries').subscribe(response => {
      this.countries = response;
    })
  }

  addAcademic(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.academics[this.academics.length] = temp;
    data.resetForm();
  }
  addEmployment(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.employeement[this.employeement.length] = temp;
    data.resetForm();
  }

  addData(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.familyList[this.familyList.length] = temp;
    data.resetForm();
  }
  delData(index) {
    this.familyList.splice(index, 1);
  }
  delAcademic(index) {
    this.academics.splice(index, 1);
  }
  delEmployment(index) {
    this.employeement.splice(index, 1);
  }
  addQuali(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.qualiList[this.qualiList.length] = temp;
    data.resetForm();
  }



  delQuali(index) {
    this.qualiList.splice(index, 1);
  }



  addValue(para) {

    if (!para.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = para.value;
    temp['salary_slip'] = this.selectedPaySlipFile;
    this.amtList[this.amtList.length] = temp;
    para.resetForm();
  }
  delValue(index) {
    this.amtList.splice(index, 1);
  }

  saveEmployee(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();


    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("isAppointment", "Active");
    uploadData.append("reporting_to", "");

    if (this.selectedFile2 !== undefined) {
      uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append('resume', this.selectedFile, this.selectedFile.name);
    }
    uploadData.append('familyList', JSON.stringify(this.familyList));
    uploadData.append('qualiList', JSON.stringify(this.qualiList));
    uploadData.append('amtList', JSON.stringify(this.amtList));
    uploadData.append('result', JSON.stringify(this.result));
    uploadData.append('document_list', JSON.stringify(this.documents));
    uploadData.append('academics', JSON.stringify(this.academics));
    uploadData.append('employeement', JSON.stringify(this.employeement));
    //  uploadData.append('result',JSON.stringify(this.result));


    this.service.post('hr/employee.php?type=saveEmployee', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        alertify.success('Record Added Successfully');
        this.router.navigate(['/hr/employees']);
      } else {
        alertify.error(response['status']);
      }
    });
  }

  calculateAge(birthday) { // birthday is a date
    birthday = new Date(birthday);
    if (!isNaN(birthday)) {
      var ageDifMs = Date.now() - birthday.getTime();
      var ageDate = new Date(ageDifMs); // miliseconds from epoch
      if (Math.abs(ageDate.getUTCFullYear() - 1970) >= 18) {
      } else {
        alertify.error('Under 18 Age Employee Not Acceptable!');
      }
      this.birthdate = Math.abs(ageDate.getUTCFullYear() - 1970);
    }
  }

  onDocsFileChanged(event) {
    this.selectedDocsFile = event.target.files[0];
  }

  onPayslipFileChanged(event) {
    this.selectedPaySlipFile = event.target.files[0];
  }


  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }

  checkSelection(value) {
    if (value === 'select on Special Term') {
      this.isSpecialTerm = true;
    } else {
      this.isSpecialTerm = false;
    }
  }

  checkAddress(value) {
    if (value) {
      this.tempflat_no = this.permanent_flat;
      this.temp_country = this.permanent_country;
      this.temp_state = this.permanent_state;
      this.temp_city = this.permanent_city;
      this.temp_pincode = this.permanent_pincode;
      this.temp_mobile = this.permanent_mobile_no;
      this.temp_telephone = this.permanent_telephone;
      this.temp_contact = this.permanent_contact;
    } else {
      this.tempflat_no = '';
      this.temp_country = '';
      this.temp_state = '';
      this.temp_city = '';
      this.temp_pincode = '';
      this.temp_mobile = '';
      this.temp_telephone = '';
      this.temp_contact = '';
    }
  }
  isShown: boolean = false; // hidden by default
  isdata: boolean;
  ispart: boolean;
  istable: boolean;
  ispara: boolean;
  isdiv: boolean;
  isrow: boolean;
  isreq: boolean;

  toggleShow() {
    this.isShown = !this.isShown;

  }
  dataShow() {
    this.isdata = !this.isdata;
  }
  valueShow() {
    this.ispart = !this.ispart;
  }
  systemShow() {
    this.istable = !this.istable;
  }
  indShow() {
    this.ispara = !this.ispara;
  }
  sysShow() {
    this.isdiv = !this.isdiv;
  }
  sysShow1() {
    this.isdiv1 = !this.isdiv1;
  }
  actualShow() {
    this.isrow = !this.isrow;
  }
  inputShow() {
    this.isreq = !this.isreq;
  }
  inputShow1() {
    this.isreq1 = !this.isreq1;
  }
}


