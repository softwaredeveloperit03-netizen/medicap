import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new-capa',
  templateUrl: './new-capa.component.html'
})
export class NewCapaComponent implements OnInit {
  dataForm;
  request_by = '';
  request_date;
  departments;
  constructor(private service: DataAccessService, private router:Router) {
    this.request_by = localStorage.getItem('emp_id');
    this.request_date = this.formatDate(new Date());
  }

  ngOnInit(): void {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;

    return [year, month, day].join('-');
  }

  saveCAPA(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    alert('Saved Successfully');
    this.router.navigate(['../capa']);
  }

}
