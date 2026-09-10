import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initial-pq',
  templateUrl: './initial-pq.component.html',
  styleUrls: ['./initial-pq.component.css'],
})
export class InitialPqComponent implements OnInit {
  department = [];
  List = [];
  departments;
  designations;
  newResponsibility: string = '';
  departments1;
  designations1;
  employees: Object;
  responsibilities: any;
  designation1: any;
  department1: any;
  Rlist: { Responsibilities: string }[] = [];
  selectedResult: any;
  async ngOnInit(): Promise<void> {
    this.getDepartments();
  }
  constructor(private service: DataAccessService, private router: Router) {}

  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = {
      Department: data.value.department,
      Designation: data.value.designation,
    };
    this.department.push(temp);
    data.resetForm();
  }

  delData(index) {
    this.department.splice(index, 1);
  }

  getDepartments() {
    this.service
      .get('hr/employee.php?type=get_department_by_designation')
      .subscribe((response) => {
        this.departments = response;
      });
  }

  getDesignation(data) {
    let department = data.value;
    for (let i = 0; i < this.departments.length; i++) {
      if (this.departments[i].department_name === department) {
        this.designations = this.departments[i].designations;
        break;
      }
    }
  }

  getEmployees(value) {
    this.service
      .get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + value)
      .subscribe(
        (response) => {
          this.employees = response;
        },
        (error) => {
          console.error('Error fetching employees:', error);
        }
      );
  }

  addMain(data) {
    let temp = data.value;

    if (!data.valid) {
      alertify.error('Please select department and designation!');
      return;
    }

    temp['Rlist'] = this.Rlist;

    this.List.push(temp);
    data.resetForm();
    this.Rlist = [];
  }

  delData1(index) {
    this.List.splice(index, 1);
  }

  // addrespott(i) {
  //   if (this.Responsibilities) {
  //     this.Rlist.push({
  //       Responsibilities: this.Responsibilities,
  //     });
  //     this.Responsibilities = '';
  //   }
  // }

  addResponsibility() {
    if (this.newResponsibility.trim() !== '') {
      this.Rlist.push({ Responsibilities: this.newResponsibility });
      this.newResponsibility = ''; // Clear the input field after adding responsibility
    }
  }

  deleteResponsibility(index: number) {
    this.Rlist.splice(index, 1);
  }
  savePq(data) {
    let temp = data.value;
    temp['List'] = this.List;
    temp['department'] = this.department;

    console.log(temp);
    this.service
      .post('qa/qualification.php?type=savePq', JSON.stringify(temp))
      .subscribe((response) => {
        console.log('Response from server:', response);
        if (response['status'] == 'success') {
          alert('saved successfully');
          data.resetForm();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
      data.resetForm();
  }


  // Save(addForm) {
  //   let res = addForm.value;
  //   this.service
  //     .post('/qms/incident.php?type=saveCAPAformB',
  //     .subscribe((response) => {
  //       console.log('Response from server:', response);
  //       if (response['status'] == 'success') {
  //         addForm.resetForm();
  //         this.router.navigate(['/qa/capa']);
  //         alertify.success('Successfully Saved');
  //       } else {
  //         alert('An error occurred');
  //       }
  //     });
  // }
}


