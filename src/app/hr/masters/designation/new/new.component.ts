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

  constructor(private service:DataAccessService,private router:Router) { }
  departments;
  designations;

  list = [];
  ngOnInit() {
    this.getDepartment();
  }
  getDepartment(){
    this.service.get('hr/department.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    })
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.list[this.list.length] = data.value;
    data.resetForm();
  }

  del(index) {
    this.list.splice(index, 1);
  }
  addDesignation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['responsibilities'] = this.list;
    this.service.post('hr/designation.php?type=saveDesignation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.router.navigate['/hr/designation'];
        alertify.success('Designation Added Successfully');

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
