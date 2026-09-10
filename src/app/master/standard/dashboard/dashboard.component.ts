import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  loading = false;
  standards: any[] = [];
  item: any[] = [];
  searchQuery = '';
  statusFilter = 'All';

  currentPage = 1;
  pageSize = 10;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getStandards();
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
        if (Array.isArray(this.rights) && this.rights.length > 0) {
          this.isuser = this.rights[0].isuser;
          this.ischecker = this.rights[0].ischecker;
          this.isapprover = this.rights[0].isapprover;
          this.qms_approver = this.rights[0].qms_approver;
          this.dept_head = this.rights[0].dept_head;
          this.isauditor = this.rights[0].isauditor;
          this.plant_head = this.rights[0].plant_head;
          this.shift_allocator = this.rights[0].shift_allocator;
        }
      });
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil((this.item?.length || 0) / this.pageSize) || 1);
  }

  get pagedItems(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return (this.item || []).slice(start, start + this.pageSize);
  }

  get lastIndexOnPage(): number {
    return Math.min(this.currentPage * this.pageSize, this.item.length || 0);
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  onPageSizeChange(value: number | string) {
    this.pageSize = typeof value === 'number' ? value : parseInt(String(value), 10) || 10;
    this.currentPage = 1;
  }

  goFirst(): void {
    this.currentPage = 1;
  }

  goPrev(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goNext(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  goLast(): void {
    this.currentPage = this.totalPages;
  }

  getStandards() {
    this.loading = true;
    const status = this.statusFilter === 'All' ? 'all' : this.statusFilter.toLowerCase();
    this.service.get('qc/standard.php?type=getStandards&status=' + status).subscribe({
      next: (response) => {
        this.standards = Array.isArray(response) ? response : [];
        this.filterItem();
        this.loading = false;
      },
      error: () => {
        this.standards = [];
        this.item = [];
        this.loading = false;
      },
    });
  }

  download() {
    this.service.open('qc/standard.php?type=downloadStandards&status=' + this.statusFilter);
  }

  filterItem() {
    const q = (this.searchQuery || '').toLowerCase().trim();
    this.item = this.standards.filter((material: any) => {
      const status = String(material.status || '').toLowerCase();
      const okStatus =
        this.statusFilter === 'All' || status === this.statusFilter.toLowerCase();
      const okSearch =
        !q ||
        String(material.standard_no || '')
          .toLowerCase()
          .includes(q) ||
        String(material.standard_name || material.material_name || '')
          .toLowerCase()
          .includes(q) ||
        String(material.material_type || '')
          .toLowerCase()
          .includes(q) ||
        String(material.standard_category || material.standard || '')
          .toLowerCase()
          .includes(q) ||
        String(material.pharmacopeia_reference || '')
          .toLowerCase()
          .includes(q) ||
        String(material.analyte_marker || '')
          .toLowerCase()
          .includes(q) ||
        String(material.entry_by || '')
          .toLowerCase()
          .includes(q) ||
        String(material.entry_date || '')
          .toLowerCase()
          .includes(q) ||
        String(material.status || '')
          .toLowerCase()
          .includes(q);
      return okStatus && okSearch;
    });
    this.currentPage = 1;
  }

  AllRecord() {
    this.searchQuery = '';
    this.statusFilter = 'All';
    this.getStandards();
  }
}
