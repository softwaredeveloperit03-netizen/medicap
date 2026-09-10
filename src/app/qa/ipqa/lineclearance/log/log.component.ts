import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {
  materials;
  departments;
  sections;

  department = '';
  section = '';
  from_date = '';
  to_date = '';
  max_date = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getClearanceLog();
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getSection(department) {
    this.service.get('hrDepartment.php?type=getDepartmentSection&selectedDepartment='+department).subscribe(response => {
      this.sections = response;
    });
  }

  getClearanceLog() {
    this.service.get('qa/clearance.php?type=getLineClearanceLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department + '&section=' + this.section).subscribe(response => {
      this.materials = response;
    });
  }

  download() {
    this.service.open('qa/clearance.php?type=downloadLineClearanceLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department + '&section=' + this.section);
  }

}
