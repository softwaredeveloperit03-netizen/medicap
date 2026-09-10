import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { getEmpDisplayName } from '../../employee-training-records/training-records.utils';

declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  formNo = 'FQA-006-A';
  revisionNo = '00';
  effectiveDate = '2025-04-01';

  employees: any[] = [];
  selectedEmployee: any = null;
  recordDate = '';
  employeeName = '';
  employeeId = '';
  initials = '';
  signature = '';
  dateOfEmployment = '';
  deptPosition = '';
  comment = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.recordDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.getEmployees();
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
      this.dateOfEmployment = '';
      this.deptPosition = '';
      return;
    }

    this.employeeId = this.selectedEmployee.emp_id || '';
    this.employeeName = [this.selectedEmployee.firstname, this.selectedEmployee.middlename, this.selectedEmployee.lastname]
      .filter(Boolean)
      .join(' ')
      .trim();

    const joiningDate = this.selectedEmployee.joining_date || '';
    if (joiningDate) {
      const parsed = new Date(joiningDate);
      this.dateOfEmployment = !isNaN(parsed.getTime())
        ? this.datePipe.transform(parsed, 'yyyy-MM-dd') || ''
        : joiningDate;
    } else {
      this.dateOfEmployment = '';
    }

    const dept = this.selectedEmployee.department || '';
    const position = this.selectedEmployee.designation || this.selectedEmployee.operator_category || '';
    this.deptPosition = [dept, position].filter(Boolean).join(' / ');
  }

  stampSignature(): void {
    this.signature = getEmpDisplayName() + ' - ' + this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
  }

  save(form: NgForm): void {
    if (form.invalid || !this.selectedEmployee || !this.employeeName.trim()) {
      alertify.error('Please select employee and fill required fields');
      return;
    }

    const payload = {
      form_no: this.formNo,
      record_date: this.recordDate,
      employee_id: this.employeeId,
      employee_name: this.employeeName,
      initials: this.initials,
      signature: this.signature,
      date_of_employment: this.dateOfEmployment,
      dept_position: this.deptPosition,
      comment: this.comment,
    };

    this.service
      .post('qa/employeeInitialsSignature.php?type=saveEmployeeInitialsSignature', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/employee-initials-signature/log']);
        } else {
          alertify.error(response?.status || 'Failed to save record');
        }
      });
  }
}
