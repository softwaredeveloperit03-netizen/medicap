import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { PLS_API } from '../pls.constants';

@Component({
  selector: 'app-pls-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  from_date = '';
  to_date = '';
  statusFilter = 'all';
  logs: any[] = [];
  loading = false;

  statusOptions = [
    { value: 'all', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending_section_a', label: 'Pending Section A' },
    { value: 'pending_section_b', label: 'Pending Section B' },
    { value: 'pending_lab_receive', label: 'Pending Lab Receive' },
    { value: 'pending_section_c', label: 'Pending Section C' },
    { value: 'closed', label: 'Closed' }
  ];

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    const today = new Date();
    const past = new Date();
    past.setMonth(past.getMonth() - 3);
    this.to_date = today.toISOString().substring(0, 10);
    this.from_date = past.toISOString().substring(0, 10);
    this.loadLogs();
  }

  loadLogs() {
    this.loading = true;
    const url = PLS_API + 'type=getSampleLog'
      + '&from_date=' + encodeURIComponent(this.from_date)
      + '&to_date=' + encodeURIComponent(this.to_date)
      + '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  view(log: any) {
    this.router.navigate(['/qc/processing-laboratory-samples/view', log.id]);
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
