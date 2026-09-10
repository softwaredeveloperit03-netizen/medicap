import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');
  }
  ngOnInit() {
    this.get_rights();
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
  loggedInDept;
  auditLoaded = false;
  auditLoading = false;
  from_date = '';
  to_date = '';
  searchQuery = '';
  auditResults: any[] = [];
  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 20;
  currentPage = 1;

  initAuditDates(): void {
    if (this.from_date && this.to_date) {
      return;
    }
    const now = new Date();
    const to = now.toISOString().slice(0, 10);
    const fromObj = new Date();
    fromObj.setDate(fromObj.getDate() - 30);
    const from = fromObj.toISOString().slice(0, 10);
    this.from_date = from;
    this.to_date = to;
  }

  openAuditTab(): void {
    this.initAuditDates();
    if (!this.auditLoaded) {
      this.getAuditTrail();
      this.auditLoaded = true;
    }
  }

  getAuditTrail(): void {
    this.auditLoading = true;
    this.service
      .get(
        'store/raw.php?type=getWeighingAuditTrail&from_date=' +
          encodeURIComponent(this.from_date) +
          '&to_date=' +
          encodeURIComponent(this.to_date)
      )
      .subscribe(
        (response: any) => {
          this.auditResults = Array.isArray(response) ? response : [];
          this.auditLoading = false;
          this.currentPage = 1;
        },
        () => {
          this.auditResults = [];
          this.auditLoading = false;
        }
      );
  }

  onPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 20;
    this.currentPage = 1;
  }

  getSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  get filteredAuditRows(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.auditResults || [];
    }
    const q = this.searchQuery.toLowerCase().trim();
    return (this.auditResults || []).filter((row: any) =>
      Object.values(row || {}).some((v) => v != null && String(v).toLowerCase().includes(q))
    );
  }

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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



}
