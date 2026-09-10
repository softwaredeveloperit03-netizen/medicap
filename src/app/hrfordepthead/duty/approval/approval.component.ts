import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-duty-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe]
})
export class DutyApprovalComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  loading = false;
  isHrHead = false;
  pageTitle = 'Outdoor Duty Approval (Department Head)';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    const dept = (localStorage.getItem('department') || '').trim();
    this.isHrHead = dept === 'Human Resource';
    this.pageTitle = this.isHrHead
      ? 'Outdoor Duty Approval (HR Department Head)'
      : 'Outdoor Duty Approval (Department Head)';
    this.loadPending();
  }

  loadPending() {
    this.loading = true;
    if (this.isHrHead) {
      this.loadHrQueue();
    } else {
      this.loadDeptQueue();
    }
  }

  loadDeptQueue() {
    const dept = encodeURIComponent(localStorage.getItem('department') || '');
    this.service.get('admin/housekeeping.php?type=getOutdoorDutyForDeptHead&deptName=' + dept).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  loadHrQueue() {
    const dept = encodeURIComponent('Human Resource');
    this.service.get('admin/housekeeping.php?type=getOutdoorDutyForDeptHead&deptName=' + dept).subscribe((deptRows: any) => {
      const firstLevel = Array.isArray(deptRows) ? deptRows : [];
      this.service.get('admin/housekeeping.php?type=getOutdoorDutyForHrHead').subscribe((hrRows: any) => {
        const secondLevel = Array.isArray(hrRows) ? hrRows : [];
        this.results = [...firstLevel, ...secondLevel];
        this.loading = false;
      }, () => {
        this.results = firstLevel;
        this.loading = false;
      });
    }, () => {
      this.service.get('admin/housekeeping.php?type=getOutdoorDutyForHrHead').subscribe((hrRows: any) => {
        this.results = Array.isArray(hrRows) ? hrRows : [];
        this.loading = false;
      }, () => {
        this.results = [];
        this.loading = false;
      });
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

  getStageLabel(row: any): string {
    if (!this.isHrHead) {
      return 'Dept Head';
    }
    return this.isHrSecondLevel(row) ? 'HR Head' : 'Dept Head (HR)';
  }

  getDateLabel(row: any): string {
    if (!this.isHrHead) {
      return row?.createdOn || '';
    }
    return this.isHrSecondLevel(row) ? (row?.deptHeadApprovalOn || '') : (row?.createdOn || '');
  }

  getApproveType(row: any): string {
    return this.isHrHead && this.isHrSecondLevel(row)
      ? 'hrHeadApproveOutdoorDuty'
      : 'deptHeadApproveOutdoorDuty';
  }

  approve(row: any) {
    if (!row || row.id == null) return;
    const type = this.getApproveType(row);
    this.service.get('admin/housekeeping.php?type=' + type + '&id=' + encodeURIComponent(String(row.id)) + '&action=approve').subscribe((res: any) => {
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
    const type = this.getApproveType(row);
    this.service.get('admin/housekeeping.php?type=' + type + '&id=' + encodeURIComponent(String(row.id)) + '&action=reject').subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success('Rejected');
        this.loadPending();
      } else {
        alertify.error(res?.message || 'Failed');
      }
    });
  }
}
