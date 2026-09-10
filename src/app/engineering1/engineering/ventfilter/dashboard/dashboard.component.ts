import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'critical', title: 'List of vent fltr - Criti', route: 'critical', icon: 'fa-filter', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'non-critical', title: 'List of vent fltr-Non criti', route: 'non-critical', icon: 'fa-utensils', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'replacement', title: 'Vent Filter replac Rec', route: 'replacement', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'insectocutor', title: 'Integrity test report', route: 'insectocutor', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'schedule', title: 'Schedule Vent fltr replac', route: 'schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'critical', title: 'List of vent filter - Critical', route: 'critical', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'non-critical', title: 'List of vent filter - Non critical', route: 'non-critical', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'replacement', title: 'Vent Filter replacement Record', route: 'replacement', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'shedule', title: 'Schedule Vent filter replacement', route: 'shedule', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
  ];

 constructor(private service: DataAccessService,) { 
  this.loggedInDept = localStorage.getItem('department');

 }
  ngOnInit() {
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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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

}
