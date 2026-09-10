import { Component, OnInit } from '@angular/core';
import { DataAccessService} from 'src/app/data-access.service'

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  results;

  sections;
  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getSection();
    this.getDailyTemperatureLog();
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

  getSection() {
    this.service
      .get('qa/temperature.php?type=getSections')
      .subscribe((response) => {
        this.sections = response;
      });
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service
      .post(
        'qa/temperature.php?type=saveDailyTemperature',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Record saved successfully');
          data.resetForm();
        } else {
          alert(response['status']);
        }
      });
  }

  getDailyTemperatureLog() {
    this.service
      .get('qa/temperature.php?type=getDailyTemperatureLog')
      .subscribe((response) => {
        this.results = response;
      });
  }
}
