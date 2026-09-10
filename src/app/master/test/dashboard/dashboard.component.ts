import { Component, OnInit } from '@angular/core';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { STANDARD_TEST_TEMPLATES } from '../shared/standard-tests.constants';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {}

  /** Add Method moved to Master → MOA Master (moa-stp-copy). */
  showAddMethodOnTestMaster = false;
  moaTypeLabel = 'GTP Process';
  seedingStandards = false;

  pageSize = 25;
  currentPage = 1;

  ngOnInit(): void {
    this.getTests();
    this.get_rights();
  }

  tests;
  selectedResult: any = {};
  isView = false;
  loading = false;
  searchQuery = '';

  getTests() {
    this.loading = true;
    const mainLog$ = this.service.get('master/test.php?type=getTestsLog');
    const inactiveLog$ = this.service.get('master/test.php?type=getTestsByStatus&status=In-Active').pipe(
      catchError(() => of([]))
    );
    forkJoin([mainLog$, inactiveLog$]).subscribe(([mainList, inactiveList]) => {
      const main = Array.isArray(mainList) ? mainList : [];
      const inactive = Array.isArray(inactiveList) ? inactiveList : [];
      const mainIds = new Set(main.map((t: any) => t.id));
      const onlyInactive = inactive.filter((t: any) => !mainIds.has(t.id));
      this.tests = [...main, ...onlyInactive];
      this.loading = false;
      this.onFilterChange();
    }, () => { this.loading = false; });
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }

  displayEntryBy(result: any): string {
    if (!result) return 'NA';
    return result.entry_by_name || result.entry_by || 'NA';
  }

  displayApproveBy(result: any): string {
    if (!result) return '-';
    return result.approve_by_name || result.approve_by || '-';
  }

  viewTest(result: any) {
    this.selectedResult = result || {};
    this.isView = true;
  }

  get filteredMaterials(): any[] {
    if (!this.tests) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.tests;
    const query = this.searchQuery.toLowerCase().trim();
    return this.tests.filter((t: any) =>
      Object.entries(t).some(([key, value]) => {
        if (key === 'entry_date' || key === 'approve_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value as Date | undefined;
          const d = dateValue instanceof Date ? dateValue : undefined;
          return value != null && d && !isNaN(d.getTime()) && d.toISOString().slice(0, 10).includes(query);
        }
        return value != null && value.toString().toLowerCase().includes(query);
      })
    );
  }

  get totalEntries(): number {
    return this.filteredMaterials.length;
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.filteredMaterials.length / this.pageSize));
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }

  get pagedMaterials(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredMaterials.slice(start, start + this.pageSize);
  }

  get paginationStart(): number {
    if (this.totalEntries === 0) return 0;
    return (this.currentPage - 1) * this.pageSize + 1;
  }

  get paginationEnd(): number {
    return Math.min(this.currentPage * this.pageSize, this.totalEntries);
  }

  onFilterChange(): void {
    this.currentPage = 1;
  }

  onPageNoChange(value: any): void {
    const p = Number(value) || 1;
    this.currentPage = Math.min(Math.max(1, p), this.totalPages);
  }

  onPageSizeChange(value: any): void {
    this.pageSize = Number(value) || 25;
    this.currentPage = 1;
  }

  seedStandardTests(): void {
    if (this.seedingStandards) return;
    const masterUserName =
      localStorage.getItem('username') ||
      localStorage.getItem('user') ||
      localStorage.getItem('firstname') ||
      'Master User';
    this.seedingStandards = true;
    const payload = {
      masterUserName,
      tests: STANDARD_TEST_TEMPLATES,
    };
    this.service.post('master/test.php?type=seedStandardTests', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        this.seedingStandards = false;
        if (response?.status === 'success') {
          const added = response.added ?? 0;
          const skipped = response.skipped ?? 0;
          alertify.success(`Standard tests loaded (${added} added, ${skipped} already exist). Prepared by: ${masterUserName}`);
          this.getTests();
        } else {
          alertify.error(response?.message || 'Could not load standard tests.');
        }
      },
      error: () => {
        this.seedingStandards = false;
        alertify.error('Could not load standard tests. Check your connection.');
      },
    });
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + localStorage.getItem('department')).subscribe(response => {
      this.rights = response;
      this.isuser = this.rights[0].isuser;
      this.ischecker = this.rights[0].ischecker;
      this.isapprover = this.rights[0].isapprover;
      this.qms_approver = this.rights[0].qms_approver;
      this.dept_head = this.rights[0].dept_head;
      this.isauditor = this.rights[0].isauditor;
      this.plant_head = this.rights[0].plant_head;
      this.shift_allocator = this.rights[0].shift_allocator;
    });
  }

  addmethod(id: number) {
    this.router.navigate(['/qc/moa/methods/new/' + id]);
  }

  isStatusPasswordModal = false;
  statusAuthPassword = '';
  pendingStatusChange: { id: number | null; status: string | null } = { id: null, status: null };

  get pendingStatusActionLabel(): string {
    if (!this.pendingStatusChange.status) return '';
    return this.pendingStatusChange.status === 'In-Active' ? 'Inactive' : 'Active';
  }

  openStatusAuthModal(id: number, status: string) {
    this.pendingStatusChange = { id, status };
    this.statusAuthPassword = '';
    this.isStatusPasswordModal = true;
  }

  closeStatusAuthModal() {
    this.isStatusPasswordModal = false;
    this.pendingStatusChange = { id: null, status: null };
    this.statusAuthPassword = '';
  }

  submitStatusAuth(form: { valid: boolean; reset?: () => void }) {
    if (!form.valid || !this.statusAuthPassword || !this.pendingStatusChange.id || !this.pendingStatusChange.status) {
      alertify.error('Please enter password or PIN');
      return;
    }
    const id = this.pendingStatusChange.id;
    const status = this.pendingStatusChange.status;
    this.service.verifyAuthCredential(this.statusAuthPassword).subscribe({
      next: (response) => {
        if (response.status === 'success') {
          alertify.success('Password verified');
          this.closeStatusAuthModal();
          if (form.reset) form.reset();
          this.changeStatus(id, status);
        } else {
          alertify.error(response.message || 'Invalid password or PIN. Action not allowed.');
        }
      },
      error: () => {
        alertify.error('Could not verify password. Check your connection.');
      },
    });
  }

  changeStatus(id: number, status: string) {
    let url = 'master/test.php?type=changeTestStatus&id=' + id + '&status=' + encodeURIComponent(status);
    if (status === 'In-Active') {
      url += '&in_active_date=' + encodeURIComponent(new Date().toISOString());
      url += '&inactivated_by_id=' + encodeURIComponent(localStorage.getItem('emp_id') || '');
      url += '&inactivated_by_name=' + encodeURIComponent(localStorage.getItem('username') || localStorage.getItem('user') || '');
    }
    this.service.get(url).subscribe((response: any) => {
      if (response && response['status']) {
        alertify.success('Test status changed successfully');
        if (this.tests && Array.isArray(this.tests)) {
          const item = this.tests.find((t: any) => t.id == id);
          if (item) {
            item.status = status;
            if (status === 'In-Active') {
              item.in_active_date = item.in_active_date || new Date().toISOString();
              item.inactivated_by_id = item.inactivated_by_id || localStorage.getItem('emp_id') || '';
              item.inactivated_by_name = item.inactivated_by_name || localStorage.getItem('username') || localStorage.getItem('user') || '';
            }
          }
        }
      } else {
        alertify.error('Some error occurred');
      }
    });
  }
}
