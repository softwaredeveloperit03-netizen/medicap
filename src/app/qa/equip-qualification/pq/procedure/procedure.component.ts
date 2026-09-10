import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-procedure',
  templateUrl: './procedure.component.html',
  styleUrls: ['./procedure.component.css'],
})
export class ProcedureComponent implements OnInit {
  procedureList = [];
  isView = false;
  results: Object;
  selectedReport = [];
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getProdure();
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.procedureList[this.procedureList.length] = temp;
    data.resetForm();
  }
  getProdure() {
    this.service
      .get(
        '/qa/qualification.php?type=getpqchecklist&id=' +
          this.selectedReport['id']
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  delData(index) {
    this.procedureList.splice(index, 1);
  }
  download() {
    this.service.open('');
  }
  saveProcedure(data) {
    let temp = data.value;
    temp['procedureList'] = this.procedureList;
    console.log(temp);
    this.service
      .post(
        'qa/qualification.php?type=saveProcedure&id=' +
          this.selectedReport['id'], JSON.stringify(temp)
      )
      .subscribe((response) => {
        console.log('Response from server:', response);
        if (response['status'] == 'success') {
          alert('saved successfully');
          data.resetForm();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
}
