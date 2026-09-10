import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-assmentbyqa',
  templateUrl: './assmentbyqa.component.html',
  styleUrls: ['./assmentbyqa.component.css'],
})
export class AssmentbyqaComponent implements OnInit {
  departmentList: any[] = [];
  equipmentList: any[] = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCcForAssementByQa();
    this.getDepartments();
    this.getEquipments();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departmentList = response || [];
    }, () => {
      this.departmentList = [];
    });
  }

  getEquipments() {
    const plant_id = localStorage.getItem('plant_id') || '';
    this.service.get('common.php?type=getEquipments&plant_id=' + plant_id).subscribe((response: any) => {
      this.equipmentList = response || [];
    }, () => {
      this.equipmentList = [];
    });
  }

  onEquipmentSelect(ev: Event, form: any) {
    const select = ev.target as HTMLSelectElement;
    const idx = select.selectedIndex;
    if (idx > 0 && this.equipmentList[idx - 1] && form && form.form) {
      form.form.patchValue({ equipmentCode: this.equipmentList[idx - 1].equipment_code || '' });
    }
  }

  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcForAssementByQa() {
    this.service
      .get(
        'changecontrol1.php?type=getCcDeptComByQa&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i) {
    this.selectedResult = this.results[i];
    this.isView = true;
  }

  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
    window.open(url, '_blank');
  }
  departmentChangeData = [];

  addDepartmentChange(data) {
    if (!data.valid) {
      alert('All Fields Are Required!!!!');
      return;
    }
    let temp = data.value;
    this.departmentChangeData.push(temp);
    data.reset();
  }

  delDepartmentChange(i) {
    this.departmentChangeData.splice(i, 1);
  }

  equipmentChangeData = [];

  addEquipmentChange(data) {
    if (!data.valid) {
      alert('All Fields Are Required!!!!');
      return;
    }
    let temp = data.value;
    this.equipmentChangeData.push(temp);
    data.reset();
  }

  delEquipmentChange(i) {
    this.equipmentChangeData.splice(i, 1);
  }

  consentRevDoc: File;
  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.consentRevDoc = event.target.files[0];
    }
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const temp = data.value;

    temp['id'] = this.selectedResult['id'];
    temp['ccNo'] = this.selectedResult['ctrl_no'];
    temp['deptName'] = localStorage.getItem('department');
    temp['equipmentChangeData'] = this.equipmentChangeData;
    temp['departmentChangeData'] = this.departmentChangeData;
    this.service
      .post('changecontrol1.php?type=saveMehaAssByQA', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alert('Assessment By QA  Saved Successfully !!!!!!');
          this.getCcForAssementByQa();
          data.resetForm();
          this.isView = false;
          this.selectedResult = [];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
}
