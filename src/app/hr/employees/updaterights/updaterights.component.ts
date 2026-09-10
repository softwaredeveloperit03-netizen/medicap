import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-updaterights',
  templateUrl: './updaterights.component.html',
  styleUrls: ['./updaterights.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UpdaterightsComponent implements OnInit {
  results: any[] = [];
  allResults: any[] = [];
  departments: any[] = [];
  search_department = 'ALL EMP';
  loggedInEmpId: string | null = null;
  isViewChange = false;
  selectedResult: any = {};
  selectedEmployeeForViewChange: any = null;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.loggedInEmpId = localStorage.getItem('emp_id');
    this.getDepartments();
    this.getRights();
  }

  getRights(): void {
    if (this.search_department === '' || this.search_department == null) {
      this.search_department = 'ALL EMP';
    }
    const encodedDepartment = encodeURIComponent(this.search_department);
    this.service
      .get('hr/employee.php?type=getRights_LogForUpdate&department_name=' + encodedDepartment)
      .subscribe({
        next: (response: any) => {
          if (response && Array.isArray(response)) {
            this.allResults = this.uniqueRightsRows(response);
            this.results = [...this.allResults];
          } else {
            this.allResults = [];
            this.results = [];
          }
          this.cdr.markForCheck();
        },
        error: () => {
          if (typeof alertify !== 'undefined') alertify.error('Failed to load rights data.');
          this.allResults = [];
          this.results = [];
          this.cdr.markForCheck();
        },
      });
  }

  getDepartments(): void {
    this.service.get('common.php?type=getDepartments').subscribe({
      next: (response: any) => {
        this.departments = Array.isArray(response) ? response : [];
        this.cdr.markForCheck();
      },
      error: () => {
        this.departments = [];
        this.cdr.markForCheck();
      },
    });
  }

  removeDuplicates(array: any[], key: string): any[] {
    if (!array || !Array.isArray(array)) return [];
    const seen = new Set();
    const unique: any[] = [];
    for (const item of array) {
      const keyValue = item[key];
      if (keyValue != null && keyValue !== '' && !seen.has(keyValue)) {
        seen.add(keyValue);
        unique.push(item);
      }
    }
    return unique;
  }

  /** One row per employee + rights department (not one row per employee only). */
  uniqueRightsRows(array: any[]): any[] {
    if (!array || !Array.isArray(array)) return [];
    const seen = new Set<string>();
    const unique: any[] = [];
    for (const item of array) {
      const empId = item.emp_id != null ? String(item.emp_id) : '';
      const rightsDept = (item.rightsDept || item.department || '').toString().trim();
      const rowKey = `${empId}::${rightsDept}`;
      if (!empId || seen.has(rowKey)) continue;
      seen.add(rowKey);
      unique.push({ ...item, rightsDept: rightsDept || item.department });
    }
    return unique;
  }

  rightsDepartmentLabel(row: any): string {
    return (row?.rightsDept || row?.department || 'N/A').toString();
  }

  viewChangeRights(user: any): void {
    if (!user) {
      return;
    }
    const source = user;
    const defaults = {
      isuser: 'No', ischecker: 'No', isapprover: 'No', qms_approver: 'No',
      isauditor: 'No', dept_head: 'No', trainig_cordinator: 'No', shift_allocator: 'No', task_assigner: 'No',
    };
    this.selectedEmployeeForViewChange = Object.assign({}, source);
    const rightsDept = source.rightsDept || source.department || '';
    this.selectedResult = Object.assign({}, defaults, source, {
      department: rightsDept,
      rightsDept,
    });
    this.isViewChange = true;
    this.cdr.markForCheck();
  }

  closeViewChangeModal(): void {
    this.isViewChange = false;
    this.selectedResult = {};
    this.selectedEmployeeForViewChange = null;
    this.cdr.markForCheck();
  }

  updateRightsFromModal(): void {
    if (!this.selectedResult || Object.keys(this.selectedResult).length === 0) {
      if (typeof alertify !== 'undefined') alertify.error('Please select an employee to update rights!');
      return;
    }
    if (!this.selectedResult.department && this.selectedResult.rightsDept) {
      this.selectedResult.department = this.selectedResult.rightsDept;
    }
    if (!this.selectedResult.department) {
      if (typeof alertify !== 'undefined') alertify.error('Rights department is missing; cannot save.');
      return;
    }
    const idParam = this.loggedInEmpId != null ? encodeURIComponent(this.loggedInEmpId) : '';
    const url = 'hr/employee.php?type=changeRights' + (idParam ? '&id=' + idParam : '');
    this.service.post(url, JSON.stringify(this.selectedResult)).subscribe({
      next: (response: any) => {
        if (response && response['status'] === 'success') {
          if (typeof alertify !== 'undefined') alertify.success('Data Updated Successfully!');
          this.closeViewChangeModal();
          this.getRights();
        } else {
          const err = response && response['status'] ? response['status'] : 'An error occurred';
          if (typeof alertify !== 'undefined') alertify.error('Failed: ' + err);
        }
        this.cdr.markForCheck();
      },
      error: () => {
        if (typeof alertify !== 'undefined') alertify.error('Failed to update rights. Please try again!');
        this.cdr.markForCheck();
      },
    });
  }

  trackByEmpId(_i: number, item: any): any {
    const dept = item?.rightsDept || item?.department || '';
    return `${item.emp_id || _i}::${dept}`;
  }
}
