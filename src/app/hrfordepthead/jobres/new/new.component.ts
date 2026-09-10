import { Component, OnInit } from '@angular/core';
import {FormBuilder } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

 
   employees: any;

  constructor(private fb: FormBuilder,private service:DataAccessService) { }

  ngOnInit(): void {
  
    this.getEmployees();
    this.getALLDeptEmployees();
   
  }

  selectedEmp =[];

  onEmpChange(emp_id: any) {
   let obj =  this.employees.find(item=>item.emp_id == emp_id);
    this.selectedEmp = obj;
  }


  description = '';
  descData =[];

  addDesc(data){
    if (!data.valid) {
      alertify.error('All Field Required!!!!!!!!!');
      return;
    }
    let temp =  data.value;
    this.descData.push(temp);
    data.reset();
  }

  delDesc(index){
    this.descData.splice(index,1);
  }



  onSubmit(data) {

    if (!data.valid) {
      alertify.error('All Field Required!!!!!!!!!');
      return;
    }

    let temp =  data.value;
    temp['emp_code'] = this.selectedResult['emp_id'];
    temp['resp'] = this.descData;
    this.service.post('hr/responsibility.php?type=saveJobResponsibilities', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        this.descData=[];
        this.isView = false;
        data.reset();
        this.getEmployees();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  employees1;


  getEmployees() {
    this.service.get('hr/responsibility.php?type=getPendingEmployees&jaduDept='+localStorage.getItem('department')).subscribe(response => {
      this.employees = response;
     })
  }

  getALLDeptEmployees() {
    this.service.get('hr/responsibility.php?type=getALLDeptEmployees&jaduDept='+localStorage.getItem('department')).subscribe(response => {
      this.employees1 = response;
     })
  } 
  
  selectedResult =[];
  isView = false;

  View(i){
    this.selectedResult = this.employees[i];
    this.isView = true;
  }



  

}
