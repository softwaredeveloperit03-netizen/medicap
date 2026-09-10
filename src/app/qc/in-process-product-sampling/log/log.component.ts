import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { IPPS_API } from '../ipps.constants';

@Component({
  selector: 'app-ipps-log',
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
    { value: 'all', label: 'All' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending_qc_receive', label: 'Pending QC Receive' },
    { value: 'pending_testing', label: 'Pending Testing' },
    { value: 'pending_qc_review', label: 'Pending QC Review' },
    { value: 'pending_analyst_entry', label: 'Pending Analyst Entry' },
    { value: 'pending_qc_approval', label: 'Pending QC Approval' },
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
    const url = IPPS_API + 'type=getRecordLog'
      + '&from_date=' + encodeURIComponent(this.from_date)
      + '&to_date=' + encodeURIComponent(this.to_date)
      + '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  view(log: any) {
    this.router.navigate(['/qc/in-process-product-sampling/view', log.id]);
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
