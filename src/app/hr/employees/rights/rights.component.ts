import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  OnInit,
} from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-rights',
  templateUrl: './rights.component.html',
  styleUrls: ['./rights.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RightsComponent implements OnInit {
  results: any[] = [];
  filteredResults: any[] = [];
  allResults: any[] = [];
  departments: any[] = [];
  searchText = '';
  search_department = 'ALL EMP';
  loading = false;
  saving = false;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  get displayCount(): string {
    if (this.searchText && this.searchText.trim() !== '') {
      return `${this.results.length} of ${this.allResults.length}`;
    }
    return this.results.length.toString();
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getRights();
  }

  getRights(): void {
    this.loading = true;
    this.cdr.markForCheck();
    if (!this.search_department || this.search_department === '') {
      this.search_department = 'ALL EMP';
    }
    const encodedDepartment = encodeURIComponent(this.search_department);
    this.service
      .get('hr/employee.php?type=getRightsData&search_department=' + encodedDepartment)
      .subscribe({
        next: (response: any) => {
          if (response && Array.isArray(response)) {
            this.allResults = this.removeDuplicates(response, 'emp_id');
            this.applyFilters();
          } else {
            this.allResults = [];
            this.results = [];
            this.filteredResults = [];
          }
          this.loading = false;
          this.cdr.markForCheck();
        },
        error: () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Failed to load user rights data. Please try again!');
          }
          this.allResults = [];
          this.results = [];
          this.filteredResults = [];
          this.loading = false;
          this.cdr.markForCheck();
        },
      });
  }

  applyFilters(): void {
    let filtered = [...this.allResults];
    if (this.searchText && this.searchText.trim() !== '') {
      const searchLower = this.searchText.toLowerCase().trim();
      filtered = filtered.filter((user) => {
        const empId = (user.emp_id || '').toString().toLowerCase();
        const firstName = (user.firstname || '').toLowerCase();
        const middleName = (user.middlename || '').toLowerCase();
        const lastName = (user.lastname || '').toLowerCase();
        const fullName = `${firstName} ${middleName} ${lastName}`.trim();
        const department = (user.department || '').toLowerCase();
        const designation = (user.designation || '').toLowerCase();
        return (
          empId.includes(searchLower) ||
          firstName.includes(searchLower) ||
          lastName.includes(searchLower) ||
          fullName.includes(searchLower) ||
          department.includes(searchLower) ||
          designation.includes(searchLower)
        );
      });
    }
    filtered = this.sortByEmpId(filtered);
    this.filteredResults = filtered;
    this.results = filtered;
    this.cdr.markForCheck();
  }

  sortByEmpId(array: any[]): any[] {
    if (!array || !Array.isArray(array)) return [];
    return array.sort((a, b) => {
      const empIdA = (a.emp_id || '').toString().trim();
      const empIdB = (b.emp_id || '').toString().trim();
      const numA = parseFloat(empIdA);
      const numB = parseFloat(empIdB);
      if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
      return empIdA.localeCompare(empIdB, undefined, { numeric: true, sensitivity: 'base' });
    });
  }

  onSearchChange(): void {
    this.applyFilters();
  }

  clearSearch(): void {
    this.searchText = '';
    this.applyFilters();
  }

  removeDuplicates(array: any[], key: string): any[] {
    if (!array || !Array.isArray(array)) return [];
    const seen = new Set();
    const unique: any[] = [];
    for (const item of array) {
      const keyValue = item[key];
      if (keyValue != null && keyValue !== '' && !seen.has(keyValue)) {
        seen.add(keyValue);
        const copy = { ...item };
        if (copy.check === undefined) copy.check = false;
        copy.isuser = copy.isuser || 'No';
        copy.ischecker = copy.ischecker || 'No';
        copy.isapprover = copy.isapprover || 'No';
        copy.qms_approver = copy.qms_approver || 'No';
        copy.isauditor = copy.isauditor || 'No';
        copy.dept_head = copy.dept_head || 'No';
        copy.shift_allocator = copy.shift_allocator || 'No';
        copy.trainig_cordinator = copy.trainig_cordinator || 'No';
        copy.task_assigner = copy.task_assigner || 'No';
        unique.push(copy);
      }
    }
    return unique;
  }

  trackByEmpId(_index: number, item: any): any {
    return item.emp_id || _index;
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

  submitright(): void {
    const selectedItems = this.results.filter((term) => term.check === true);
    if (selectedItems.length === 0) {
      if (typeof alertify !== 'undefined') {
        alertify.warning('Please select at least one employee to update.');
      } else alert('Please select at least one employee to update.');
      return;
    }
    const validItems = selectedItems.filter((item) => item.emp_id);
    if (validItems.length === 0) {
      if (typeof alertify !== 'undefined') alertify.error('Selected items are invalid.');
      return;
    }
    this.results.forEach((item) => (item.check = false));
    this.saving = true;
    this.cdr.markForCheck();
    this.service
      .post('hr/employee.php?type=update_empRights', JSON.stringify(validItems))
      .subscribe({
        next: (response: any) => {
          this.saving = false;
          this.cdr.markForCheck();
          if (response && response['status'] === 'success') {
            if (typeof alertify !== 'undefined') alertify.success('Data Updated Successfully!');
            this.getRights();
            this.searchText = '';
          } else {
            if (typeof alertify !== 'undefined') alertify.error('Failed. Please try again!');
          }
        },
        error: () => {
          this.saving = false;
          this.cdr.markForCheck();
          if (typeof alertify !== 'undefined') alertify.error('Failed to update. Please try again!');
        },
      });
  }

  fullName(user: any): string {
    return [user.firstname, user.middlename, user.lastname].filter(Boolean).join(' ');
  }
}
