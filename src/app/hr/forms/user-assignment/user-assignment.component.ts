import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-user-assignment',
  templateUrl: './user-assignment.component.html',
  styleUrls: ['./user-assignment.component.css']
})
export class UserAssignmentComponent implements OnInit {
  isNewForm = false;
  rights;
  employees;
  selectedEmployee;
  isFirst;
  user = false;
  checker = false;
  approver = false;
  isOfficeMail = false;
  isOfficePhone = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getUserRights();
    this.getNoRoleEmployee();
  }

  getUserRights() {
    this.service.get('hrDepartment.php?type=getEmployees').subscribe(response=> {
      this.rights = response;
    });
  }

  getNoRoleEmployee() {
    this.service.get('hrDepartment.php?type=getNoRoleEmployee')
    .subscribe(response => {
      this.employees = response;
    });
  }

  getSelectedEmployee(index) {
    index = index-1;
    this.selectedEmployee = this.employees[index];
    this.isFirst = true;
  }

  assignRole(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('hrDepartment.php?type=assignRole', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        this.isFirst = true;
        this.isOfficeMail = false;
        this.isOfficePhone = false;
        this.isNewForm = false;
        this.getNoRoleEmployee();
        this.getUserRights();
      } else {
        alert(response['status']);
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
