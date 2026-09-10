import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-audit',
  templateUrl: './audit.component.html',
  styleUrls: ['./audit.component.css'],
  providers:[DatePipe]
})
export class AuditComponent implements OnInit {
  departments;
  logs;
  department = '';
  constructor(private service: DataAccessService) {
    this.department = localStorage.getItem('department');
    var date = new Date();
    console.log(date.getMonth());
    console.log(date.getFullYear());
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getAuditTrails();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getAuditTrails() {
    this.service.get('qaDepartment.php?type=getAuditTrails&dept=' + this.department).subscribe(response => {
      this.logs = response;
    });
  }

}
