import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { DataAccessService } from '../data-access.service';

@Injectable({
  providedIn: 'root'
})
export class HrDataService {

  employees: any = [];
  observableEmployee;

  designations: any = [];
  observableDesignation;
  constructor(private service: DataAccessService, private http: HttpClient) {
    this.observableEmployee = new BehaviorSubject(this.employees);
    this.observableDesignation = new BehaviorSubject(this.designations);

    // this.getEmployees();
    this.getDesignations();
  }

  productChange() {
    this.observableEmployee.next(this.employees);
  }

  getEmployees() {
    return new Promise(resolve => {
      let url = this.service.url + 'hr/employee.php?type=getEmployees&department_name=&designation=&unit=&employee_id=' + '&token=' + localStorage.getItem('token') + '&user_no=gmpdemo1';
      this.http.get(url).subscribe(response => {
        this.employees = response;
        this.productChange();
        resolve(this.employees)
      });
    });
  }

  designationChange() {
    this.observableDesignation.next(this.designations);
  }

  getDesignations() {
    return new Promise(resolve => {
      let url = this.service.url + 'common.php?type=getDesignations' + '&token=' + localStorage.getItem('token') + '&user_no=gmpdemo1';
      this.http.get(url).subscribe(response => {
        this.designations = response;
        this.designationChange();
        resolve(this.designations)
      });
    });
  }
}
