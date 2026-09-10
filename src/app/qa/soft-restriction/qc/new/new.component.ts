import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})

export class NewComponent implements OnInit {
  departments ;

  

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getDepartments();
  }


  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }

  saveSoftware_restrication(data){

let temp=data.value;

    this.service.post('qa/custimize.php?type=saveSoftware_restrication', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved successfully');
        data.resetForm();
    
       
      } else {
        alert('An error occured');
      }
    });
  }
}
