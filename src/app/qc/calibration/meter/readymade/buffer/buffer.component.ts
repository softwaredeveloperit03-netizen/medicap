import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-buffer',
  templateUrl: './buffer.component.html',
  styleUrls: ['./buffer.component.css'],
})
export class BufferComponent implements OnInit {
  loading;
  results: any[] = [];
  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.get_rights();
    this.getReadymadeBuffer();
  }

  getReadymadeBuffer() {
    this.loading = true;
    this.service.get('qc/calibration/meter.php?type=getReadymadeBuffer').subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.results = [];
        this.loading = false;
      }
    );
  }
  // -----------------------------------------12th july------------------------------------------//

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
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
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
  //---------------------------------------------------------------------------------//
}
