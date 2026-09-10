import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  designations: any[] = [];
  filteredDesignations: any[] = [];
  isView = false;
  selectedResult: any;
  searchTerm = '';
  pageSize = 10;
  currentPage = 1;

  constructor(
    private service: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) {
    this.loggedInDept = localStorage.getItem('department');
    this.loggedInDept = localStorage.getItem('department');
  }
  ngOnInit() {
    this.getDesignations();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
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
  //---------------------------------------------------------------------------------//
  getDesignations() {
    this.service
      .get('hr/designation.php?type=getDesignations')
      .subscribe((response) => {
        this.designations = this.dedupePositions(Array.isArray(response) ? response : []);
        this.applyFilter();
      });
  }

  private dedupePositions(rows: any[]): any[] {
    const map = new Map<string, any>();
    (rows || []).forEach((row) => {
      const key =
        String(row?.dept_id ?? '') +
        '|' +
        String(row?.designation || '').trim().toLowerCase();
      const prev = map.get(key);
      if (!prev) {
        map.set(key, row);
        return;
      }
      const prevActive = String(prev.status || '').toLowerCase() === 'active';
      const rowActive = String(row.status || '').toLowerCase() === 'active';
      if (rowActive && !prevActive) {
        map.set(key, row);
      } else if (rowActive === prevActive && Number(row.id) > Number(prev.id)) {
        map.set(key, row);
      }
    });
    return Array.from(map.values());
  }

  applyFilter() {
    const q = String(this.searchTerm || '').trim().toLowerCase();
    if (!q) {
      this.filteredDesignations = [...this.designations];
    } else {
      this.filteredDesignations = this.designations.filter((item: any) =>
        String(item?.designation_heading || '').toLowerCase().includes(q) ||
        String(item?.designation || '').toLowerCase().includes(q) ||
        String(item?.department_name || '').toLowerCase().includes(q) ||
        String(item?.status || '').toLowerCase().includes(q)
      );
    }
    this.currentPage = 1;
  }

  get totalPages(): number {
    const count = this.filteredDesignations.length;
    return count === 0 ? 1 : Math.ceil(count / this.pageSize);
  }

  get pagedDesignations(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredDesignations.slice(start, start + this.pageSize);
  }

  get startIndex(): number {
    if (this.filteredDesignations.length === 0) {
      return 0;
    }
    return (this.currentPage - 1) * this.pageSize + 1;
  }

  get endIndex(): number {
    return Math.min(this.currentPage * this.pageSize, this.filteredDesignations.length);
  }

  goPrev() {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goNext() {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  viewForm(item: any) {
    this.isView = true;
    this.selectedResult = item;
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/hr');
  }
}
