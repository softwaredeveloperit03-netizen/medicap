import { Location } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-memo',
  templateUrl: './memo.component.html',
  styleUrls: ['./memo.component.css']
})
export class MemoComponent implements OnInit {

  sections = [];
  employees = [];
  departments = [];

  tempData = [];
  emp_id;

  reactiveForm: FormGroup;
  constructor(private service: DataAccessService, private location: Location, private fb: FormBuilder) {
    this.reactiveForm = this.fb.group({
      department: ['', [Validators.required]],
      area: ['', [Validators.required]],
      section: ['', [Validators.required]],
      employee: ['', [Validators.required]],
      designation: { value: '', disabled: true },
      shift: ['', [Validators.required]],
      repeated_failure: ['', [Validators.required]],
      failure_title: ['', [Validators.required]],
      failure_description: ['', [Validators.required]],
      remark: ['', [Validators.required]]
    });
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getEmployees();
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = JSON.parse(JSON.stringify(response));
    });
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployees').subscribe(response => {
      this.employees = JSON.parse(JSON.stringify(response));
    });
  }

  getSection() {
    const department = this.reactiveForm.value.department;
    this.service.get('hrDepartment.php?type=getDepartmentSection&selectedDepartment=' + department).subscribe(response => {
      this.sections = JSON.parse(JSON.stringify(response));
    });
  }

  onEmployeeChange(index) {
    const item = this.employees[index];
    this.emp_id = item.emp_id;
    this.reactiveForm.patchValue({
      designation: item.first_designation
    });
  }

  submit(): void {
    if (!this.reactiveForm.valid) {
      alert('All fields are required');
      return;
    }
    const formData = new FormData();
    formData.append('emp_id', this.emp_id);
    formData.append('department', this.reactiveForm.value.department);
    formData.append('area', this.reactiveForm.value.area);
    formData.append('section', this.reactiveForm.value.section);
    formData.append('shift', this.reactiveForm.value.shift);
    formData.append('repeated_failure', this.reactiveForm.value.repeated_failure);
    formData.append('failure_title', this.reactiveForm.value.failure_title);
    formData.append('failure_description', this.reactiveForm.value.failure_description);
    formData.append('remark', this.reactiveForm.value.remark);

    this.service.post('qaDepartment.php?type=saveGMPMemo', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alert('success');
      } else {
        alert('failed');
      }
    });


  }

  close(): void {
    this.location.back();
  }

}
