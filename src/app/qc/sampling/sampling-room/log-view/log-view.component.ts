import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-sampling-room-log-view',
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
    this.service.get('qc/sampling/sampling_room.php?type=getSamplingRoomLogById&id=' + id)
      .subscribe((res: any) => {
        this.sheet = res.sheet;
        this.entries = (res.entries || []).map((row: any) => ({ ...row, verifying: false }));
        this.loading = false;
      }, () => { this.loading = false; });
  }

  checkMark(val: string): string {
    return val === 'Yes' ? '✓' : '';
  }

  isRowVerified(row: any): boolean {
    return !!(row.verified_by && String(row.verified_by).trim());
  }

  getVerifiedByDisplay(row: any): string {
    if (row.verified_by_name) {
      return row.verified_by_name;
    }
    if (row.verified_by) {
      return row.verified_by;
    }
    return '';
  }

  isVerified(): boolean {
    return this.sheet && String(this.sheet.status).toLowerCase() === 'verified';
  }

  getVerifierName(): string {
    return localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
  }

  downloadPdf() {
    if (!this.sheet?.id) {
      return;
    }
    this.service.open('qc/sampling/sampling_room_pdf.php?id=' + this.sheet.id);
  }

  verifyRow(row: any) {
    if (!this.sheet || this.isRowVerified(row)) return;
    row.verifying = true;
    const payload = {
      sheet_id: this.sheet.id,
      entry_id: row.id,
      verified_by: localStorage.getItem('emp_id'),
      verified_by_name: this.getVerifierName()
    };
    this.service.postJson('qc/sampling/sampling_room.php?type=verifySamplingRoomLogEntry', JSON.stringify(payload))
      .subscribe((res: any) => {
        row.verifying = false;
        if (res && res.status === 'success') {
          row.verified_by = res.verified_by_name || this.getVerifierName();
          row.verified_by_name = row.verified_by;
          alertify.success('Row verified.');
          if (res.sheet_verified) {
            this.sheet.status = 'verified';
            this.sheet.verified_by = localStorage.getItem('emp_id');
            this.sheet.verified_by_name = res.verified_by_name || this.getVerifierName();
            alertify.success('All rows verified. Log sheet completed.');
          }
        } else {
          alertify.error((res && (res.message || res.status)) || 'Verification failed');
        }
      }, () => {
        row.verifying = false;
        alertify.error('Verification failed');
      });
  }
}
