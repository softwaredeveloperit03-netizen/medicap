import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-individual',
  templateUrl: './individual.component.html',
  styleUrls: ['./individual.component.css']
})
export class IndividualComponent implements OnInit {

  departments;
  employees;
  selectedEmployee = [];
  records;

  department = '';
  trainings = 0;
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getEmployees(department) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment&department=' + department).subscribe(response => {
      this.employees = response;
    });
  }

  getRecords(index) {
    index = index - 1;
    this.selectedEmployee = this.employees[index];
    this.service.get('training.php?type=getTrainingRecords&department=' + this.department + '&employee=' + this.selectedEmployee['emp_id']).subscribe(response => {
      this.records = response;
      this.trainings = Object.keys(this.records).length;
    });
  }

  
  downloadreport(){
    if(this.department != '' && this.selectedEmployee['emp_id'] != undefined){
      this.service.open('pdf1/training.php?type=trainingrecords&employee='+ this.selectedEmployee['emp_id']+'&department1='+this.department);
    }else{
      alert('Select Employee');
    }
    
  }
}
