import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-personalhy',
  templateUrl: './personalhy.component.html',
  styleUrls: ['./personalhy.component.css']
})
export class PersonalhyComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }


  departments1;
  employees;


  ngOnInit(): void {

    this.service.observableDepartment.subscribe(response => {
      this.departments1 = response;
    });
  }


  save(data) {

    let temp = data.value;
    
      this.service.post('admin/housekeeping.php?type=save_personal_hygiene', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
        data.reset();
       });
    

  }



  getEmployees(value) {
    this.service.get('employee.php?type=getDeptEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }

}


 