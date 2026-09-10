import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare const alertify: any;

@Component({
  selector: 'app-fprod-ebmr-audit-trail',
  templateUrl: './audit-trail.component.html',
  styleUrls: ['../../../master/ebmr-bpr/ebmr-bpr.theme.css', './audit-trail.component.css'],
})
export class FprodEbmrAuditTrailComponent implements OnInit {
  loading = false;
  rows: any[] = [];
  actionList: string[] = [];
  generatedAt = '';
  selected: any = null;

  filters = {
    batch_id: '',
    batch_no: '',
    product: '',
    action: '',
    emp: '',
    ip: '',
    from_date: '',
    to_date: '',
    q: '',
    limit: 500,
  };

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    const today = new Date();
    const from = new Date(today);
    from.setDate(from.getDate() - 30);
    this.filters.to_date = today.toISOString().slice(0, 10);
    this.filters.from_date = from.toISOString().slice(0, 10);
    this.load();
  }

  load(): void {
    this.loading = true;
    const p = new URLSearchParams();
    Object.keys(this.filters).forEach((k) => {
      const v = (this.filters as any)[k];
      if (v !== '' && v != null) p.set(k, String(v));
    });
    this.service.get('master/ebmr_bpr.php?type=getBmrAuditTrail&' + p.toString()).subscribe({
      next: (r: any) => {
        this.loading = false;
        if (r?.status === 'success') {
          this.rows = Array.isArray(r.rows) ? r.rows : [];
          this.actionList = Array.isArray(r.actions) ? r.actions : [];
          this.generatedAt = r.generated_at || '';
        } else {
          this.rows = [];
          alertify.error(r?.message || 'Failed to load audit trail');
        }
      },
      error: () => {
        this.loading = false;
        alertify.error('Network error loading audit trail');
      },
    });
  }

  resetFilters(): void {
    const today = new Date();
    const from = new Date(today);
    from.setDate(from.getDate() - 30);
    this.filters = {
      batch_id: '',
      batch_no: '',
      product: '',
      action: '',
      emp: '',
      ip: '',
      from_date: from.toISOString().slice(0, 10),
      to_date: today.toISOString().slice(0, 10),
      q: '',
      limit: 500,
    };
    this.load();
  }

  openDetail(row: any): void {
    this.selected = row;
  }

  closeDetail(): void {
    this.selected = null;
  }

  trackByRow(_i: number, r: any): any {
    return r?.id ?? _i;
  }

  shortToken(t: string): string {
    if (!t) return '—';
    return t.length > 12 ? t.slice(0, 8) + '…' + t.slice(-4) : t;
  }

  exportCsv(): void {
    if (!this.rows.length) {
      alertify.warning('No rows to export');
      return;
    }
    const cols = [
      'at_time',
      'batch_no',
      'product_code',
      'product_name',
      'action',
      'detail',
      'by_emp',
      'emp_name',
      'department',
      'designation',
      'ip_address',
      'host_name',
      'module',
      'meaning',
      'auth_method',
      'auth_type',
      'signature_token',
      'esign_id',
      'stage_name',
      'step_name',
      'reason',
      'record_hash',
      'event_source',
    ];
    const esc = (v: any) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
    const lines = [cols.join(',')];
    this.rows.forEach((r) => lines.push(cols.map((c) => esc(r[c])).join(',')));
    const blob = new Blob([lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'BMR_Audit_Trail_' + (this.filters.from_date || 'export') + '.csv';
    a.click();
    URL.revokeObjectURL(url);
  }

  printTrail(): void {
    window.print();
  }

  close(): void {
    this.router.navigate(['/fproduction/ebmr']);
  }
}
