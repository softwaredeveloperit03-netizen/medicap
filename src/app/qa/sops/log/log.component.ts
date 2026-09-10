import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  departments;
  reference = [];
  department_name = '';
  sop_for = '';
  status = '';

  selectedResult = [];
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSOPs();
    this.getDepartments();
  }

  getCheckedSOPs() {
    this.service.get('sops.php?type=getsoplog&department_name=' + this.department_name + '&sop_for=' + this.sop_for + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.reference = this.selectedResult['reference'];
    this.isView = true;
  }

  download(value) {
    if (value == 'manual') {
      this.service.open('pdf1/sop.php?type=sop&sop_no=' + this.selectedResult['sop_no']);
    } else if (value == 'digital') {
      this.service.open('pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no']);
    } else {
      this.service.open('pdf1/sop.php?type=log');
    }
  }

}
