import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-deviation',
  templateUrl: './deviation.component.html',
  styleUrls: ['./deviation.component.css'],
})
export class DeviationComponent implements OnInit {
  results: Object;
  selectedReport = [];
  constructor(private service: DataAccessService, private router: Router) {}
  deviationList = [];
  isview = false;
  ngOnInit(): void {
    this.getdeviation();
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.deviationList[this.deviationList.length] = temp;
    data.resetForm();
  }
  getdeviation() {
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
    this.isview = true;
  }

  delData(index) {
    this.deviationList.splice(index, 1);
  }
  download() {
    this.service.open('');
  }
  saveDeviation(data) {
    let temp = data.value;
    temp['deviationList'] = this.deviationList;
    console.log(temp);
    this.service
      .post(
        'qa/qualification.php?type=saveDeviation&id=' +
          this.selectedReport['id'],
        JSON.stringify(temp)
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

