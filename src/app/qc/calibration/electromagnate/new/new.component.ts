import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  results: any[] = [];
  selectedResult: any = {};
  isView = false;
  from_date = '';
  to_date = '';
  today = '';

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.get_rights();
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

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
}
