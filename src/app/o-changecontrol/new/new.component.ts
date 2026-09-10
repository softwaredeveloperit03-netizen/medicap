import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
   department_name = localStorage.getItem('department'); // Fetching from localStorage
   employees;
   departments;
  materials;
  devScope;
  products;
  isMajor
isMinor
actualProcedure
deviationObserved
rootCause
 
RiskAssessment
ActionTaken
ChangeRetaedto = [
  { id: 1, name: 'Materials (RM / PM)', value: 1, valuen: false },
  { id: 2, name: 'Specification/ STP', value: 2, valuen: false },
  { id: 3, name: 'Manufacturing Process/ BMR/ BPR', value: 3, valuen: false },
  { id: 4, name: 'SOP', value: 4, valuen: false },
  { id: 5, name: 'Manufacturer /Supplier / Vendor', value: 5, valuen: false },
  { id: 6, name: 'System/ Software', value: 6, valuen: false },
  { id: 7, name: 'Process Control', value: 7, valuen: false },
  { id: 8, name: 'Others', value: 8, valuen: false }
];

onchangeSelect(selectedDept: any, event: any) {
  // First, reset all to false
  this.ChangeRetaedto.forEach(dept => dept.valuen = false);

  // Then, set the selected one to true if checked
  if (event.target.checked) {
    selectedDept.valuen = true;
  }
}

 
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getDepartments();
     this.getEmployees();
     this.getEquipments();
    this.getProducts();
    this.getMaterialsByTypes();
 this.getInitiatByData();
  const today = new Date();
  this.initiateDate = today.toISOString().substring(0, 10);
 
   }
rootCauses
  initiateDate: string = '';
   rootCauseFile: File;
   standProceSysDoc: File;
  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.rootCauseFile = event.target.files[0];
    }
  }
  onFileChanged1(event) {
    if (event.target.files.length === 1) {
      this.standProceSysDoc = event.target.files[0];
    }
  }
 
 

  getDepartments() {
    return new Promise((res, rej) => {
      this.service
        .get('hr/employee.php?type=get_department_by_designation')
        .subscribe((response) => {
          this.departments = response;
          res(response);
        });
    });
  }
  
  getEmployees() {
    this.service.get(
        'hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.employees = response;
       });
  }
  

  getMaterialsByTypes() {
    this.service.get('common.php?type=getMaterialsFormDeviation')
      .subscribe((response) => {
        this.materials = response;
      });
  }

  getProducts() {
    this.service.get('common.php?type=devGetProduct').subscribe((response) => {
      this.products = response;
    });
  }

  getEquipments() {
    this.service.get('common.php?type=get_Equipments&depart='+localStorage.getItem('department')).subscribe((response) => {
      this.equipments = response;
    });
  }
  
  getInitiatByData() {
    this.service.get('common.php?type=getInitiatByData').subscribe((response) => {
      this.identifiedBy = response['identifiedBy'];
    });
  }
  identifiedBy = '';
  equipments;
 
 prodMatStageDoc;
  View(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
    // window.open(this.selectedResult['documents']);
  }

 
  
  devScope1 = '';

  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let formData = new FormData();
    const temp = data.value;

    // Append form values to FormData

    for (let key in temp) {
      if (temp.hasOwnProperty(key)) {
        formData.append(key, temp[key]);
      }
    }

    if(temp['devScope'] == 'Other'){
      formData.append('devScope', this.devScope1);
    }
  
    if (this.rootCauseFile) {
      formData.append('rootCauseFile', this.rootCauseFile, this.rootCauseFile.name);
    }
    if (this.standProceSysDoc) {
      formData.append('standProceSysDoc', this.standProceSysDoc, this.standProceSysDoc.name);
    }

    console.log(formData);
    this.service
      .post('pDeviation.php?type=saveQmsDeviations', formData)
      .subscribe(
        (response) => {
          if (response['status'] === 'success') {
            this.router.navigate(['/qa/qms/deviation']);
            alert('Deviation Initiated Successfully. Proceed...');
           
            data.resetForm();
          } else {
            alert('Failed: An error occurred, please try again!');
          }
        }
        
      );
  }
 
}
