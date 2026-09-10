import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  results: any[] = [];
  departments: any[] = [];
  designations: any[] = [];
  emp_department: string | null = null;
  designation: string | null = null;

  isapprover = 'No';
  loggedInDept: string | null = null;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    this.getDepartments();
    this.getRequestsLog();
  }

  get_rights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) {
      this.cdr.markForCheck();
      return;
    }
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          encodeURIComponent(empId) +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.isapprover = r.isapprover || 'No';
        this.cdr.markForCheck();
      });
  }

  getDepartments(): void {
    this.service
      .get('hr/employee.php?type=get_department_by_designation')
      .subscribe((response: any) => {
        this.departments = Array.isArray(response) ? response : [];
        this.cdr.markForCheck();
      });
  }

  getDesignation(data: { value: string }): void {
    const value = data?.value;
    if (!value || !this.departments?.length) {
      this.designations = [];
      this.cdr.markForCheck();
      return;
    }
    const found = this.departments.find(
      (d: any) => d && d.department_name === value
    );
    this.designations = found && Array.isArray(found.designations) ? found.designations : [];
    this.cdr.markForCheck();
  }

  getRequestsLog(): void {
    this.service.get('hr/password.php?type=getRequestsLog').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.cdr.markForCheck();
    });
  }

  trackByIndex(_index: number): number {
    return _index;
  }

  trackByDeptId(_index: number, dept: any): string {
    return (dept && dept.department_name) || '';
  }

  trackByDesId(_index: number, des: any): string {
    return (des && des.designation) || '';
  }
}
