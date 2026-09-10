import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-closure',
  templateUrl: './closure.component.html',
  styleUrls: ['./closure.component.css'],
})
export class ClosureComponent implements OnInit {
  isView: boolean = false;
  results: any;
  selectedReport: any = {};
  Department: any;
  departments: any;
  DocumentNo: string = '';
  Document: string = '';
  version: string = '';

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
        'changecontrol1.php?type=getccClosure&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.results = response;
      }, (error: any) => {
        console.error('Error fetching change control closure:', error);
      });
  }

  view(index: number) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  getDepartments() {
    return new Promise((res, rej) => {
      this.service
        .get('hr/employee.php?type=get_department_by_designationMeha')
        .subscribe((response: any) => {
          this.departments = response;
          res(response);
        }, (error: any) => {
          rej(error);
        });
    });
  }

  update(data: any) {
    if (!data.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    let temp = data.value;
    this.service
      .post(
        'changecontrol1.php?type=saveCCclosure&ctrl_no=' +
          this.selectedReport['ctrl_no'] +
          '&deptName=' +
          localStorage.getItem('department'),
        JSON.stringify(temp)
      )
      .subscribe((response: any) => {
        if (response['status'] == 'true') {
          this.isView = false;
          this.getAprvlCC();
          alertify.success('Change Control Successfully Proceed...');
        } else {
          alertify.error('An error occurred, please try again');
        }
      }, (error: any) => {
        alertify.error('Error saving change control closure');
      });
  }
}

