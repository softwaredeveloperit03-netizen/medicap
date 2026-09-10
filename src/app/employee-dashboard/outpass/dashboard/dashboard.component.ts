import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-outpass-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class OutpassDashboardComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';

  constructor(private service: DataAccessService, private datepipe: DatePipe) {}

  ngOnInit() {
    this.getMyOutpass();
  }

  getMyOutpass() {
    this.service.get('security/outpass_api.php?type=getMyOutpass').subscribe((response: any) => {
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

  getStatusLabel(status: string): string {
    const map: Record<string, string> = {
      PENDING_DEPT_HEAD: 'Pending Dept Head',
      PENDING_HR_HEAD: 'Pending HR Head',
      PENDING_PLANT_HEAD: 'Pending Plant Head',
      PENDING_SECURITY_EXIT: 'Awaiting Exit',
      EXIT: 'Completed',
      REJECTED_DEPT_HEAD: 'Rejected (Dept)',
      REJECTED_HR_HEAD: 'Rejected (HR)',
      REJECTED_PLANT_HEAD: 'Rejected (Plant)'
    };
    return map[status] || status;
  }

  download() {
    this.service.open('security/outpass_api.php?type=downloadMyOutpassPdf');
  }
}
