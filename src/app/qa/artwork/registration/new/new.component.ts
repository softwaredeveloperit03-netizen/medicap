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

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
  }









  saveEmployee(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
    
    this.service.post('hr/employee.php?type=savedevloper', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        alertify.success('Record Added Successfully');
       } else {
        alertify.error(response['status']);
      }
    });
  }
















}
