import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FPS_API, statusLabel } from '../fps.constants';

@Component({
  selector: 'app-fps-log',
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
    { value: 'pending_qc_manager', label: 'Pending QC Manager' },
    { value: 'pending_qa_approval', label: 'Pending QA Approval' },
    { value: 'pending_production', label: 'Pending Production' },
    { value: 'pending_qc_receive', label: 'Pending QC Receive' },
    { value: 'pending_results', label: 'Pending Results' },
    { value: 'pending_coa_approval', label: 'Pending C of A Approval' },
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
    const url = FPS_API + 'type=getRecordLog'
      + '&from_date=' + encodeURIComponent(this.from_date)
      + '&to_date=' + encodeURIComponent(this.to_date)
      + '&status=' + encodeURIComponent(this.statusFilter);
    this.service.get(url).subscribe((res: any) => {
      this.logs = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  view(log: any) {
    this.router.navigate(['/qc/finished-product-sampling/view', log.id]);
  }

  statusLabel = statusLabel;
}
