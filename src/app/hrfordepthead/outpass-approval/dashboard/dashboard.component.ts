import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-outpass-approval-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class OutpassApprovalDashboardComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  dept_head = 'No';
  isHrHead = false;
  pageTitle = 'Outpass Approval (Department Head)';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    const dept = (localStorage.getItem('department') || '').trim();
    this.isHrHead = dept === 'Human Resource';
    this.pageTitle = this.isHrHead
      ? 'Outpass Approval (HR Department Head)'
      : 'Outpass Approval (Department Head)';
    this.get_rights();
    this.loadPending();
  }

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + localStorage.getItem('department')).subscribe((res: any) => {
      this.dept_head = res?.[0]?.dept_head || 'No';
    });
  }

  loadPending() {
    if (this.isHrHead) {
      this.loadHrHeadQueue();
    } else {
      this.getOutpassForDeptHead();
    }
  }

  loadHrHeadQueue() {
    const dept = encodeURIComponent('Human Resource');
    this.service.get('security/outpass_api.php?type=getOutpassForDeptHead&deptName=' + dept).subscribe((deptRows: any) => {
      const firstLevel = Array.isArray(deptRows) ? deptRows : [];
      this.service.get('security/outpass_api.php?type=getOutpassForHrHead').subscribe((hrRows: any) => {
        const secondLevel = Array.isArray(hrRows) ? hrRows : [];
        this.results = [...firstLevel, ...secondLevel];
      });
    });
  }

  getOutpassForDeptHead() {
    const dept = encodeURIComponent(localStorage.getItem('department') || '');
    this.service.get('security/outpass_api.php?type=getOutpassForDeptHead&deptName=' + dept).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  getOutpassForHrHead() {
    this.service.get('security/outpass_api.php?type=getOutpassForHrHead').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  get filteredResults(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter(r =>
      Object.values(r).some(v => v && String(v).toLowerCase().includes(q))
    );
  }

  isHrSecondLevel(row: any): boolean {
    return row?.status === 'PENDING_HR_HEAD';
  }

  getDateLabel(row: any): string {
    if (!this.isHrHead) {
      return row?.createdOn || '';
    }
    return this.isHrSecondLevel(row) ? (row?.deptHeadApprovalOn || '') : (row?.createdOn || '');
  }

  getStageLabel(row: any): string {
    if (!this.isHrHead) {
      return 'Dept Head';
    }
    return this.isHrSecondLevel(row) ? 'HR Head' : 'Dept Head (HR)';
  }

  approve(row: any) {
    if (!row || row.id == null) return;
    const type = this.isHrHead && this.isHrSecondLevel(row) ? 'hrHeadApproveOutpass' : 'deptHeadApproveOutpass';
    this.service.get('security/outpass_api.php?type=' + type + '&id=' + encodeURIComponent(String(row.id)) + '&action=approve').subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Approved');
        this.loadPending();
      } else {
        alertify.error(res?.message || 'Failed');
      }
    });
  }

  reject(row: any) {
    if (!row || row.id == null) return;
    const type = this.isHrHead && this.isHrSecondLevel(row) ? 'hrHeadApproveOutpass' : 'deptHeadApproveOutpass';
    this.service.get('security/outpass_api.php?type=' + type + '&id=' + encodeURIComponent(String(row.id)) + '&action=reject').subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Rejected');
        this.loadPending();
      } else {
        alertify.error(res?.message || 'Failed');
      }
    });
  }
}
