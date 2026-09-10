import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}
  Department = localStorage.getItem('department');
  department_name = localStorage.getItem('department') || '';

  ngOnInit(): void {
    this.Department = localStorage.getItem('department');
    this.department_name = localStorage.getItem('department') || '';
    this.getCcLog(this.department_name);
    this.getDepartments();
  }

  departments;

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
  download() {
    this.service.open(
      'changecontrol1.php?type=CCLogMehaPdf&id=' + this.selectedResult['id']
    );
  }

  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcLog(deptName) {
    this.service
      .get('changecontrol1.php?type=getCcLogMeha&deptName=' + deptName)
      .subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i) {
    this.selectedResult = this.results[i];
    this.isView = true;
  }

  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
    window.open(url, '_blank');
  }

  consentRevDoc: File;
  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.consentRevDoc = event.target.files[0];
    }
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const temp = data.value;

    temp['id'] = this.selectedResult['id'];
    temp['ccNo'] = this.selectedResult['ctrl_no'];
    temp['deptName'] = localStorage.getItem('department');

    this.service
      .post('changecontrol1.php?type=closedByQaHead', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getCcLog(localStorage.getItem('department'));
          data.resetForm();
          this.isView = false;
          this.selectedResult = [];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
}
