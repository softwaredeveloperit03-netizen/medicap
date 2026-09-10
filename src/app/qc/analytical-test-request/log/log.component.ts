import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-atr-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  logs: any[] = [];
  from_date = '';
  to_date = '';
  statusFilter = '';
  loading = false;

  statusOptions = [
    { value: '', label: 'All' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending_qa_verify', label: 'Pending QA Verify' },
    { value: 'pending_production_verify', label: 'Pending Production Verify' },
    { value: 'pending_lab_receive', label: 'Pending Lab Receive' },
    { value: 'pending_analyst', label: 'Pending Analyst' },
    { value: 'pending_alt_method_qa', label: 'Pending Alt Method QA' },
    { value: 'pending_lab_manager', label: 'Pending Lab Manager' },
    { value: 'pending_qa_disposition', label: 'Pending QA Disposition' },
    { value: 'closed', label: 'Closed' },
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
    let url = 'qc/analytical_test_request.php?type=getRequestsByStatus';
    if (this.statusFilter) url += '&status=' + encodeURIComponent(this.statusFilter);
    if (this.from_date) url += '&from_date=' + this.from_date;
    if (this.to_date) url += '&to_date=' + this.to_date;
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  view(log: any) {
    this.router.navigate(['/qc/analytical-test-request/view', log.id]);
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
