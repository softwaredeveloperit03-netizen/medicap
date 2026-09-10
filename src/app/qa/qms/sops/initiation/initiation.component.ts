import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initiation',
  templateUrl: './initiation.component.html',
  styleUrls: ['./initiation.component.css']
})
export class InitiationComponent implements OnInit {

 

  departments;
  department = localStorage.getItem('department');
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.department = localStorage.getItem('department');

  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }


 
 

  save(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
 
    let temp = data.value;
  
    this.service.post('sops.php?type=saveinitiation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.resetForm();
        this.router.navigate(['/qa/qms/sops']);
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
