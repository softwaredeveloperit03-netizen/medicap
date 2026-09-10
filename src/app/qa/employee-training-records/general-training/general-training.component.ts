import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate, getEmpDisplayName, hasStamp } from '../training-records.utils';

declare let alertify: any;

interface TrainingRow {
  sop_policy_no: string;
  rev_no: string;
  procedure: string;
  read_understood_by: string;
  reviewed_by: string;
}

@Component({
  selector: 'app-general-training',
  templateUrl: './general-training.component.html',
  styleUrls: ['../training-records.shared.css', './general-training.component.css'],
  providers: [DatePipe],
})
export class GeneralTrainingComponent implements OnInit {
  formNo = 'FQA-003-A';
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

  employees: any[] = [];
  selectedEmployee: any = null;
  employeeName = '';
  employeeId = '';
  employeeSignature = '';
  position = '';
  department = '';
  trainingRows: TrainingRow[] = [this.emptyRow()];

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
    if (this.isLog) {
      this.getLog();
    } else {
      this.getEmployees();
    }
  }

  getEmployees(): void {
    this.service.get('employee.php?type=getEmployees').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.loadEmployeesFallback();
        }
      },
      () => this.loadEmployeesFallback()
    );
  }

  loadEmployeesFallback(): void {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
            this.employees = Array.isArray(res) ? res : [];
          });
        }
      },
      () => {
        this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
          this.employees = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  getEmployeeLabel(emp: any): string {
    const name = [emp?.firstname, emp?.middlename, emp?.lastname].filter(Boolean).join(' ').trim();
    return (name || emp?.emp_id || '') + (emp?.emp_id ? ' (' + emp.emp_id + ')' : '');
  }

  onEmployeeChange(): void {
    if (!this.selectedEmployee) {
      this.employeeName = '';
      this.employeeId = '';
      this.position = '';
      this.department = '';
      return;
    }

    this.employeeId = this.selectedEmployee.emp_id || '';
    this.employeeName = [this.selectedEmployee.firstname, this.selectedEmployee.middlename, this.selectedEmployee.lastname]
      .filter(Boolean)
      .join(' ')
      .trim();
    this.position = this.selectedEmployee.designation || '';
    this.department = this.selectedEmployee.department || '';

    if (this.employeeId) {
      this.loadPreviousTraining(this.employeeId);
    }
  }

  loadPreviousTraining(empId: string): void {
    const params =
      'emp_id=' +
      encodeURIComponent(empId) +
      '&employee_name=' +
      encodeURIComponent(this.employeeName);
    this.service
      .get('qa/employeeTrainingRecords.php?type=getGeneralTrainingByEmployee&' + params)
      .subscribe((response: any) => {
        if (!response || !response.id) {
          return;
        }
        if (response.position) {
          this.position = response.position;
        }
        if (response.department) {
          this.department = response.department;
        }
        const rows = Array.isArray(response.training_rows) ? response.training_rows : [];
        if (rows.length) {
          this.trainingRows = rows.map((row: TrainingRow) => ({
            sop_policy_no: row.sop_policy_no || '',
            rev_no: row.rev_no || '',
            procedure: row.procedure || '',
            read_understood_by: '',
            reviewed_by: '',
          }));
        }
      });
  }

  emptyRow(): TrainingRow {
    return { sop_policy_no: '', rev_no: '', procedure: '', read_understood_by: '', reviewed_by: '' };
  }

  addRow(): void {
    this.trainingRows.push(this.emptyRow());
  }

  removeRow(index: number): void {
    if (this.trainingRows.length > 1) {
      this.trainingRows.splice(index, 1);
    }
  }

  stampEmployeeSignature(): void {
    this.employeeSignature = getEmpDisplayName() + ' - ' + this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
  }

  getLog(): void {
    this.service
      .get(
        'qa/employeeTrainingRecords.php?type=getGeneralTrainingLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.selectedEmployee || !this.employeeName.trim()) {
      alertify.error('Please select employee and fill required fields');
      return;
    }
    const payload = {
      employee_id: this.employeeId,
      employee_name: this.employeeName,
      employee_signature: this.employeeSignature,
      position: this.position,
      department: this.department,
      training_rows: this.trainingRows.map((row) => ({
        sop_policy_no: row.sop_policy_no,
        rev_no: row.rev_no,
        procedure: row.procedure,
        read_understood_by: '',
        reviewed_by: '',
      })),
    };
    this.service
      .post('qa/employeeTrainingRecords.php?type=saveGeneralTraining', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/employee-training-records/general/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  stampRow(record: any, rowIndex: number, field: 'read_understood_by' | 'reviewed_by'): void {
    const key = record.id + '-' + rowIndex + '-' + field;
    if (hasStamp(record.training_rows?.[rowIndex]?.[field]) || this.stamping === key) {
      return;
    }
    this.stamping = key;
    this.service
      .post(
        'qa/employeeTrainingRecords.php?type=updateGeneralTrainingStamp',
        JSON.stringify({ id: record.id, row_index: rowIndex, field })
      )
      .subscribe(
        (response: any) => {
          this.stamping = null;
          if (response?.status === 'success') {
            record.training_rows = response.training_rows;
            if (this.selectedRecord?.id === record.id) {
              this.selectedRecord.training_rows = response.training_rows;
            }
            alertify.success('Stamp saved.');
          } else if (response?.status === 'already_stamped') {
            record.training_rows[rowIndex][field] = response.value;
            alertify.warning('Already stamped.');
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
    this.service.open('qa/employeeTrainingRecords.php?type=downloadGeneralTrainingForm&id=' + id);
  }
}
