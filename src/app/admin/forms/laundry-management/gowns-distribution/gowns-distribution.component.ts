import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-gowns-distribution',
  templateUrl: './gowns-distribution.component.html',
  styleUrls: ['./gowns-distribution.component.css']
})
export class GownsDistributionComponent implements OnInit {

  isView = false;
  isNew = false;

  selectedEntry;
  Gowns = [];
  emp_name ='';
  GownsForm : FormGroup;
  list;
  department_name ='';
  department;
  employee;
  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {

    this.GownsForm = this.fb.group({
      emp_name: ['', [Validators.required]],
      size: ['', [Validators.required]],
      no_units: ['', [Validators.required]],
    });

   }

  ngOnInit() {
    this.getGownsList();
    this.getDepartments();
  }

  addGowns() {
    this.Gowns.push({
      emp_name: this.GownsForm.value.emp_name,
      size: this.GownsForm.value.size,
      no_units: this.GownsForm.value.no_units
    });
    this.GownsForm.reset();
  }

  deleteGowns(index) {
    this.Gowns.splice(index, 1);
  }

  viewEntry(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getGownsList() {
    this.service.get('admin.php?type=getGownsList').subscribe(response => {
      this.list = response;
    });
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.department = response;
    });
  }

  getEmployee() {
    this.employee = undefined;
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + this.department_name).subscribe(response => {
      this.employee = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('department_name', data.value.department_name);
      formData.append('gowns', JSON.stringify(this.Gowns));

      this.service.post('admin.php?type=saveGownsEntry', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getGownsList();
          data.resetForm();
          this.isNew = false;
          this.Gowns = [];
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }

  close() {
    this.router.navigate(['/laundry-dashboard']);
  }

}



