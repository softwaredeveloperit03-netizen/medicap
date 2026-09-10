import { Location } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-master-checklist',
  templateUrl: './master-checklist.component.html',
  styleUrls: ['./master-checklist.component.css']
})
export class MasterCheckListComponent implements OnInit {

  list = [];
  sections = [];
  departments = [];
  checkLists = [];
  isView = true;
  isNew = false;
  selectedCheckList;
  reactiveForm: FormGroup;
  constructor(private service: DataAccessService, private location: Location, private fb: FormBuilder) {
    this.reactiveForm = this.fb.group({
      department: ['', [Validators.required]],
      area: ['', [Validators.required]],
      section: ['', [Validators.required]],
      checkpoint: ['']
    });
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getGMPMonitoring();
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
      console.log("ra",this.sections);
    });
  }

  getGMPMonitoring() {
    this.service.get('qaDepartment.php?type=getGMPMonitoring').subscribe(response => {
      this.list = JSON.parse(JSON.stringify(response));
    });
  }

  viewCheckList(index) {
    this.isView = false;
    this.selectedCheckList = this.list[index].checkpoints;
  }

  addCheckList() {
    this.checkLists[this.checkLists.length] = this.reactiveForm.value.checkpoint;
    this.reactiveForm.patchValue({ checkpoint: '' });
  }

  deleteCheckList(index) {
    this.checkLists.splice(index, 1);
  }

  approve(index) {
    const id = this.list[index].id;
    this.service.get('qaDepartment.php?type=approveGMPMonitoring&id=' + id).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.getGMPMonitoring();
      }
    });
  }

  submit(): void {
    const formData = new FormData();
    formData.append('department', this.reactiveForm.value.department);
    formData.append('area', this.reactiveForm.value.area);
    formData.append('section', this.reactiveForm.value.section);
    formData.append('checkpoint', this.checkLists.toString());

    this.service.post('qaDepartment.php?type=saveGMPMonitoring', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.reactiveForm.reset();
        this.checkLists = [];
        this.isNew = false;
        this.getGMPMonitoring();
      } else {
        alert('failed');
      }
    });
  }

  close(): void {
    this.location.back();
  }

}
