import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}
  caliList = [];
  ngOnInit(): void {}
  


  
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.caliList[this.caliList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.caliList.splice(index, 1);
  }
  download() {
    this.service.open('qc/raw.php?type=downloadpdf');
  }
}
