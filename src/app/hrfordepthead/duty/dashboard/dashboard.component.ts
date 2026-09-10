import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  logResults: any[] = [];
  logSearchQuery = '';
  logLoading = false;
  isHrHead = false;
  pageTitle = 'Outdoor Duty Form (Department Head)';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    const dept = (localStorage.getItem('department') || '').trim();
    this.isHrHead = dept === 'Human Resource';
    this.pageTitle = this.isHrHead
      ? 'Outdoor Duty Form (HR Department Head)'
      : 'Outdoor Duty Form (Department Head)';
    this.loadLog();
  }

  loadLog() {
    this.logLoading = true;
    const url = this.isHrHead
      ? 'admin/housekeeping.php?type=getOutdoorDutyHrLog'
      : 'admin/housekeeping.php?type=getOutdoorDutyDeptLog&deptName=' + encodeURIComponent(localStorage.getItem('department') || '');

    this.service.get(url).subscribe((response: any) => {
      this.logResults = Array.isArray(response) ? response : [];
      this.logLoading = false;
    }, () => {
      this.logResults = [];
      this.logLoading = false;
    });
  }

  get filteredLog(): any[] {
    if (!this.logResults || this.logResults.length === 0) return [];
    if (!this.logSearchQuery || this.logSearchQuery.trim() === '') return this.logResults;
    const q = this.logSearchQuery.toLowerCase().trim();
    return this.logResults.filter(r =>
      Object.values(r).some(v => v && String(v).toLowerCase().includes(q))
    );
  }

  getStatusLabel(status: string): string {
    const map: Record<string, string> = {
      PENDING_DEPT_HEAD: 'Pending Dept Head',
      PENDING_HR_HEAD: 'Pending HR Head',
      APPROVED: 'Approved',
      PENDING_SECURITY_EXIT: 'Approved',
      EXIT: 'Approved',
      REJECTED_DEPT_HEAD: 'Rejected (Dept)',
      REJECTED_HR_HEAD: 'Rejected (HR)'
    };
    return map[status] || status || '-';
  }

  getStatusClass(status: string): string {
    if (!status) return 'app-status-badge';
    if (status.indexOf('REJECTED') === 0) return 'app-status-badge app-status-badge--rejected';
    if (status === 'APPROVED' || status === 'EXIT' || status === 'PENDING_SECURITY_EXIT') {
      return 'app-status-badge app-status-badge--approved';
    }
    return 'app-status-badge app-status-badge--pending';
  }
}
