import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate, hasStamp } from '../training-records.utils';

declare let alertify: any;

interface EmployeeRow {
  employee_name: string;
  read_understood: string;
}

@Component({
  selector: 'app-group-training',
  templateUrl: './group-training.component.html',
  styleUrls: ['../training-records.shared.css'],
  providers: [DatePipe],
})
export class GroupTrainingComponent implements OnInit {
  formNo = 'FQA-003-C';
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

  documentType = '';
  documentTypeOther = '';
  documentCodeRevision = '';
  documentTitle = '';
  note = '';
  employeeRows: EmployeeRow[] = Array.from({ length: 10 }, () => this.emptyRow());

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) this.getLog();
  }

  emptyRow(): EmployeeRow {
    return { employee_name: '', read_understood: '' };
  }

  addRow(): void {
    this.employeeRows.push(this.emptyRow());
  }

  removeRow(index: number): void {
    if (this.employeeRows.length > 1) this.employeeRows.splice(index, 1);
  }

  getLog(): void {
    this.service
      .get('qa/employeeTrainingRecords.php?type=getGroupTrainingLog&from_date=' + this.fromDate + '&to_date=' + this.toDate)
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.documentType || !this.documentTitle.trim()) {
      alertify.error('Please fill required fields');
      return;
    }
    const payload = {
      document_type: this.documentType,
      document_type_other: this.documentTypeOther,
      document_code_revision: this.documentCodeRevision,
      document_title: this.documentTitle,
      note: this.note,
      employee_rows: this.employeeRows.filter((r) => r.employee_name.trim()),
    };
    this.service
      .post('qa/employeeTrainingRecords.php?type=saveGroupTraining', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/employee-training-records/group/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  stampRow(record: any, rowIndex: number): void {
    const key = record.id + '-' + rowIndex;
    if (hasStamp(record.employee_rows?.[rowIndex]?.read_understood) || this.stamping === key) return;
    this.stamping = key;
    this.service
      .post('qa/employeeTrainingRecords.php?type=updateGroupTrainingStamp', JSON.stringify({ id: record.id, row_index: rowIndex }))
      .subscribe(
        (response: any) => {
          this.stamping = null;
          if (response?.status === 'success') {
            record.employee_rows = response.employee_rows;
            if (this.selectedRecord?.id === record.id) this.selectedRecord.employee_rows = response.employee_rows;
            alertify.success('Read & Understood saved.');
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
    this.service.open('qa/employeeTrainingRecords.php?type=downloadGroupTrainingForm&id=' + id);
  }
}
