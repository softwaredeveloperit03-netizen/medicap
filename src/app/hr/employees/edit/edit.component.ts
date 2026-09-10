import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EmpModel } from '../emp-model';
import { NgForm } from '@angular/forms';
declare let alertify;
@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {


  employement_type = 'Existing Employee';
  joining_status = 'Trainee';
  emp_level = '';
  induction = 'Yes';
  epf_app = 'No';
  esic_app = 'No';
 
 
  constructor(private service: DataAccessService, private route: ActivatedRoute, private router: Router) {}

  ngOnInit() {
    this.getDepartments();  
    this.getQualifications();
    this.emp_id = this.route.snapshot.queryParamMap.get('emp_id');
    this.getEmployeeDetails(this.emp_id);
  }

  getEmployeeDetails(emp_code) {
    this.service.get('hr/employee.php?type=getEmployeeDetails&emp_code=' + emp_code).subscribe((response: EmpModel) => {
      this.selectedResult = response[0];
      this.getDesignation(response[0]?.department);
      this.getUploadedDocByEmpId(response[0]?.emp_id);
    });
  }
 


  departments;
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((response) => {
      this.departments = response;
    });
  }

  designations =[];
  department = '';
  getDesignation(department) {
 
    for (let i = 0; i < this.departments.length; i++) {
      if (this.departments[i]['department_name'] == department) {
        this.designations = this.departments[i]['designations'];
      }
    }
  }
 

  docfile: File;
  onFileChanged8(event) {
    this.docfile = event.target.files[0];
  }

  emp_id;
  documents ;
    addDocuments(documentForm: NgForm) {
      if (!documentForm.valid) {
        alertify.error('All fields are required!');
        return;
      }

      const temp = documentForm.value;
      const uploadData = new FormData();
      uploadData.append('documentName', temp['documentName']);
      uploadData.append('emp_id', this.emp_id);

      if (this.docfile !== undefined) {
        uploadData.append('doc', this.docfile, this.docfile.name);
      }

      // Pass plant_id in query string
      this.service.post('hr/employee.php?type=add_doc',uploadData).subscribe((response: any) => {
        if (response.status === 'success') {
          documentForm.resetForm();
          alertify.success('Doc Added Successfully');
          this.getUploadedDocByEmpId(this.emp_id)
        } else {
          alertify.error(response.status);
        }
      });
    }


  getUploadedDocByEmpId(emp_id) {
      this.service.get('hr/employee.php?type=getUploadedDocByEmpId&emp_idForDoc=' +emp_id ).subscribe((response) => {
        this.documents = response;
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
 
  Qualifications ;
  getQualifications() {
  
    this.service.get('common.php?type=getQualifications').subscribe((response) => {
      this.Qualifications = response;
    });
  
  }

  
  addAcademic(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.selectedResult['academics'].push(temp);
    data.resetForm();
  }
  delAcademic(index) {
    this.selectedResult['academics'].splice(index, 1);
  }


 
  addEmployment(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.selectedResult['employeement'].push(temp);
    data.resetForm();
  }

  delEmployment(index) {
    this.selectedResult['employeement'].splice(index, 1);
  }

 
   
  addLanguage(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.selectedResult['languages'].push(temp);
    data.resetForm();
  }

  delLanguage(index) {
    this.selectedResult['languages'].splice(index, 1);
  }

 
  updatePhoto() {

      const uploadData = new FormData();
   
      if (this.selectedFile2) {
        uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
      }else{
        alertify.error('Please fill all required fields');
        return;
      }

      uploadData.append('emp_id' , this.emp_id);

      this.service.post('hr/employee.php?type=updatePhoto', uploadData).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Image Updated Successfully');
          this.getEmployeeDetails(this.emp_id);
        } else {
          alertify.error('Error: ' + response['status']);
        }
      });
  }


  updateManditoryDetails(form: NgForm) {
      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      const temp = form.value;
      temp['emp_id'] = this.emp_id;

      this.service.post('hr/employee.php?type=updateManditoryDetails', JSON.stringify(temp)).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Manditory Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }

  updateOtherDetails(form: NgForm) {
      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      const temp = form.value;
      temp['emp_id'] = this.emp_id;

      this.service.post('hr/employee.php?type=updateOtherDetails', JSON.stringify(temp)).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Other Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }

  updateAddress(form: NgForm) {
      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      const temp = form.value;
      temp['emp_id'] = this.emp_id;

      this.service.post('hr/employee.php?type=updateAddress', JSON.stringify(temp)).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Address Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }


  updateBankDetails(form: NgForm) {
      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      const temp = form.value;
      temp['emp_id'] = this.emp_id;

      this.service.post('hr/employee.php?type=updateBankDetails', JSON.stringify(temp)).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Bank Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }

  updateAcademicsDetails() {

      if (this.selectedResult['academics']?.length == 0) {
        alertify.error('Please Add Academics Details...');
        return;
      }
      const uploadData = new FormData();
      uploadData.append('emp_id' , this.emp_id);
      uploadData.append('academics', JSON.stringify(this.selectedResult['academics'] || []));

      this.service.post('hr/employee.php?type=updateAcademicsDetails', uploadData).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Academics Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }

  updateLanguages() {

      if (this.selectedResult['languages']?.length == 0) {
        alertify.error('Please Add languages Details...');
        return;
      }
      const uploadData = new FormData();
      uploadData.append('emp_id' , this.emp_id);
      uploadData.append('languages', JSON.stringify(this.selectedResult['languages'] || []));

      this.service.post('hr/employee.php?type=updateLanguages', uploadData).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('languages Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }

  updateEmployeement() {

      if (this.selectedResult['employeement']?.length == 0) {
        alertify.error('Please Add Employeement Details...');
        return;
      }
      const uploadData = new FormData();
      uploadData.append('emp_id' , this.emp_id);
      uploadData.append('employeement', JSON.stringify(this.selectedResult['employeement'] || []));

      this.service.post('hr/employee.php?type=updateEmployeement', uploadData).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Employeement Details Updated Successfully');
            this.getEmployeeDetails(this.emp_id);
          } else {
            alertify.error('Error: ' + response['status']);
          }
        });
  }




 



 
  birthdate: string | null = null;
  age: number | null = null;
  minDate = new Date(); // today

  calculateAge(birthday: string) {
    if (!birthday) return;

    const birthDate = new Date(birthday);
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();

    // Adjust if the birthday hasn't occurred yet this year
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }

    this.age = age;

    if (age < 18) {
      alertify.error('Under 18 Age Employee Not Acceptable!');
    }
  }


 
  selectedFile2: File;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
 

  permanent_flat = '';
  permanent_country = 'India';
  permanent_state = '';
  permanent_city = '';
  permanent_pincode = '';

  tempflat_no = '';
  temp_country = 'India';
  temp_state = '';
  temp_city = '';
  temp_pincode = '';

 
 

  checkAddress(value) {
    if (value) {
      this.tempflat_no = this.permanent_flat;
      this.temp_country = this.permanent_country;
      this.temp_state = this.permanent_state;
      this.temp_city = this.permanent_city;
      this.temp_pincode = this.permanent_pincode;
    } else {
      this.tempflat_no = '';
      this.temp_country = 'India';
      this.temp_state = '';
      this.temp_city = '';
      this.temp_pincode = '';
    }
  }



  isMandField: boolean = true;  
  isAddressDetails: boolean = true;  
  isBank: boolean = true;  
  isacadamic: boolean = true;  
  isLanguage: boolean= true;
  isEmpHistory: boolean= true;
  isDocuments: boolean= true;


  mandField() {
    this.isMandField = !this.isMandField;
  }

  addressdETAILS() {
    this.isAddressDetails = !this.isAddressDetails;
  }

  showBank() {
    this.isBank = !this.isBank;
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



  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam",
    "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi",
    "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir",
    "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram",
    "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
    "Uttar Pradesh","Uttarakhand","West Bengal"
  ];

 

}



