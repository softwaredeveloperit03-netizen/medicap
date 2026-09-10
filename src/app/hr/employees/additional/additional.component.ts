import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';

declare let alertify: any;

@Component({
  selector: 'app-additional',
  templateUrl: './additional.component.html',
  styleUrls: ['./additional.component.css'],
})
export class AdditionalComponent implements OnInit {
  results: any[] = [];
  filteredResults: any[] = [];
  allResults: any[] = [];
  departments: any[] = [];
  search_department = 'ALL EMP';
  searchText = '';
  returnTo = '/hr/user';

  get displayCount(): string {
    if (this.searchText && this.searchText.trim() !== '') {
      return `${this.results.length} of ${this.allResults.length}`;
    }
    return this.results.length.toString();
  }

  adddepartments: any[] = [];
  isAllocation = false;
  isViewAdditional = false;
  selctedEmp: any = null;
  emp_id1: any;
  emp_id: any;
  designation: any;
  firstname: any;
  lastname: any;
  /** Employee's HR primary department (display only). */
  primaryDepartment: any;
  /** Department selected in allocate / update form (additional rights). */
  allocateDepartment = '';
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  trainig_cordinator = 'No';
  isauditor = 'No';
  dept_head = 'No';
  shift_allocator = 'No';
  mainUserRights: any = {};

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    const returnTo = this.route.snapshot.queryParamMap.get('returnTo');
    if (returnTo) {
      this.returnTo = returnTo;
    }
    this.getDepartments();
    this.getRights();
  }

  getRights(): void {
    if (this.search_department === '' || this.search_department == null) {
      this.search_department = 'ALL EMP';
    }
    if (this.search_department === 'ALLEMP') {
      this.search_department = 'ALL EMP';
    }
    const encodedDepartment = encodeURIComponent(this.search_department);
    const apiUrl = 'hr/employee.php?type=getRights_Log&department_name=' + encodedDepartment;
    this.service.get(apiUrl).subscribe({
      next: (response: any) => {
        if (response && Array.isArray(response)) {
          this.allResults = this.removeDuplicates(response, 'emp_id');
          this.applyFilters();
        } else {
          this.allResults = [];
          this.results = [];
          this.filteredResults = [];
          if (typeof alertify !== 'undefined') {
            alertify.warning('No data available or invalid response format.');
          }
        }
      },
      error: () => {
        if (typeof alertify !== 'undefined') {
          alertify.error('Failed to load user rights data.');
        }
        this.allResults = [];
        this.results = [];
        this.filteredResults = [];
      },
    });
  }

  applyFilters(): void {
    let filtered = [...this.allResults];
    if (this.searchText && this.searchText.trim() !== '') {
      const searchLower = this.searchText.toLowerCase().trim();
      filtered = filtered.filter((user: any) => {
        const empId = (user.emp_id || '').toString().toLowerCase();
        const firstName = (user.firstname || '').toLowerCase();
        const lastName = (user.lastname || '').toLowerCase();
        const fullName = `${firstName} ${lastName}`.trim();
        const dept = (user.department || '').toLowerCase();
        const des = (user.designation || '').toLowerCase();
        return (
          empId.includes(searchLower) ||
          firstName.includes(searchLower) ||
          lastName.includes(searchLower) ||
          fullName.includes(searchLower) ||
          dept.includes(searchLower) ||
          des.includes(searchLower)
        );
      });
    }
    filtered = this.sortByEmpId(filtered);
    this.filteredResults = filtered;
    this.results = filtered;
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

  getDepartments(): void {
    this.service.get('common.php?type=getDepartments').subscribe({
      next: (response: any) => {
        this.departments = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.departments = [];
      },
    });
  }

  getaddDep_data(): void {
    if (!this.emp_id1) {
      this.adddepartments = [];
      return;
    }
    const encodedEmpId = encodeURIComponent(this.emp_id1);
    const apiUrl = 'hr/employee.php?type=getaddDep_data&emp_id1=' + encodedEmpId;
    this.service.get(apiUrl).subscribe({
      next: (response: any) => {
        if (response && Array.isArray(response)) {
          this.adddepartments = this.removeDuplicateDepartments(response);
        } else {
          this.adddepartments = [];
        }
      },
      error: () => {
        if (typeof alertify !== 'undefined') {
          alertify.error('Failed to load additional department rights.');
        }
        this.adddepartments = [];
      },
    });
  }

  viewAdditionalRights(index: number): void {
    if (index < 0 || index >= this.results.length) {
      if (typeof alertify !== 'undefined') alertify.error('Invalid employee selection!');
      return;
    }
    this.isViewAdditional = true;
    this.selctedEmp = this.results[index];
    this.emp_id1 = this.selctedEmp['emp_id'];
    this.firstname = this.selctedEmp['firstname'] || '';
    this.lastname = this.selctedEmp['lastname'] || '';
    this.designation = this.selctedEmp['designation'] || '';
    this.primaryDepartment = this.selctedEmp['department'] || '';
    this.mainUserRights = {
      isuser: this.selctedEmp['isuser'] || 'No',
      ischecker: this.selctedEmp['ischecker'] || 'No',
      isapprover: this.selctedEmp['isapprover'] || 'No',
      qms_approver: this.selctedEmp['qms_approver'] || 'No',
      trainig_cordinator: this.selctedEmp['trainig_cordinator'] || 'No',
      isauditor: this.selctedEmp['isauditor'] || 'No',
      dept_head: this.selctedEmp['dept_head'] || 'No',
      shift_allocator: this.selctedEmp['shift_allocator'] || 'No',
    };
    this.getaddDep_data();
  }

  addDep(index: number): void {
    if (index < 0 || index >= this.results.length) {
      if (typeof alertify !== 'undefined') alertify.error('Invalid employee selection!');
      return;
    }
    this.isAllocation = true;
    this.selctedEmp = this.results[index];
    this.emp_id1 = this.selctedEmp['emp_id'];
    this.emp_id = this.selctedEmp['emp_id'];
    this.firstname = this.selctedEmp['firstname'] || '';
    this.lastname = this.selctedEmp['lastname'] || '';
    this.designation = this.selctedEmp['designation'] || '';
    this.primaryDepartment = this.selctedEmp['department'] || '';
    this.isuser = 'No';
    this.ischecker = 'No';
    this.isapprover = 'No';
    this.qms_approver = 'No';
    this.trainig_cordinator = 'No';
    this.isauditor = 'No';
    this.dept_head = 'No';
    this.shift_allocator = 'No';
    this.allocateDepartment = '';
    this.getaddDep_data();
  }

  /** Load an existing additional-department row into the form to change No → Yes (or any value). */
  editExistingDeptRights(row: any): void {
    if (!row?.department) {
      if (typeof alertify !== 'undefined') alertify.warning('Invalid department row.');
      return;
    }
    this.allocateDepartment = row.department;
    this.isuser = row.isuser || 'No';
    this.ischecker = row.ischecker || 'No';
    this.isapprover = row.isapprover || 'No';
    this.qms_approver = row.qms_approver || 'No';
    this.trainig_cordinator = row.trainig_cordinator || 'No';
    this.isauditor = row.isauditor || 'No';
    this.dept_head = row.dept_head || 'No';
    this.shift_allocator = row.shift_allocator || 'No';
    if (typeof alertify !== 'undefined') {
      alertify.message('Update rights below and click SUBMIT to save "' + row.department + '".');
    }
  }

  resetAllocateForm(): void {
    this.allocateDepartment = '';
    this.isuser = 'No';
    this.ischecker = 'No';
    this.isapprover = 'No';
    this.qms_approver = 'No';
    this.trainig_cordinator = 'No';
    this.isauditor = 'No';
    this.dept_head = 'No';
    this.shift_allocator = 'No';
  }

  isDepartmentAlreadyAllocated(departmentName: string): boolean {
    if (!departmentName || !this.adddepartments || this.adddepartments.length === 0) {
      return false;
    }
    return this.adddepartments.some(
      (dept: any) =>
        dept.department &&
        dept.department.toLowerCase() === departmentName.toLowerCase()
    );
  }

  allocateRights(data: any): void {
    if (!data || !data.value) {
      if (typeof alertify !== 'undefined') alertify.error('Form data is invalid!');
      return;
    }
    const temp = { ...data.value, department: this.allocateDepartment };
    if (!this.selctedEmp || !this.selctedEmp['emp_id']) {
      if (typeof alertify !== 'undefined') alertify.error('Employee information is missing!');
      return;
    }
    temp['emp_id'] = this.selctedEmp['emp_id'];
    if (!temp['department'] || temp['department'] === '' || temp['department'] == null) {
      if (typeof alertify !== 'undefined') alertify.error('Please select a department!');
      return;
    }
    const selectedDept = temp['department'];
    const isDuplicate = this.isDepartmentAlreadyAllocated(selectedDept);
    if (isDuplicate && typeof alertify !== 'undefined') {
      alertify.warning(
        'This employee already has rights allocated for "' +
          selectedDept +
          '". The existing rights will be updated.'
      );
    }
    temp['isuser'] = temp['isuser'] || 'No';
    temp['ischecker'] = temp['ischecker'] || 'No';
    temp['isapprover'] = temp['isapprover'] || 'No';
    temp['qms_approver'] = temp['qms_approver'] || 'No';
    temp['trainig_cordinator'] = temp['trainig_cordinator'] || 'No';
    temp['isauditor'] = temp['isauditor'] || 'No';
    temp['dept_head'] = temp['dept_head'] || 'No';
    temp['shift_allocator'] = temp['shift_allocator'] || 'No';

    this.service
      .post('hr/employee.php?type=update_additional_empRights', JSON.stringify(temp))
      .subscribe({
        next: (response: any) => {
          if (response && response['status'] === 'success') {
            const message = response['message'] || 'Additional Department Rights Allocated Successfully!';
            if (typeof alertify !== 'undefined') alertify.success(message);
            this.isAllocation = false;
            this.resetAllocateForm();
            this.getRights();
            if (this.emp_id1) this.getaddDep_data();
            this.selctedEmp = null;
            this.emp_id1 = '';
          } else {
            const errorMsg = response && response['status'] ? response['status'] : 'An error occurred';
            if (typeof alertify !== 'undefined') alertify.error('Failed: ' + errorMsg);
          }
        },
        error: () => {
          if (typeof alertify !== 'undefined') alertify.error('Failed to allocate rights. Please try again!');
        },
      });
  }

  exportToExcel(): void {
    if (!this.results || this.results.length === 0) {
      if (typeof alertify !== 'undefined') alertify.warning('No data available to export!');
      return;
    }
    const formattedData = this.results.map((user: any, index: number) => ({
      'S.No': index + 1,
      'Emp Id': user.emp_id || '',
      Name: `${user.firstname || ''} ${user.lastname || ''}`.trim(),
      Department: user.department || '',
      Designation: user.designation || '',
      'User Rights': user.isuser || 'No',
      'Checker Rights': user.ischecker || 'No',
      'Approver Rights': user.isapprover || 'No',
      'QMS Approver': user.qms_approver || 'No',
      'Training Cordinator': user.trainig_cordinator || 'No',
      Auditor: user.isauditor || 'No',
      'Dept Head': user.dept_head || 'No',
      'Shift Management': user.shift_allocator || 'No',
    }));
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { 'Additional User Rights': worksheet },
      SheetNames: ['Additional User Rights'],
    };
    XLSX.writeFile(workbook, 'Additional_User_Rights.xlsx');
    if (typeof alertify !== 'undefined') alertify.success('Data exported successfully!');
  }

  trackByEmpId(index: number, item: any): any {
    return item.emp_id || index;
  }
}
