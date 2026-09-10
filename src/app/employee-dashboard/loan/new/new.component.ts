import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  designations;
  departments;
  emp_id;
  entry_date;
  emp_name;
  lists;

  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }

 
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }

  getDesignation(idx) {
    this.designations =[];
    this.designations = this.departments[idx-1]['designations']
  }

  saveloan(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp=Form.value;
    temp['materials']=this.lists;
    // this.service.post('store/outward.php?type=saveMaterialOutForm', JSON.stringify(temp))
    // .subscribe(response => {
    //   if (response['status'] === 'success') {
       
    //     Form.resetForm();
    //     // this.getMaterialOutDetails();
    //     alertify.success("save successfully");
    //   } else {
    //     alertify.error('Please Try Again');
    //   }
    //   }),
    }
  }
