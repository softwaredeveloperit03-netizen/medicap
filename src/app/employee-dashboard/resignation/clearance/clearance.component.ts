import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-clearance',
  templateUrl: './clearance.component.html',
  styleUrls: ['./clearance.component.css']
})
export class ClearanceComponent implements OnInit {
  employeeList;
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
  closeform(form){
    this.isSelectedResignation = false;
    form.resetForm();
  }
  getPendingResignations() {
    this.service.get('hr/resignation.php?type=getResignationCleanrance&department=Human Resource').subscribe(response => {
      this.resignations = response;
    });
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }


  getApprovedDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  DepartmentClearence(data) {
    const temp = new FormData();
    temp['reg_id'] = data.resignation_id;
    temp['department'] = 'Human Resource';
    this.service.post('hr/resignation.php?type=appoveDepartmentClearence', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
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
