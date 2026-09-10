import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-performance',
  templateUrl: './performance.component.html',
  styleUrls: ['./performance.component.css']
})
export class PerformanceComponent implements OnInit {

  results;
  employees;
  departments;
  designations;

  department_name='';
  designation='';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMonthlyPerformance();
    this.getDepartments();
    this.getDesignation();
  }

  getMonthlyPerformance() {
    this.service.get('hr/employee.php?type=getMonthlyPerformance&department_name=' + this.department_name + '&designation=' + this.designation).subscribe(response => {
      this.results = response;
    });
  }

 
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignation() {
    this.service.get('common.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

}
