import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { SO_API, SO_STATUS_LABELS } from '../so.constants';

@Component({
  selector: 'app-so-log',
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
    { value: 'pending_check', label: 'Pending Check' },
    { value: 'checked', label: 'Checked' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'pending_results_review', label: 'Pending QC Results Review' },
    { value: 'closed', label: 'Closed' }
  ];

  constructor(
    public service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit() {
    const today = new Date();
    const past = new Date();
    past.setMonth(past.getMonth() - 3);
    this.to_date = today.toISOString().substring(0, 10);
    this.from_date = past.toISOString().substring(0, 10);
    this.route.queryParams.subscribe(q => {
      if (q['status']) {
        this.statusFilter = q['status'];
      }
      this.loadLogs();
    });
  }

  loadLogs() {
    this.loading = true;
    const url = SO_API + 'type=getOrders'
      + '&from_date=' + encodeURIComponent(this.from_date)
      + '&to_date=' + encodeURIComponent(this.to_date)
      + '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  view(log: any) {
    this.router.navigate(['/qc/shipping-order/view', log.id]);
  }

  statusLabel(s: string): string {
    return SO_STATUS_LABELS[s] || (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
