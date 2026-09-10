import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isView = false;
  results;
  equipment_name = '';
  equipment_type = '';
  status = '';
  selectedResult = [];
  departs;
  department_name = '';

  constructor(private service: DataAccessService, private router: Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getEquipmentsLog();
    this.getDepart();
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
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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

  getEquipmentsLog() {
    this.service
      .get(
        'rnd/qa/master/equipment.php?type=getEquipmentsLog&equipment_type=' +
          this.equipment_type +
          '&equipment_name=' +
          this.equipment_name +
          '&status=' +
          this.status +
          '&department_name=' +
          this.department_name
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  edit() {
    this.router.navigate([
      'rnd/qa/master/equipment/edit/' +
        this.selectedResult['id'] +
        '/' +
        this.selectedResult['equipment_type'] +
        '/' +
        this.selectedResult['department'] +
        '/' +
        this.selectedResult['section'],
    ]);
  }
  getDepart() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departs = response;
    });
  }
}
