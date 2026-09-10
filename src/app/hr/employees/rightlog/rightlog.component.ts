import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';

declare let alertify: any;

@Component({
  selector: 'app-rightlog',
  templateUrl: './rightlog.component.html',
  styleUrls: ['./rightlog.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RightlogComponent implements OnInit {
  results: any[] = [];
  filteredResults: any[] = [];
  allResults: any[] = [];
  departments: any[] = [];
  search_department = 'ALL EMP';
  searchText = '';

  additionalRightsCache: { [empId: string]: any[] } = {};
  loadingAdditionalRights: { [empId: string]: boolean } = {};
  selectedEmployeeForView: any = null;
  isViewAdditional = false;
  selectedEmployeeForMainView: any = null;
  isViewMain = false;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  get displayCount(): string {
    if (this.searchText && this.searchText.trim() !== '') {
      return `${this.results.length} of ${this.allResults.length}`;
    }
    return this.allResults.length.toString();
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getRights();
  }

  getRights(): void {
    if (!this.search_department || this.search_department === '') {
      this.search_department = 'ALL EMP';
    }
    const encodedDepartment = encodeURIComponent(this.search_department);
    this.service
      .get('hr/employee.php?type=getRights_Log&department_name=' + encodedDepartment)
      .subscribe({
        next: (response: any) => {
          if (response && Array.isArray(response)) {
            this.allResults = this.removeDuplicates(response, 'emp_id');
            this.allResults.forEach((u) => {
              u.additionalRightsCount = 0;
              u.hasAdditionalRights = false;
            });
            this.applyFilters();
            this.preloadAdditionalRightsCounts();
          } else {
            this.allResults = [];
            this.results = [];
            this.filteredResults = [];
          }
          this.cdr.markForCheck();
        },
        error: () => {
          if (typeof alertify !== 'undefined') alertify.error('Failed to load user rights data.');
          this.allResults = [];
          this.results = [];
          this.filteredResults = [];
          this.cdr.markForCheck();
        },
      });
  }

  preloadAdditionalRightsCounts(): void {
    const batchSize = 10;
    for (let i = 0; i < this.allResults.length; i += batchSize) {
      const batch = this.allResults.slice(i, i + batchSize);
      setTimeout(() => {
        batch.forEach((user) => this.getAdditionalRightsCount(user.emp_id));
      }, i * 50);
    }
  }

  getAdditionalRightsCount(empId: string): void {
    if (!empId || this.loadingAdditionalRights[empId]) return;
    this.loadingAdditionalRights[empId] = true;
    this.cdr.markForCheck();
    const encodedEmpId = encodeURIComponent(empId);
    this.service
      .get('hr/employee.php?type=getaddDep_data&emp_id1=' + encodedEmpId)
      .subscribe({
        next: (response: any) => {
          if (response && Array.isArray(response)) {
            const uniqueDepts = this.removeDuplicateDepartments(response);
            const count = uniqueDepts.length;
            const user = this.allResults.find((u) => u.emp_id === empId);
            if (user) {
              user.additionalRightsCount = count;
              user.hasAdditionalRights = count > 0;
              this.additionalRightsCache[empId] = uniqueDepts;
            }
            const filteredUser = this.results.find((u) => u.emp_id === empId);
            if (filteredUser) {
              filteredUser.additionalRightsCount = count;
              filteredUser.hasAdditionalRights = count > 0;
            }
          }
          this.loadingAdditionalRights[empId] = false;
          this.cdr.markForCheck();
        },
        error: () => {
          this.loadingAdditionalRights[empId] = false;
          this.cdr.markForCheck();
        },
      });
  }

  viewMainRights(user: any): void {
    if (!user?.emp_id) {
      return;
    }
    this.selectedEmployeeForMainView = user;
    this.isViewMain = true;
    this.cdr.markForCheck();
  }

  closeMainModal(): void {
    this.selectedEmployeeForMainView = null;
    this.isViewMain = false;
    this.cdr.markForCheck();
  }

  closeAdditionalModal(): void {
    this.selectedEmployeeForView = null;
    this.isViewAdditional = false;
    this.cdr.markForCheck();
  }

  viewAdditionalRights(user: any): void {
    if (!user?.emp_id) {
      return;
    }
    if (this.additionalRightsCache[user.emp_id]) {
      this.selectedEmployeeForView = user;
      this.isViewAdditional = true;
      this.cdr.markForCheck();
      return;
    }
    this.loadingAdditionalRights[user.emp_id] = true;
    this.cdr.markForCheck();
    const encodedEmpId = encodeURIComponent(user.emp_id);
    this.service
      .get('hr/employee.php?type=getaddDep_data&emp_id1=' + encodedEmpId)
      .subscribe({
        next: (response: any) => {
          if (response && Array.isArray(response)) {
            this.additionalRightsCache[user.emp_id] = this.removeDuplicateDepartments(response);
          } else {
            this.additionalRightsCache[user.emp_id] = [];
          }
          this.loadingAdditionalRights[user.emp_id] = false;
          this.selectedEmployeeForView = user;
          this.isViewAdditional = true;
          this.cdr.markForCheck();
        },
        error: () => {
          this.loadingAdditionalRights[user.emp_id] = false;
          this.cdr.markForCheck();
        },
      });
  }

  applyFilters(): void {
    let filtered = [...this.allResults];
    if (this.searchText && this.searchText.trim() !== '') {
      const q = this.searchText.toLowerCase().trim();
      filtered = filtered.filter((user) => {
        const empId = (user.emp_id || '').toString().toLowerCase();
        const name = `${(user.firstname || '')} ${(user.lastname || '')}`.trim().toLowerCase();
        const dept = (user.department || '').toLowerCase();
        const des = (user.designation || '').toLowerCase();
        return empId.includes(q) || name.includes(q) || dept.includes(q) || des.includes(q);
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
        unique.push(item);
      }
    }
    return unique;
  }

  removeDuplicateDepartments(array: any[]): any[] {
    if (!array || !Array.isArray(array)) return [];
    const seen = new Set();
    const unique: any[] = [];
    for (const item of array) {
      const keyValue = (item.emp_id || '') + '_' + (item.department || '');
      if (keyValue && !seen.has(keyValue)) {
        seen.add(keyValue);
        unique.push(item);
      }
    }
    return unique;
  }

  trackByEmpId(_i: number, item: any): any {
    return item.emp_id || _i;
  }

  formatDate(dateString: string): string {
    if (!dateString) return 'N/A';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return dateString;
      return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch {
      return dateString;
    }
  }

  getAllocatedBy(user: any): string {
    if (user.update_by_firstname || user.update_by_lastname) {
      const name = `${user.update_by_firstname || ''} ${user.update_by_lastname || ''}`.trim();
      if (name) return name;
      if (user.update_by) return user.update_by;
    }
    if (user.entry_by_firstname || user.entry_by_lastname) {
      const name = `${user.entry_by_firstname || ''} ${user.entry_by_lastname || ''}`.trim();
      if (name) return name;
      if (user.entry_by) return user.entry_by;
    }
    return 'N/A';
  }

  getAllocatedDate(user: any): string {
    if (user.update_date) return this.formatDate(user.update_date);
    if (user.entry_date) return this.formatDate(user.entry_date);
    return 'N/A';
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

  exportToExcel(): void {
    if (!this.results?.length) {
      if (typeof alertify !== 'undefined') alertify.warning('No data available to export!');
      return;
    }
    const formattedData = this.results.map((user, index) => ({
      'S.No': index + 1,
      'Emp Id': user.emp_id || '',
      Name: `${user.firstname || ''} ${user.lastname || ''}`.trim(),
      Department: user.department || '',
      Designation: user.designation || '',
      'User Rights': user.isuser || 'No',
      'Additional Rights Count': user.additionalRightsCount || 0,
      'Allocated By': this.getAllocatedBy(user),
      Date: this.getAllocatedDate(user),
    }));
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { 'User Rights Log': worksheet },
      SheetNames: ['User Rights Log'],
    };
    XLSX.writeFile(workbook, 'User_Rights_Log.xlsx');
    if (typeof alertify !== 'undefined') alertify.success('Data exported successfully!');
  }
}
