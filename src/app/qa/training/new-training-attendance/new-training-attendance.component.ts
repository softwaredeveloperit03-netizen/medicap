import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new-training-attendance',
  templateUrl: './new-training-attendance.component.html',
  styleUrls: ['./new-training-attendance.component.css'],
})
export class NewTrainingAttendanceComponent implements OnInit {
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

  formData = {
    trainingTopic: '',
    requiredOutcomes: '',
    documentsInvolved: '',
    dateOfTraining: '',
    trainerName: '',
    trainerSignDate: '',
  };

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getEmployees1();
    this.getTrainers();
    this.getTrainingNeeds();
    this.getsubject();
    this.getDepartments();
    this.get_rights();
  }

  getTrainingNeeds() {
    this.service
      .get('training.php?type=getTrainingNeeds')
      .subscribe((response) => {
        this.trainings = response;
        console.log(response);
      });
  }
  departments;
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  employees1;

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
    console.log('inside addEmployees');
    if (this.selectedEmp.length !== 0) {
      let len = Object.keys(this.employeeList).length;
      this.employeeList[len] = this.selectedEmp;
      this.selectedEmp = [];
      console.log(this.employeeList);
    }
  }

  saveTrainingNeeds(data) {
    let res = data.value;
    // console.log(res)
    // let temp = {}
    // temp['trainingTopic'] = res['trainingTopic']
    // temp['Outcome'] = res['Outcome']
    // temp['documentsInvolved'] = res['documentsInvolved']
    // temp['dateOfTraining']=res['dateOfTraining']
    // temp['Facilitator'] = res['Facilitator']
    // temp['Facilitator_date']=res['Facilitator_date']
    // temp['Position_title']=res['Position_title']
    res['employeeList'] = this.employeeList;

    this.service
      .post(
        'training.php?type=saveAttendanceTrainingNeeds',
        JSON.stringify(res)
      )
      .subscribe((response) => {
        console.log('Response from server:', response);
        if (response['status'] == 'success') {
          alert('New Training Seminar Attendance Saved');
          this.getTrainingNeeds();
          this.isNewTraining = false;
        } else {
          alert('An error occurred');
        }
      });
  }

  add_subject;

  checkOther(value) {
    if (value == 'Other') {
      this.add_subject = '';
      this.add_subject = true;
    }
  }

  subject_list;

  getsubject() {
    this.service.get('training.php?type=getsubject').subscribe((response) => {
      this.subject_list = response;
    });
  }

  subject_name;

  add_subject_name() {
    this.service
      .get('training.php?type=addsubjects&subject=' + this.subject_name)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getsubject();
          this.add_subject = false;
          console.log(response);
          alertify.success('Subject saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }

  viewNeeds(index) {
    this.selectedNeed = this.trainings[index];
    this.isView = true;
  }

  downloadreport() {
    this.service.open('pdf1/training.php?type=identificationneedslog');
  }

  downloadview(value) {
    this.service.open(
      'pdf1/training.php?type=identificationoftrainingneeds&id=' + value
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

  removeEmployee(index: number) {
    this.employeeList.splice(index, 1);
  }
}
