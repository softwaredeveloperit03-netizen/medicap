import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css'],
  providers: [DatePipe],
})
export class FormComponent implements OnInit {
  results: any[] = [];
  selectedResult: any = {};
  isView = false;
  from_date = '';
  to_date = '';
  today = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.getDailyEquipments();
  }

  getDailyEquipments() {
    this.service
      .get(
        'qc/calibration.php?type=getDailyEquipments&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(index: number) {
    this.selectedResult = { ...(this.results[index] || {}) };
    this.isView = true;
    this.loadRecords();
  }

  loadRecords() {
    if (!this.selectedResult?.equipment_code) {
      return;
    }
    this.service
      .get(
        'qc/calibration.php?type=getDailyEquipments&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response: any) => {
        const list = Array.isArray(response) ? response : [];
        const match = list.find(
          (row) => row.equipment_code === this.selectedResult.equipment_code
        );
        if (match) {
          this.selectedResult = { ...match };
        }
      });
  }

  closeForm() {
    this.isView = false;
  }

  check(j: number) {
    const records = this.selectedResult['records'] || [];
    const record = { ...(records[j] || {}) };
    const weights = [...(this.selectedResult['weights'] || [])];
    let status = 'OK';
    for (let i = 0; i < weights.length; i++) {
      const weight = { ...weights[i] };
      if (
        +weight['limit_from'] <= +weight['display_weight'] &&
        +weight['limit_to'] >= +weight['display_weight']
      ) {
        status = 'OK';
      } else {
        status = 'NOT OK';
        break;
      }
      weights[i] = weight;
    }
    record['status1'] = status;
    records[j] = record;
    this.selectedResult = {
      ...this.selectedResult,
      records,
      weights,
    };
  }

  save(equipment: any) {
    const temp = {
      equipment_code: this.selectedResult['equipment_code'],
      weights: this.selectedResult['weights'],
      status1: equipment.status1,
      remark: equipment.remark,
      done_by: equipment.done_by,
    };
    this.service
      .post('qc/calibration.php?type=saveDailyCalibration', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Record Save Successfully !!');
          this.isView = false;
          this.getDailyEquipments();
        } else {
          alertify.error('Error to save records !!');
        }
      });
  }

  downloadReport() {
    this.service.open(
      'qc/calibration.php?type=downloadDailyEquipmentRwport&id=' +
        this.selectedResult['id'] +
        '&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
}
