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

  departments;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {


    this.getDepartments();
  }



  getDepartments() {
    this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
      this.departments = response;
    });
  }
}
