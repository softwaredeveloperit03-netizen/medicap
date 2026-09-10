import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    this.getMyOutdoorDuty();
  }

  getMyOutdoorDuty() {
    this.loading = true;
    this.service.get('admin/housekeeping.php?type=getMyOutdoorDuty').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
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
}
