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

  
  departments;
  
  
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }


  getDepartments() {
    this.service.get('common.php?type=getDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }
 
  

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/section.php?type=saveSection', JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success') {
        this.router.navigate(['/master/section'])
        alertify.success('Record Inserted Successfully');
        this.getDepartments();
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



}
