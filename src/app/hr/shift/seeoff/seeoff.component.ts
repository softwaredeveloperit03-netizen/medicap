import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-seeoff',
  templateUrl: './seeoff.component.html',
  styleUrls: ['./seeoff.component.css']
})
export class SeeoffComponent implements OnInit {
  department;
  result;
  shift;
  list=[];
  shifts;
  

  max_date = '';
  from_date = '';
  to_date = '';
    departments;
    selectedDepartmentData;
    plant_id;
 
    constructor(private service: DataAccessService, private router: Router) {
      this.plant_id = this.service.getPlantConfigFields("plant_id")
    }
  
   
    ngOnInit() {
      this.service.observableDepartment.subscribe(response => {
        this.plant_id = this.service.getPlantConfigFields("plant_id")
        this.departments = response;
      });
  
      this.getEmployees();
    
    }
    employees;
    getEmployees() {
      // this.service.get('hr/emp.php?type=getemp').subscribe(response => {
      this.service.get('hr/employee.php?type=getEmployeesList'  ).subscribe(response => {
        this.employees = response;
      });
    } 



}
