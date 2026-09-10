import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results;
  isView = false;
  selectedResult;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getVolumetricMaster();
    this.get_rights();
  }

  view(i) {
    this.selectedResult = [];
    this.selectedResult = this.results[i];
    this.isView = true;
  }

  getVolumetricMaster() {
    this.service
      .get('qc/volumetric.php?type=getVolumetricMaster')
      .subscribe((response) => {
        this.results = response;
      });
  }

  download() {
    this.service.open('qc/volumetric.php?type=downloadVolumetricMaster');
  }
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  trainig_cordinator = 'No';
  rights;

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
        this.trainig_cordinator = this.rights[0].trainig_cordinator;
      });
  }
}
