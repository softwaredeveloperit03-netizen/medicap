
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-identification',
  templateUrl: './identification.component.html',
  styleUrls: ['./identification.component.css']
})
export class IdentificationComponent implements OnInit {
  isNewTraining  = false;
  isOther = false;
  employees;
  trainers;
  emp = '';
  employeeList = [];
  selectedEmp = []
  trainings;

  selectedNeed = [];
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployees1();
    this.getTrainers();
    this.getTrainingNeeds();
    this.getsubject();
    this.getDepartments();
  }

  getTrainingNeeds() {
    this.service.get('training.php?type=getTrainingNeedsEHS').subscribe(response => {
      this.trainings = response;
    });
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response =>{
      this.departments = response;

    } )
  }
  employees1;
  departments;
  onCheckboxChange(event: Event) {
    const checkbox = event.target as HTMLInputElement;
    if (checkbox.checked) {
      console.log('Checkbox is checked');
      let len = this.employees1.length;
      for(let i = 0;i<len;i++){
        let len = Object.keys(this.employeeList).length;
        this.employeeList[len] = this.employees1[i];
      }
    } else {
      console.log('Checkbox is unchecked');
      this.employeeList =[];
     }
  }

  getEmployees(value) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  getEmployees1() {
    this.service.get('employee.php?type=getEmployees').subscribe(response => {
      this.employees = response;
      this.employees1 = response;
    });
  }

  selectEmp(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEmp= this.employees[index];
    }
  }

  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.trainers = response;
    });
  }

  addEmployees() {
    if (this.selectedEmp.length !== 0) {
      let len = Object.keys(this.employeeList).length;
      this.employeeList[len] = this.selectedEmp;
      this.selectedEmp = [];
    }
  }

   

  saveTrainingNeeds(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    data = data.value;
    data['department'] = 'EHS';
    data['employees'] = this.employeeList;
    this.service.post('training.php?type=saveTrainingNeedsdept', JSON.stringify(data)).subscribe(response => {
      if (response['status'] == "success") {
        alert('Training has been allocated to employee');
        this.getTrainingNeeds();
        this.isNewTraining =false;
      } else {
        alert('An error occured');
      }
    });
  }

  add_subject;
  checkOther(value) {
 
    if (value == 'Other') {
      this.add_subject = '';
      this.add_subject = true;
    }
  }
  
   
  subject_list;
  getsubject() {
    this.service.get('training.php?type=getsubject').subscribe(response => {
      this.subject_list = response;
    });
  }

  subject_name;
  add_subject_name() {
    this.service.get('training.php?type=addsubjects&subject=' + this.subject_name).subscribe(response => {
      if (response['status'] == 'success') {
        this.getsubject();
        this.add_subject = false;

        alertify.success('Subject saved successfully');

      } else {
        alertify.error(response['status']);
      }
    });

  }


  
  viewNeeds(index) {
    this.selectedNeed = this.trainings[index];
    this.isView = true;
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=traininglog');
  }
  downloadview(value){
    this.service.open('pdf1/training.php?type=trainingneedsview&id='+value);
  }

}
