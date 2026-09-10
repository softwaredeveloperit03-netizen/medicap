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
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/section.php?type=saveSection', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/hr/section']);
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

}
