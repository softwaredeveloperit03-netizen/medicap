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
  new_capa_dtl;
  close_cmt_capa_hod;
  constructor(private service: DataAccessService) {}
  department_name = localStorage.getItem('department');

  ngOnInit(): void {
    this.getInprocessCapa();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.getAssessment();
    this.getAssessment1();
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

  getInprocessCapa() {
    this.service
      .get(
        'qms/capa2.php?type=getDeptChckCAPA&deptName=' +
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
  desc_immidiateAction;
  reason_first_alt_tcd;
  reason_Second_alt_tcd;
  update(value) {
    let temp = {};

    temp['new_capa_dtl'] = this.new_capa_dtl;
    temp['close_cmt_capa_hod'] = this.close_cmt_capa_hod;
    // temp['qa_head_remark'] = this.qa_head_remark;
    // uploadData.append('origin',JSON.stringify(origin1));
    this.service
      .post(
        'qms/capa2.php?type=checkDeptHead&status=' +
          value +
          '&capa_no=' +
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
