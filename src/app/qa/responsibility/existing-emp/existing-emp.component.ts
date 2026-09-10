import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-existing-emp',
  templateUrl: './existing-emp.component.html',
  styleUrls: ['./existing-emp.component.css']
})
export class ExistingEmpComponent implements OnInit {

  employeeForm: FormGroup;
  pro: boolean = false;
  reportingPersons = ['Person A', 'Person B', 'Person C'];
  employees: any;

  constructor(private fb: FormBuilder,private service:DataAccessService) { }

  ngOnInit(): void {
   
    this.getEmployees();
    
  }
 
  isView=false;
  selectedResult=[];
 view(index){
  this.selectedResult=this.employees[index]
  this.isView=true;
 }

  getEmployees() {
    this.service.get('qa/job.php?type=getDataJOB').subscribe(response => {
      this.employees = response;
      console.log('this.employees',this.employees);
    })
  }

}
