import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-chember',
  templateUrl: './chember.component.html',
  styleUrls: ['./chember.component.css'],
})
export class ChemberComponent implements OnInit {
  isNew = false;
  results;
  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getStabilityChemberLog();
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
          this.loggedInDept
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

  getStabilityChemberLog() {
    this.service
      .get('stability.php?type=getStabilityChemberLog')
      .subscribe((response) => {
        this.results = response;
      });
  }

  saveChemberTemperature(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service
      .post(
        'stability.php?type=saveChemberTemperature',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Temp/Humidity record saved successfully');
          data.resetForm();
          this.getStabilityChemberLog();
          this.isNew = false;
        } else {
          alert('An error occured, Please try again!');
        }
      });
  }
}
