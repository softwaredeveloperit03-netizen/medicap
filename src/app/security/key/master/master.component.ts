import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css'],
  providers: [DatePipe],
})
export class MasterComponent implements OnInit {
  department: any;
    key_no: any;
    section: any;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.loggedInDept = localStorage.getItem('department');
    this.loggedInDept = localStorage.getItem('department');
  }
  keys;
  ngOnInit() {
    this.getKeys();
    this.get_rights();
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

  const plant_id = localStorage.getItem('plant_id');

  this.service
    .get(`security/gatepass.php?type=getKeymaster&plant_id=${plant_id}`)
    .subscribe((res: any) => {

      console.log('API:', res);

      this.rights = res;

      if (res?.length > 0) {
        const r = res[0];

        this.department = r.department;
        this.key_no = r.key_no;
        this.section = r.section;
      }
    });
}
  //---------------------------------------------------------------------------------//

  getKeys() {
    this.service
      .get('security/gatepass.php?type=getKeymaster')
      .subscribe((response) => {
        this.keys = response;
      });
  }
  download() {
    this.service.open('security/gatepass.php?type=getKeyMasterpdf');
  }
}
