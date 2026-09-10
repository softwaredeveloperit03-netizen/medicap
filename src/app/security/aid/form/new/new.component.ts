import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {

  visitorName='';
  isMobile = false;

  isNew = false;
  designations;

 
  


  results;
  category;
  departments;
  employees;
  from_date = '';
  to_date = '';
  results1=[];
  department_name = '';
  constructor(private service: DataAccessService,private datepipe:DatePipe, private router:Router) {
  
  }

  ngOnInit() {
    this.getDepartments();
    this.getDesignation();
    this.getEmployees();
  }


  getEmployees(){
    this.service.get('employee.php?type=getAllEmployees').subscribe(response => {
      this.employees = response;
    });
  }
  getDesignation(){
    this.service.get('common.php?type=getDesignationHeading').subscribe(response => {
      this.designations = response;
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  
  saveAid(data) {
    if(!data.valid){
      alertify.error("all field are required");
      return;
    }
    let temp = data.value;
    this.service.post('security/firstaid.php?type=saveFirtsAid', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Gate Pass Record Added');
        data.resetForm();
        this.router.navigate(['/security/aid/form']);
      } else {
       alertify.error('An error occured');
      }
    });
  }
  download() {
    this.service.open('security/entry.php?type=downloadLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name);
  }

}
