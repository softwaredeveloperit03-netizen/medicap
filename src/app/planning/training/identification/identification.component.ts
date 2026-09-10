
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.getDepartments();
  }

  getTrainingNeeds() {
    this.service.get('training.php?type=getTrainingNeeds').subscribe(response => {
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
    data['employees'] = this.employeeList;
    this.service.post('training.php?type=saveTrainingNeeds', JSON.stringify(data)).subscribe(response => {
      if (response['status'] == "success") {
        alert('Training has been allocated to employee');
        this.getTrainingNeeds();
        this.isNewTraining =false;
      } else {
        alert('An error occured');
      }
    });
  }

  checkOther(value) {
    if (value === 'Other') {
      this.isOther = true;
    } else {
      this.isOther = false;
    }
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
