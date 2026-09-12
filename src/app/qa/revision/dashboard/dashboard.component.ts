import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  result: any[] = [];
  logResults: any[] = [];
  pendingSearch = '';
  logSearch = '';
  logStatus = 'All';
  logReqFor = 'All';
  activeTab: 'pending' | 'log' = 'pending';
  isView = false;
  selectedResult: any = null;
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingRevisionRequest();
  }

  getPendingRevisionRequest(): void {
    this.loading = true;
    this.service.get('revision.php?type=getPendingRevisionRequest').subscribe(
      (response: any) => {
        this.result = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.result = [];
        this.loading = false;
      }
    );
  }

  openPending(): void {
    this.activeTab = 'pending';
    this.isView = false;
    this.getPendingRevisionRequest();
  }

  openLog(): void {
    this.activeTab = 'log';
    this.isView = false;
    this.getRevisionRequestLog();
  }

  getRevisionRequestLog(): void {
    this.loading = true;
    this.service
      .get(
        'revision.php?type=getRevisionRequestLog&status=' +
          encodeURIComponent(this.logStatus) +
          '&req_for=' +
          encodeURIComponent(this.logReqFor)
      )
      .subscribe(
        (response: any) => {
          this.logResults = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => {
          this.logResults = [];
          this.loading = false;
        }
      );
  }

  get filteredPending(): any[] {
    const q = (this.pendingSearch || '').trim().toLowerCase();
    if (!q) {
      return this.result;
    }
    return this.result.filter((row: any) =>
      Object.keys(row || {}).some((k) => row[k] != null && String(row[k]).toLowerCase().includes(q))
    );
  }

  get filteredLogResults(): any[] {
    const q = (this.logSearch || '').trim().toLowerCase();
    if (!q) {
      return this.logResults;
    }
    return this.logResults.filter((row: any) =>
      Object.keys(row || {}).some((k) => row[k] != null && String(row[k]).toLowerCase().includes(q))
    );
  }

  view(item: any): void {
    this.selectedResult = item;
    this.isView = true;
  }

  closeView(): void {
    this.isView = false;
    this.selectedResult = null;
  }

  approveReq(): void {
    if (!this.selectedResult || !this.selectedResult.id) {
      return;
    }
    this.service
      .post(
        'revision.php?type=approveRevisionRequest&id=' + this.selectedResult.id,
        JSON.stringify(this.selectedResult)
      )
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Reviewed successfully');
          this.closeView();
          this.getPendingRevisionRequest();
          if (this.activeTab === 'log') {
            this.getRevisionRequestLog();
          }
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      });
  }

  statusClass(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'pending') {
      return 'status-pill status-pill--pending';
    }
    if (s === 'accepted') {
      return 'status-pill status-pill--accepted';
    }
    if (s === 'rejected') {
      return 'status-pill status-pill--rejected';
    }
    return 'status-pill';
  }
}
