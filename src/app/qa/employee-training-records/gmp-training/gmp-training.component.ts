import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate, hasStamp } from '../training-records.utils';

declare let alertify: any;

interface AttendanceRow {
  employee_name: string;
  signature: string;
  attendance_date: string;
  comments: string;
}

@Component({
  selector: 'app-gmp-training',
  templateUrl: './gmp-training.component.html',
  styleUrls: ['../training-records.shared.css'],
  providers: [DatePipe],
})
export class GmpTrainingComponent implements OnInit {
  formNo = 'FQA-003-B';
  revisionNo = '00';
  effectiveDate = '2025-03-24';
  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';
  stamping: string | null = null;

  trainer = '';
  trainingDate = '';
  aidsUsed = '';
  attendanceRows: AttendanceRow[] = Array.from({ length: 8 }, () => this.emptyRow());

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.trainingDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) this.getLog();
  }

  emptyRow(): AttendanceRow {
    return { employee_name: '', signature: '', attendance_date: '', comments: '' };
  }

  addRow(): void {
    this.attendanceRows.push(this.emptyRow());
  }

  removeRow(index: number): void {
    if (this.attendanceRows.length > 1) this.attendanceRows.splice(index, 1);
  }

  getLog(): void {
    this.service
      .get('qa/employeeTrainingRecords.php?type=getGmpTrainingLog&from_date=' + this.fromDate + '&to_date=' + this.toDate)
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.trainer.trim()) {
      alertify.error('Please fill required fields');
      return;
    }
    const payload = {
      trainer: this.trainer,
      training_date: this.trainingDate,
      aids_used: this.aidsUsed,
      attendance_rows: this.attendanceRows.filter((r) => r.employee_name.trim()),
    };
    this.service
      .post('qa/employeeTrainingRecords.php?type=saveGmpTraining', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/employee-training-records/gmp/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  stampRow(record: any, rowIndex: number): void {
    const key = record.id + '-' + rowIndex;
    if (hasStamp(record.attendance_rows?.[rowIndex]?.signature) || this.stamping === key) return;
    this.stamping = key;
    this.service
      .post('qa/employeeTrainingRecords.php?type=updateGmpTrainingStamp', JSON.stringify({ id: record.id, row_index: rowIndex }))
      .subscribe(
        (response: any) => {
          this.stamping = null;
          if (response?.status === 'success') {
            record.attendance_rows = response.attendance_rows;
            if (this.selectedRecord?.id === record.id) this.selectedRecord.attendance_rows = response.attendance_rows;
            alertify.success('Signature saved.');
          } else {
            alertify.error(response?.status || 'Failed to stamp');
          }
        },
        () => {
          this.stamping = null;
          alertify.error('Failed to stamp');
        }
      );
  }

  hasStamp = hasStamp;

  downloadForm(id: number): void {
    this.service.open('qa/employeeTrainingRecords.php?type=downloadGmpTrainingForm&id=' + id);
  }
}
