import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  selectedTraining=[];
  results;
  isView = false;
  employeeList = [];
  selectedEmp = [];

  employees;



  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getTrainings();
    this.getEmployees();
  }

  view(index) {
    this.selectedTraining = this.trainings[index];
    this.isView = true;
  }

  trainings;
  getTrainings() {
    this.service.get('training.php?type=getawaitingReTrainingsEHS').subscribe(response => {
      this.trainings = response;
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

  saveScheduleTraining(data) {
    let test = data.value;
    test['id'] = this.selectedTraining['id'];
    test['empList'] = this.employeeList;
    this.service.post('training.php?type=saveScheduleTraining', JSON.stringify(test)).subscribe(response => {
      if (response['status'] == 'success') {
        this.selectedTraining = [];
        this.employeeList = [];
        this.selectedEmp = [];
        this.isView = false;
        this.getTrainings();
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }
}
