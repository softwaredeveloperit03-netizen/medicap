import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  departments;
  designations;
  emp_name;
  department;
  designation;
  resignation_date;
  expected_releaving;
 
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
 
    this.getEmployees();
     
  }


  

  employees;
  getEmployees(){
    this.service.get('hr/employee.php?type=get_EMP_by_ID&department1='+this.department)
    .subscribe(response => {
      this.selectedEmp = response;
      this.designation = response[0]?.designation;
      this.emp_ids = response[0]?.emp_id;
      this.department = response[0]?.department;
      this.emp_name = response[0]?.firstname +" "+ response[0]?.lastname;
    });
  }


  selectedEmp;
  emp_ids;

 
    
  submit(Form){
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = Form.value;
     this.service.post('hr/resignation.php?type=saveResignation', JSON.stringify(temp)) 
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }
}

