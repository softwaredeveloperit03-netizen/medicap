import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-booth-monitoring-log-view',
  templateUrl: './log-view.component.html',
  styleUrls: ['./log-view.component.css']
})
export class LogViewComponent implements OnInit {
  sheet: any = null;
  entries: any[] = [];
  loading = true;

  constructor(public service: DataAccessService, private route: ActivatedRoute) {}

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadLog(id);
    }
  }

  loadLog(id: string) {
    this.loading = true;
    this.service.get('qc/sampling/booth_monitoring.php?type=getBoothMonitoringLogById&id=' + id)
      .subscribe((res: any) => {
        this.sheet = res.sheet;
        this.entries = (res.entries || []).map((row: any) => ({ ...row, reviewing: false }));
        this.loading = false;
      }, () => { this.loading = false; });
  }

  isRowReviewed(row: any): boolean {
    return !!(row.reviewed_by && String(row.reviewed_by).trim());
  }

  getReviewedByDisplay(row: any): string {
    if (row.reviewed_by_name) {
      return row.reviewed_by_name;
    }
    if (row.reviewed_by) {
      return row.reviewed_by;
    }
    return '';
  }

  isReviewed(): boolean {
    return this.sheet && String(this.sheet.status).toLowerCase() === 'reviewed';
  }

  getReviewerName(): string {
    return localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
  }

  downloadPdf() {
    if (!this.sheet?.id) {
      return;
    }
    this.service.open('qc/sampling/booth_monitoring_pdf.php?id=' + this.sheet.id);
  }

  formatInstrumentDisplay(row: any): string {
    if (row.equipment_name && row.instrument_id) {
      return row.equipment_name + ' (' + row.instrument_id + ')';
    }
    return row.instrument_id || row.equipment_name || '';
  }

  reviewRow(row: any) {
    if (!this.sheet || this.isRowReviewed(row)) return;
    row.reviewing = true;
    const payload = {
      sheet_id: this.sheet.id,
      entry_id: row.id,
      reviewed_by: localStorage.getItem('emp_id'),
      reviewed_by_name: this.getReviewerName()
    };
    this.service.postJson('qc/sampling/booth_monitoring.php?type=verifyBoothMonitoringLogEntry', JSON.stringify(payload))
      .subscribe((res: any) => {
        row.reviewing = false;
        if (res && res.status === 'success') {
          row.reviewed_by = res.reviewed_by_name || this.getReviewerName();
          row.reviewed_by_name = row.reviewed_by;
          alertify.success('Row reviewed.');
          if (res.sheet_reviewed) {
            this.sheet.status = 'reviewed';
            this.sheet.reviewed_by = localStorage.getItem('emp_id');
            this.sheet.reviewed_by_name = res.reviewed_by_name || this.getReviewerName();
            alertify.success('All rows reviewed. Log sheet completed.');
          }
        } else {
          alertify.error((res && (res.message || res.status)) || 'Review failed');
        }
      }, () => {
        row.reviewing = false;
        alertify.error('Review failed');
      });
  }
}
