import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  visitorName = '';
  isMobile = false;
  selectedResult = [];
  isNew = false;
  isPhoto = false;
  isView = false;
  filterargs;
  employeeList: Array<string>;

  meeting: string;
  employeeMasterList;
  newEmployee: Array<any>;
  results;
  category;
  departments;
  employees;
  from_date = '';
  to_date = '';
  results1 = [];
  department_name = '';
  designations;
  consumptions= [];
  department='';
  constructor(private service: DataAccessService, private datepipe: DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getAidDetails();
    this.getDepartments();
  }


  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    })
  }

  getAidDetails() {
    this.service.get('security/firstaid.php?type=getAidConsumption').subscribe(response => {
      this.results = response;
      this.filterDepartment();
    });
  }

  download() {
    this.service.open('security/firstaid.php?type=downloadAidConsumption&department_name=' + this.department);
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  filterDepartment() {
    this.consumptions = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['department'].toUpperCase().includes(this.department.toUpperCase())) {
        this.consumptions[this.consumptions.length] = material;
      }
    }
  }
  AllRecord(){
    this.consumptions =this.results;
    this.department='';
    
}
}
