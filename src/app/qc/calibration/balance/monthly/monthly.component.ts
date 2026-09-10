import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-monthly',
  templateUrl: './monthly.component.html',
  styleUrls: ['./monthly.component.css'],
})
export class MonthlyComponent implements OnInit {
  result;
  selectedResult = [];
  equipment_id: string;
  isView = false;
  constructor(private service: DataAccessService, private router: Router) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.monthly_data();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
   this.service
     .get(
       'hr/employee.php?type=getrights&emp_id=' +
         localStorage.getItem('emp_id') +
         '&dep_name=' +
         localStorage.getItem('department')
     )
     .subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
       this.isapprover = this.rights[0].isapprover;
       this.qms_approver = this.rights[0].qms_approver;
       this.dept_head = this.rights[0].dept_head;
       this.isauditor = this.rights[0].isauditor;
       this.plant_head = this.rights[0].plant_head;
       this.shift_allocator = this.rights[0].shift_allocator;
     });
  }
  //---------------------------------------------------------------------------------//

  monthly_data() {
    this.service
      .get(
        'qc/calibration/bulkdensity.php?type=get_monthly_calibration&department1=Quality Control'
      )
      .subscribe((response) => {
        this.result = response;
      });
  }

  downloadReport2() {
    this.service.open(
      'qc/raw.php?type=downloadmonthly&department1=Quality Control&id=' +
        this.selectedResult['a_id']
    );
  }

  view(index) {
    this.isView = true;
    this.selectedResult = this.result[index];
  }
}
