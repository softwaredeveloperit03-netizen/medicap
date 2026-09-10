import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results = [];
  form: FormGroup;
  isView = false;
  selectedResult: any = {};
  installList = [];
  machineList = [];
  blankList = [];
  constructor(private service: DataAccessService, private formBuilder: FormBuilder) { }

  ngOnInit(): void {
    this.getIQLog();
  }

  getIQLog() {
    this.service.get('qa/qualification_iq.php?type=getRequest').subscribe(
      (response) => {
        const list = Array.isArray(response) ? response : [];
        this.results = list.map((row) => this.normalizeRequestRow(row));
      },
      () => {
        this.results = [];
      }
    );
  }

  /** Align qualificationreq / equipment_requirement field names for the table + form. */
  normalizeRequestRow(row: any) {
    if (!row) {
      return {};
    }
    return {
      ...row,
      department_name: row.department_name || row.department || '',
      section_name: row.section_name || row.section || row.room_name || '',
      equip_name: row.equip_name || row.equipment_name || '',
      make: row.make || '',
      capacity: row.capacity || '',
      area: row.area || row.section_name || row.section || '',
    };
  }

  view(index) {
    this.selectedResult = this.normalizeRequestRow(this.results[index]);
    this.installList = [];
    this.machineList = [];
    this.blankList = [];
    this.isView = true;
  }

  /** Open blank IQ form when no pending request list / user wants direct entry. */
  openBlankForm() {
    this.selectedResult = {
      department_name: '',
      section_name: '',
      equip_name: '',
      make: '',
      capacity: '',
      area: '',
      size: '',
    };
    this.installList = [];
    this.machineList = [];
    this.blankList = [];
    this.isView = true;
  }

  addbtn(data) {
    if (!data || !data.valid) {
      return;
    }
    this.installList[this.installList.length] = data.value;
    data.resetForm();
  }
  addmachinbtn(data) {
    if (!data || !data.valid) {
      return;
    }
    this.machineList[this.machineList.length] = data.value;
    data.resetForm();
  }

  deleteList(index) {
    this.installList.splice(index, 1);
  }
  deleteMachinList(index) {
    this.machineList.splice(index, 1);
  }
  addblankbtn(data) {
    if (!data || !data.valid) {
      return;
    }
    this.blankList[this.blankList.length] = data.value;
    data.resetForm();
  }
  deleteblank(index) {
    this.blankList.splice(index, 1);
  }
  save(data) {
    if (!data || !data.valid) {
      alertify.error('Please complete all required steps');
      return;
    }
    let temp = data.value || {};
    temp['equipment_name'] = this.selectedResult['equip_name'] || (temp.equipment && temp.equipment.equipment_name) || '';
    temp['department_name'] = this.selectedResult['department_name'] || (temp.department && temp.department.dept) || '';
    temp['section_name'] = this.selectedResult['section_name'] || '';
    temp['request_id'] = this.selectedResult['id'] || null;
    temp['installation_check'] = this.installList;
    temp['blank_check'] = this.blankList;
    temp['machine_check'] = this.machineList;
    this.service.post('qa/qualification_iq.php?type=saveIq', JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        alertify.success('IQ saved successfully');
        this.getIQLog();
        this.isView = false;
      } else {
        alertify.error(response['status'] || 'An error occurred');
      }
    });
  }
}
