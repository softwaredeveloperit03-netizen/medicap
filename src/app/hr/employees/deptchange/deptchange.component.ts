import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-deptchange',
  templateUrl: './deptchange.component.html',
  styleUrls: ['./deptchange.component.css']
})
export class DeptchangeComponent implements OnInit {


  departments;
  employees;

  plant_id;
  isdeptChnage = false;




  constructor(private service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

  ngOnInit(): void {
    this.getDepartments();
  }


  getDepartments(){
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe(response => {
      this.departments = response;
    });
  }




  department;

  getEmployees() {
     this.service.get('hr/employee.php?type=getEmployeesbydept&department_name=' + this.department).subscribe(response => {
      this.employees = response;
    });
  }


  selectedResult =[];
  

  Change(user){
    this.selectedResult = user;
    this.isdeptChnage = true;
  }

  designations=[]


  getDesignation1(data) {
    let department = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }


 

   savedeptChange(data){

    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['emp_id123'] = this.selectedResult['emp_id'];
    temp['old_designation'] = this.selectedResult['designation'];
    temp['old_department'] = this.selectedResult['department'];

    this.service.post('hr/employee.php?type=changeDepartment', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getEmployees();
        data.resetForm();
        this.isdeptChnage =  false;
        alertify.success('Changed Successfully');
       } else {
        alertify.error(response['status']);
      }
    });

   }



}
