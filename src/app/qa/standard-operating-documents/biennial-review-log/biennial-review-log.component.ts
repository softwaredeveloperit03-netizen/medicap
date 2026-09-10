import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  BiennialReviewRow,
  FORM_B_NO,
  SOP_REF,
  REVISION_NO,
  EFFECTIVE_DATE,
  createDefaultBiennialRows,
  createEmptyBiennialRow,
  defaultFromDate,
  defaultToDate,
} from '../sod.utils';

declare let alertify: any;

@Component({
  selector: 'app-biennial-review-log',
  templateUrl: './biennial-review-log.component.html',
  styleUrls: ['../sod.shared.css'],
  providers: [DatePipe],
})
export class BiennialReviewLogComponent implements OnInit {
  formNo = FORM_B_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;

  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  sopNumber = '';
  sopTitle = '';
  issuedByQaInitialDate = '';
  reviewRows: BiennialReviewRow[] = createDefaultBiennialRows();
  departments: any[] = [];
  rowEmployees: Record<number, any[]> = {};

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
      this.getDepartments();
    }
  }

  getDepartments(): void {
    this.service.get('common.php?type=getDepartments').subscribe(
      (response: any) => {
        this.departments = Array.isArray(response) ? response : [];
        if (!this.departments.length) {
          this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((res: any) => {
            this.departments = Array.isArray(res) ? res : [];
          });
        }
      },
      () => {
        this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((res: any) => {
          this.departments = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  getDepartmentName(dept: any): string {
    return dept?.department_name || dept?.department || dept?.name || '';
  }

  getEmployeesForRow(index: number): any[] {
    return this.rowEmployees[index] || [];
  }

  onDeptChange(index: number, department: string): void {
    const row = this.reviewRows[index];
    row.review_dept = department;
    row.review_by = '';
    row.review_by_emp_id = '';
    this.rowEmployees[index] = [];

    if (!department) {
      return;
    }

    this.service
      .get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + encodeURIComponent(department))
      .subscribe(
        (response: any) => {
          this.rowEmployees[index] = Array.isArray(response) ? response : [];
        },
        () => {
          this.rowEmployees[index] = [];
        }
      );
  }

  onEmployeeChange(index: number, empId: string): void {
    const row = this.reviewRows[index];
    const employees = this.getEmployeesForRow(index);
    const emp = employees.find((e) => (e.emp_id || '') === empId);
    row.review_by_emp_id = empId || '';
    if (emp) {
      row.review_by = this.getEmployeeLabel(emp);
    } else {
      row.review_by = '';
    }
  }

  getEmployeeLabel(emp: any): string {
    const name = [emp?.firstname, emp?.middlename, emp?.lastname].filter(Boolean).join(' ').trim();
    return (name || emp?.emp_id || '') + (emp?.emp_id ? ' (' + emp.emp_id + ')' : '');
  }

  getReviewDeptDisplay(row: BiennialReviewRow): string {
    return row.review_dept || '';
  }

  getReviewByDisplay(row: BiennialReviewRow): string {
    if (row.review_by) {
      return row.review_by;
    }
    return row.reviewed_by_dept || '';
  }

  formatRowDate(value: string): string {
    if (!value) {
      return '';
    }
    const formatted = this.datePipe.transform(value, 'dd-MM-yyyy');
    return formatted || value;
  }

  getLog(): void {
    this.service
      .get(
        'qa/sodDocuments.php?type=getBiennialReviewLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.service
      .get('qa/sodDocuments.php?type=getBiennialReviewLogById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  addRow(): void {
    this.reviewRows.push(createEmptyBiennialRow());
  }

  removeRow(index: number): void {
    if (this.reviewRows.length > 1) {
      this.reviewRows.splice(index, 1);
      delete this.rowEmployees[index];
    }
  }

  getFilledReviewRows(): BiennialReviewRow[] {
    return this.reviewRows.filter((row) =>
      [
        row.revision_no,
        row.effective_date,
        row.review_dept,
        row.review_by,
        row.change_control_no,
        row.comment,
        row.initial_date,
        row.reviewed_by_dept,
      ].some((value) => (value || '').toString().trim() !== '')
    );
  }

  save(form: NgForm): void {
    if (form.invalid) {
      alertify.error('Please fill required fields');
      return;
    }

    const filledRows = this.getFilledReviewRows();
    if (!filledRows.length) {
      alertify.error('Please add at least one review row');
      return;
    }

    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      sop_number: this.sopNumber,
      sop_title: this.sopTitle,
      issued_by_qa_initial_date: this.issuedByQaInitialDate,
      review_rows: filledRows,
    };

    this.service
      .post('qa/sodDocuments.php?type=saveBiennialReviewLog', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Biennial review log saved');
          this.router.navigate(['/qa/standard-operating-documents/biennial/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/sodDocuments.php?type=downloadBiennialReviewLogForm&id=' + id);
  }
}
