import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-chngcontrol-qa',
  templateUrl: './chngcontrol-qa.component.html',
  styleUrls: ['./chngcontrol-qa.component.css'],
})
export class ChngcontrolQAComponent implements OnInit {
  isView: boolean;
  results: any;
  selectedReport: any;
  Department: any;
  departments: Object;
  constructor(private service: DataAccessService, private router: Router) {
    this.Department = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getAprvlCC();
    this.getDepartments();
  }
  getAprvlCC() {
    this.service
      .get(
        'changecontrol1.php?type=getccqa&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  getDepartments() {
    return new Promise((res, rej) => {
      this.service
        .get('hr/employee.php?type=get_department_by_designationMeha')
        .subscribe((response) => {
          this.departments = response;
          res(response);
        });
    });
  }
  update(data) {
    let temp = data.value;
    this.service
      .post(
        'changecontrol1.php?type=saveQADeptCC&ctrl_no=' +
          this.selectedReport['ctrl_no'] +
          '&deptName=' +
          localStorage.getItem('department'),
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.isView = false;
          alert('Change Control Successfully Proceed... ');
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
