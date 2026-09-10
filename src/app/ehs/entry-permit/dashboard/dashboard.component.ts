import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  date;
  departments;
  isView = false;
  entrys: any = [];
  from_date = '';
  to_date = '';
  applicable = '';
  selectedBatch: any = [];
  selectedCondition: any = [];

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getDepartments();
    this.getEntrys();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id='
      + localStorage.getItem('emp_id') + '&dep_name=' + this.loggedInDept)
      .subscribe((response: any) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

  // ✅ FIXED METHOD
  getEntrys() {
    this.service.get('ehs/vesselEntry.php?type=VesselLog&from_date=' + this.from_date + '&to_date=' + this.to_date)
      .subscribe((response: any) => {

        this.entrys = response.map((item: any) => {
          return {
            ...item,
            validity_from: this.convertToDate(item.validity_from),
            validity_to: this.convertToDate(item.validity_to),
            date: this.convertFullDate(item.date)
          };
        });

        console.log("Updated Entrys:", this.entrys);
      });
  }

  // ✅ Convert time string → Date
  convertToDate(time: string) {
    if (!time) return null;

    const today = new Date();
    const parts = time.split(':');

    if (parts.length < 2) return null;

    today.setHours(+parts[0], +parts[1], 0);
    return new Date(today);
  }

  // ✅ Convert date safely
  convertFullDate(dateStr: string) {
    if (!dateStr) return null;
    return new Date(dateStr);
  }

  // ✅ FIXED VIEW METHOD
  view(index: number) {
    const data = this.entrys[index];

    this.selectedBatch = {
      ...data,
      validity_from: this.convertToDate(data.validity_from),
      validity_to: this.convertToDate(data.validity_to)
    };

    this.selectedCondition = this.selectedBatch['conditions'];
    this.isView = true;
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  download() {
    this.service.open('ehs/vesselEntry.php?type=downloadVesselLog&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

  downloadLog() {
    this.service.open('ehs/vesselEntry.php?type=downloadLog&id=' + this.selectedBatch['id']);
  }
}