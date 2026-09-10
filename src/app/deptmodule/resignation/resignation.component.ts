import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-resignation',
  templateUrl: './resignation.component.html',
  styleUrls: ['./resignation.component.css']
})
export class ResignationComponent implements OnInit {

  filterEmployees;
  employeeList;
  employees
  employees1;
  resignations;
  selectedResignation;
  departments;
  isSelectedResignation = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingResignations();
    this.getApprovedDepartments();
    this.getEmployees();
  }

  getPendingResignations() {
    this.service.get('hr/resignation.php?type=get_resignation_dept&department1=' + localStorage.getItem('department'))
    .subscribe(response => {
      this.resignations = response;
    });
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }

  getApprovedDepartments() {
    this.service.get('common.php?type=getDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }

  showDetails(index) {
    this.selectedResignation = this.resignations[index];
    this.isSelectedResignation = true;
  }

  submitResignation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('hr/resignation.php?type=saveResignation_dept&id='+this.selectedResignation['id'], JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert("Data Submit Successfully !!");
        data.reset();
        this.isSelectedResignation = false;
        this.getPendingResignations();
      } else {
        alert('An error occured');
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
}


