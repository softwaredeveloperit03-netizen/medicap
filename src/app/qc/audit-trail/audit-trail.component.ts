import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

type AuditView = 'hub' | 'log' | 'login' | 'archive';

interface AuditKpis {
  events: number;
  today: number;
  users: number;
  modules: number;
  open_sessions: number;
  archived_reports: number;
}

interface AuditRow {
  event_date: string;
  module: string;
  form: string;
  from_time: string;
  to_time: string;
  activity: string;
  user_label: string;
  ip_address: string;
  duration_sec: number;
}

@Component({
  selector: 'app-qc-audit-trail',
  templateUrl: './audit-trail.component.html',
  styleUrls: ['./audit-trail.component.css'],
  providers: [DatePipe],
})
export class AuditTrailComponent implements OnInit {
  loading = false;
  activeView: AuditView = 'hub';
  fromDate = '';
  toDate = '';
  kpis: AuditKpis = {
    events: 0,
    today: 0,
    users: 0,
    modules: 0,
    open_sessions: 0,
    archived_reports: 0,
  };
  recentRows: AuditRow[] = [];
  allRows: AuditRow[] = [];

  readonly sidebarItems: { id: AuditView; label: string; icon: string }[] = [
    { id: 'hub', label: 'Control Hub', icon: 'fa-th-large' },
    { id: 'log', label: 'Audit Trail Log', icon: 'fa-clipboard-list' },
    { id: 'login', label: 'Login / Logout', icon: 'fa-sign-in-alt' },
    { id: 'archive', label: 'Report Archive', icon: 'fa-archive' },
  ];

  readonly quickActions: { id: AuditView; label: string; icon: string; tone: string }[] = [
    { id: 'log', label: 'Audit Trail Log', icon: 'fa-clipboard-list', tone: 'navy' },
    { id: 'login', label: 'Login / Logout', icon: 'fa-sign-in-alt', tone: 'green' },
    { id: 'archive', label: 'Report Archive', icon: 'fa-archive', tone: 'navy' },
  ];

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    const today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.toDate = today;
    this.fromDate = this.datePipe.transform(new Date(Date.now() - 29 * 86400000), 'yyyy-MM-dd') || today;
  }

  ngOnInit(): void {
    this.loadData();
  }

  setView(view: AuditView): void {
    this.activeView = view;
    this.loadData();
  }

  refreshKpis(): void {
    this.loadData();
  }

  loadData(): void {
    this.loading = true;
    const view = this.activeView === 'hub' ? 'hub' : this.activeView;
    this.service
      .get(
        'qc/audit_trail.php?type=getQcAuditTrailHub' +
          '&from_date=' +
          encodeURIComponent(this.fromDate) +
          '&to_date=' +
          encodeURIComponent(this.toDate) +
          '&view=' +
          encodeURIComponent(view)
      )
      .subscribe({
        next: (response: any) => {
          this.kpis = response?.kpis || this.kpis;
          this.allRows = Array.isArray(response?.rows) ? response.rows : [];
          this.recentRows = Array.isArray(response?.recent) ? response.recent : this.allRows.slice(0, 50);
          this.loading = false;
        },
        error: () => {
          this.kpis = {
            events: 0,
            today: 0,
            users: 0,
            modules: 0,
            open_sessions: 0,
            archived_reports: 0,
          };
          this.allRows = [];
          this.recentRows = [];
          this.loading = false;
        },
      });
  }

  displayRows(): AuditRow[] {
    if (this.activeView === 'hub') {
      return this.recentRows;
    }
    return this.allRows;
  }

  formatDateTime(value: string): string {
    if (!value) {
      return '';
    }
    return this.datePipe.transform(value, 'yyyy-MM-dd HH:mm:ss') || value;
  }
}
