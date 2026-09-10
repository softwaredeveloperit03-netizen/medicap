import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;


@Component({
  selector: 'app-category',
  templateUrl: './category.component.html',
  styleUrls: ['./category.component.css']
})
export class CategoryComponent implements OnInit {
  departments;
  trainers;
  employees;
  selectedEmp=[];
  employeeList=[];
  selectedEmployee=[];
  constructor(private service:DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getTrainers();
    this.getsubject();
    this.getEmployees1();
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response =>{
      this.departments = response;

    } )
  }

  isOther = false;


  add_subject;
  checkOther(value) {
 
    if (value == 'Other') {
      this.add_subject = '';
      this.add_subject = true;
    }
  }



  
  employees1;
   onCheckboxChange(event: Event) {
    const checkbox = event.target as HTMLInputElement;
    if (checkbox.checked) {
      console.log('Checkbox is checked');
      let len = this.employees1.length;
      for(let i = 0;i<len;i++){
        let len = Object.keys(this.employeeList).length;
        this.employeeList[len] = this.employees1[i];
      }
    } else {
      console.log('Checkbox is unchecked');
      this.employeeList =[];
     }
  }

  getEmployees(value) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  getEmployees1() {
    this.service.get('employee.php?type=getEmployees').subscribe(response => {
      this.employees = response;
      this.employees1 = response;
    });
  }



  
   
  subject_list;
  getsubject() {
    this.service.get('training.php?type=getsubject').subscribe(response => {
      this.subject_list = response;
    });
  }

  subject_name;
  add_subject_name() {
    this.service.get('training.php?type=addsubjects&subject=' + this.subject_name).subscribe(response => {
      if (response['status'] == 'success') {
        this.getsubject();
        this.add_subject = false;

        alertify.success('Subject saved successfully');

      } else {
        alertify.error(response['status']);
      }
    });

  }

  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.trainers = response;
    });
  }
 

  addEmployees(data) {
    let temp = data.value;
    temp['firstname'] = this.selectedEmp['firstname'];
    temp['lastname'] = this.selectedEmp['lastname'];
    temp['emp_id'] = this.selectedEmp['emp_id'];
    temp['department'] = this.selectedEmp['department'];
    temp['designation'] = this.selectedEmp['designation'];
     this.employeeList[this.employeeList.length] = temp;
    // this.employeeList[len] = this.selectedEmp;
    this.selectedEmp = [];
  }

  delEmployees(index) {
    this.employeeList.splice(index, 1);
  }

  selectEmp(index) {
    index = index - 1;
      this.selectedEmp = this.employees[index];
   
  }

  save(data){
    if (!data.valid) {
      alertify.error('All Fields are Required..');
      return;
    }
    let temp = data.value;
    temp['employees'] = this.employeeList;
    this.service.post('training.php?type=savecategorytraining', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted successfully');
        data.resetForm();
        this.router.navigate(['/qa/training/schedule-training']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

 

}
 