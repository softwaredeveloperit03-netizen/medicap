import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css'],
})
export class CheckingComponent implements OnInit {
  isView = false;
  results;

  selectedDev = [];
  remark = '';
  comment = '';
  plant_id: any;
  categories;
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getInprocessCapa();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.getAssessment();
    this.getAssessment1();
    this.getDepartments();
  }
  getAssessment() {
    this.categories = [
      { origin: 'Operation suspended / Hold', value: false },
      { origin: 'Status labeled & segregated / Covered', value: false },
      { origin: 'Additional Samples Collected', value: false },
      { origin: 'Activity Continued', value: false },
      { origin: 'Others', value: false },
      { origin: 'NA', value: false },
    ];
  }
  categories1;
  getAssessment1() {
    this.categories1 = [
      { origin: 'Area', value: false },
      { origin: 'Machine', value: false },
      { origin: 'Procedure', value: false },
      { origin: 'Person', value: false },
      { origin: 'Measurement', value: false },
      { origin: 'Other', value: false },
    ];
  }
  update1(value, i) {
    this.categories[i].status = value;
  }
  update2(value, i) {
    this.categories1[i].status = value;
  }

  // Models
  correctiveTask = { task: '', department: '', targetDate: '' };
  correctiveActions: any[] = [];

  preventiveTask = { task: '', department: '', targetDate: '' };
  preventiveActions: any[] = [];
  preventiveDepartments: any[] = [];
  correctiveDepartments: any[] = [];
  capaDepartments: string[] = [];
  addCorrectiveAction() {
    const { task, department, targetDate } = this.correctiveTask;

    if (task && department && targetDate) {
      this.correctiveActions.push({ ...this.correctiveTask });

      if (!this.correctiveDepartments.includes(department)) {
        this.correctiveDepartments.push(department);
      }

      this.correctiveTask = { task: '', department: '', targetDate: '' };

      // Update combined department list
      this.updateCapaDepartments();
    }
  }

  addPreventiveAction() {
    const { task, department, targetDate } = this.preventiveTask;

    if (task && department && targetDate) {
      this.preventiveActions.push({ ...this.preventiveTask });

      if (!this.preventiveDepartments.includes(department)) {
        this.preventiveDepartments.push(department);
      }

      this.preventiveTask = { task: '', department: '', targetDate: '' };

      // Update combined department list
      this.updateCapaDepartments();
    }
  }

  deleteCorrectiveAction(index: number) {
    this.correctiveActions.splice(index, 1);

    this.correctiveDepartments = this.correctiveActions
      .map((action) => action.department)
      .filter((value, index, self) => self.indexOf(value) === index);

    this.updateCapaDepartments();
  }

  deletePreventiveAction(index: number) {
    this.preventiveActions.splice(index, 1);

    this.preventiveDepartments = this.preventiveActions
      .map((action) => action.department)
      .filter((value, index, self) => self.indexOf(value) === index);

    this.updateCapaDepartments();
  }

  updateCapaDepartments() {
    this.capaDepartments = Array.from(
      new Set([...this.preventiveDepartments, ...this.correctiveDepartments])
    );
  }

  getInprocessCapa() {
    this.service
      .get(
        'qms/capa2.php?type=getPendingCAPA&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  departments;
  getDepartments() {
    return new Promise((res, rej) => {
      this.service
        .get('hr/employee.php?type=get_departmentMeha')
        .subscribe((response) => {
          this.departments = response;
          res(response);
          console.log(this.departments)
        });
    });
  }
  desc_immidiateAction;
  reason_first_alt_tcd;
  reason_Second_alt_tcd;
  update(value) {
    let temp = value.value;

    // let temp = {};

    temp['capaDepartments'] = this.capaDepartments;

    temp['preventiveActions'] = this.preventiveActions;
    temp['correctiveActions'] = this.correctiveActions;
    this.service
      .post(
        'qms/capa2.php?type=SevenStepCAPA&capa_no=' +
          this.selectedDev['capa_no'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getInprocessCapa();
          this.isView = false;
          alertify.success('Capa Updated Successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  viewFile1(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }
  viewFile2(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }
  viewFile3(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }
}  