import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  isNewTraining = false;
  isOther = false;
  employees;
  trainers;
  emp = '';
  employeeList = [];
  selectedEmp = [];
  trainings;

  selectedNeed = [];
  isView = false;
  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getEmployees1();
    this.getTrainers();
    this.getTrainingNeeds();
    this.getDepartments();
    this.get_rights();
  }

  getTrainingNeeds() {
    this.service
      .get('training.php?type=getTrainingNeedsforneedbase')
      .subscribe((response) => {
        this.trainings = response;
      });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }
  employees1;
  departments;
  onCheckboxChange(event: Event) {
    const checkbox = event.target as HTMLInputElement;
    if (checkbox.checked) {
      console.log('Checkbox is checked');
      let len = this.employees1.length;
      for (let i = 0; i < len; i++) {
        let len = Object.keys(this.employeeList).length;
        this.employeeList[len] = this.employees1[i];
      }
    } else {
      console.log('Checkbox is unchecked');
      this.employeeList = [];
    }
  }

  getEmployees(value) {
    this.service
      .get(
        'hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + value
      )
      .subscribe((response) => {
        this.employees = response;
      });
  }

  getEmployees1() {
    this.service.get('employee.php?type=getEmployees').subscribe((response) => {
      this.employees = response;
      this.employees1 = response;
    });
  }

  selectEmp(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEmp = this.employees[index];
    }
  }

  getTrainers() {
    this.service
      .get('training.php?type=getExternalTrainers')
      .subscribe((response) => {
        this.trainers = response;
      });
  }

  addEmployees() {
    if (this.selectedEmp.length !== 0) {
      let len = Object.keys(this.employeeList).length;
      this.employeeList[len] = this.selectedEmp;
      this.selectedEmp = [];
    }
  }

  saveTrainingNeeds(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    data = data.value;
    data['subject'] = 'Need-Base Training';
    data['employees'] = this.employeeList;
    this.service
      .post('training.php?type=saveTrainingNeeds', JSON.stringify(data))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Training has been allocated to employee');
          this.getTrainingNeeds();
          this.isNewTraining = false;
        } else {
          alert('An error occured');
        }
      });
  }

  checkOther(value) {
    if (value === 'Other') {
      this.isOther = true;
    } else {
      this.isOther = false;
    }
  }

  viewNeeds(index) {
    this.selectedNeed = this.trainings[index];
    this.isView = true;
  }
  downloadreport() {
    this.service.open('pdf1/training.php?type=identificationneedslogs');
  }
  downloadview(value) {
    this.service.open('pdf1/training.php?type=trainingneedsviews&id=' + value);
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
}
