import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-monitoring',
  templateUrl: './monitoring.component.html',
  styleUrls: ['./monitoring.component.css']
})
export class MonitoringComponent implements OnInit {

  sections = [];
  employees = [];
  departments = [];
  checkLists = [];

  observations: any = {};
  remark: any = {};
  informed_to: any = {};
  comment: any = {};

  reactiveForm: FormGroup;
  constructor(private service: DataAccessService, private fb: FormBuilder) {
    this.reactiveForm = this.fb.group({
      department: ['', [Validators.required ]],
      section: ['', [ Validators.required ]]
    });
  }

  ngOnInit(): void {
    this.getEmployees();
    this.getDepartments();
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployees').subscribe(response => {
      this.employees = JSON.parse(JSON.stringify(response));
    });
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = JSON.parse(JSON.stringify(response));
    });
  }

  getSection() {
    const department = this.reactiveForm.value.department;
    this.service.get('hrDepartment.php?type=getDepartmentSection&selectedDepartment=' + department).subscribe(response => {
      this.sections = JSON.parse(JSON.stringify(response));
    });
  }

  getGMPMonitoringChecklist() {
    const formData = new FormData();
    formData.append('department', this.reactiveForm.value.department);
    formData.append('section', this.reactiveForm.value.section);

    this.service.post('qaDepartment.php?type=getGMPMonitoringChecklistByDepartment', formData).subscribe(response => {
      this.checkLists = JSON.parse(JSON.stringify(response));
      this.checkLists.forEach((element, index) => {
        this.observations[index] = element.observation;
        this.remark[index] = element.remark;
        this.informed_to[index] = element.informed_to;
        this.comment[index] = element.comment;
      });
    });
  }

  add(index) {
    const formData = new FormData();
    formData.append('id', this.checkLists[index].id);
    formData.append('observation', this.observations[index]);
    formData.append('remark', this.remark[index]);
    formData.append('informed_to', this.informed_to[index]);
    formData.append('comment', this.comment[index]);

    this.service.post('qaDepartment.php?type=saveCheckpoint', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alert('success');
        this.getGMPMonitoringChecklist();
      } else {
        alert('Something went wrong');
      }
    });
  }

}
