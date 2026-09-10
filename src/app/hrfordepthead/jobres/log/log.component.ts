import { Component, OnInit } from '@angular/core';
import {FormBuilder } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

 
  employees: any;

  constructor(private fb: FormBuilder,private service:DataAccessService) { }

  ngOnInit(): void {
    this.getEmployees();
  }

  selectedEmp =[];

  onEmpChange(emp_id: any) {
   let obj =  this.employees.find(item=>item.emp_id == emp_id);
    this.selectedEmp = obj;
  }

 
  getEmployees() {
    this.service.get('hr/responsibility.php?type=respGetLog&jaduDept='+localStorage.getItem('department')).subscribe(response => {
      this.employees = response;
     })
  } 
  download() {
  this.service.open('hr/responsibility.php?type=jobRespLog&id=' + this.selectedResult['id']);   }
 

  selectedResult =[];
  isView = false;

  View(i){
    this.selectedResult = this.employees[i];
    this.isView = true;
  }

 

  

}
