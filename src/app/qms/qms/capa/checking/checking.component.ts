import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  departments;
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getInprocessCAPA();
  }

  getInprocessCAPA() {
    this.service.get('qa/capa.php?type=getInprocessCAPA').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('qa/capa.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  update(status) {
    // if(!status.valid){
    //   alertify.error('Select Departments For Review');
    //   return;
    // }
    let temp = {};

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }
    temp['departments'] = test;
    temp['status'] = status;
    temp['id'] = this.selectedResult['id'];
    temp['capa_no'] = this.selectedResult['capa_no'];
    this.service.post('qa/capa.php?type=checkCAPA', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getInprocessCAPA();
      } else {
        alertify.error('Failed: An error has occurred, please try again');
      }
    });
  }

}
