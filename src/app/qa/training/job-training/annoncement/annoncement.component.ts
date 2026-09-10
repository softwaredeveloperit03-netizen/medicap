import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-annoncement',
  templateUrl: './annoncement.component.html',
  styleUrls: ['./annoncement.component.css']
})
export class AnnoncementComponent implements OnInit {

  ispending = false;
  isView = false;
  results;
  isNewTraining = false;
  trainers;
  trainings;
  employees;
  selectedTraining = [];
  employeeList = [];
  selectedEmp = [];
  selectedResult={
    entry_by_name:''
  }
  selectedReasonForTraining;


  // --
  training_data = {
    // Section (A-F)
    training_description_title:'',
    trainee_name:'',
    start_date:'',
    finish_date:'',
    reason_for_training:'',
    reason_for_training_reference:'',
    task_or_activities:'',
    trainee_read_status:'',
    description_of_training_records_attached_status:'',
    discription_of_training:'',
    // Section (F-I)
    competancy_assessment_record_attached_status:'',
    competancy_assessment:'',
    competancy_assessment_status:'',
    work_unsupervised_status:'',
    trainee_understands_status:'',
    trainee_signature_comments:'',
    trainer_or_assessor_comments:'',
  }
    // Section (A-F)
  training_description_title;
  trainee_name;
  start_date;
  finish_date;
  reason_for_training;
  reason_for_training_reference;
  task_or_activities;
  trainee_read_status;
  description_of_training_records_attached_status;
  discription_of_training;
  // Section (F-I)
  competancy_assessment_record_attached_status;
  competancy_assessment;
  competancy_assessment_status;
  work_unsupervised_status;
  trainee_understands_status;
  trainee_signature_comments;
  trainer_or_assessor_comments;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getScheduleLog();
    this.getTrainings();
    this.getTrainers();
    this.getEmployees();
  }

  getScheduleLog() {
    this.service.get('training.php?type=getojtScheduleLog').subscribe(response => {
      this.results = response;
    });
  }

  getTrainings() {
    this.service.get('training.php?type=getPendingojtTrainings').subscribe(response => {
      this.trainings = response;
    });
  }

  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.trainers = response;
    });
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment').subscribe(response => {
      this.employees = response;
    });
  }

  selectEmp(index) {
    index = index - 1;
    this.selectedEmp= this.employees[index];
  }

  addEmployees() {
    let len = Object.keys(this.employeeList).length;
    this.employeeList[len] = this.selectedEmp;
    this.selectedEmp = [];
  }

  viewTraining(index) {
    this.selectedTraining = this.trainings[index];
    this.isNewTraining = true;
    this.ispending = false;
    
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

  saveScheduleTraining(data) {
    let test = data.value;
    test['id'] = this.selectedTraining['id'];
    test['empList'] = this.employeeList;
    this.service.post('training.php?type=saveScheduleTraining', JSON.stringify(test)).subscribe(response => {
      if (response['status'] == 'success') {
        this.selectedTraining = [];
        this.employeeList = [];
        this.selectedEmp = [];
        this.isNewTraining = false;
        this.getScheduleLog();
        this.getTrainings();
        this.ispending = true;
        alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=trainingschedulelog');
  }
}
