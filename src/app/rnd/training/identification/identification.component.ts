
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
    this.getEmployees();
    this.getTrainers();
    this.getTrainingNeeds();
    this.getsubject();
  }

  getTrainingNeeds() {
    this.service.get('training.php?type=getTrainingNeedsRD').subscribe(response => {
      this.trainings = response;
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getEmployees').subscribe(response => {
      this.employees = response;
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
    data['department'] = 'R & D';
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
