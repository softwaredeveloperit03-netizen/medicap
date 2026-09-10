import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: {
  error: (msg: string) => void;
  success: (msg: string) => void;
  confirm: (message: string, ok: () => void) => void;
};

@Component({
  selector: 'app-qcconsu',
  templateUrl: './qcconsu.component.html',
  styleUrls: ['./qcconsu.component.css'],
})
export class QcconsuComponent implements OnInit {
  constructor(public service: DataAccessService) {}

  loading = false;
  savingGst = false;
  savingHeading = false;

  gst_list: Record<string, unknown>[] = [];
  tax_heading: { id?: number; heading?: string }[] = [];

  /** Bound model so the heading select can use [ngValue] reliably */
  gstDraft: { heading: string; gst: number | null } = { heading: '', gst: null };

  ngOnInit(): void {
    this.getGST();
    this.get_tax_heading();
  }

  validateNumberInput(event: KeyboardEvent) {
    const pattern = /[0-9.]/;
    const inputChar = String.fromCharCode(event.charCode);
    if (!pattern.test(inputChar)) {
      event.preventDefault();
    }
  }

  getGST() {
    this.loading = true;
    this.gst_list = [];
    this.service.get('common.php?type=getGST').subscribe({
      next: (response: unknown) => {
        this.gst_list = Array.isArray(response) ? (response as Record<string, unknown>[]) : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Could not load GST list');
      },
    });
  }

  get_tax_heading() {
    this.service.get('common.php?type=get_tax_heading').subscribe({
      next: (response: unknown) => {
        this.tax_heading = Array.isArray(response) ? (response as { id?: number; heading?: string }[]) : [];
      },
      error: () => alertify.error('Could not load tax headings'),
    });
  }

  /** Backend returns success / inserted / updated messages */
  private isSaveOk(status: string | undefined): boolean {
    if (!status) return false;
    const s = status.toLowerCase();
    return status === 'success' || s.indexOf('success') !== -1;
  }

  saveGst(form: NgForm) {
    if (!form.valid) {
      alertify.error('Select a tax heading and enter a valid tax %');
      return;
    }
    const v = form.value as { heading?: string; gst?: string | number };
    const heading = (v.heading || '').trim();
    const gstNum = Number(v.gst);
    if (!heading) {
      alertify.error('Tax heading is required');
      return;
    }
    if (Number.isNaN(gstNum) || gstNum < 0 || gstNum > 100) {
      alertify.error('Tax % must be between 0 and 100');
      return;
    }

    const half = Math.round((gstNum / 2) * 100) / 100;
    const other = Math.round((gstNum - half) * 100) / 100;
    const cgst = half.toFixed(2);
    const sgst = other.toFixed(2);
    const payload = {
      heading,
      gst: String(gstNum),
      gst_type: 'GST',
      cgst,
      sgst,
      igst: gstNum.toFixed(2),
      Description: `${cgst} + ${sgst}`,
    };

    this.savingGst = true;
    this.service.post('master/master.php?type=save_gst', JSON.stringify(payload)).subscribe({
      next: (response: { status?: string }) => {
        this.savingGst = false;
        if (this.isSaveOk(response['status'])) {
          form.resetForm({ heading: '', gst: null });
          this.gstDraft = { heading: '', gst: null };
          this.getGST();
          alertify.success('Tax rate saved');
        } else {
          alertify.error(response['status'] || 'Save failed');
        }
      },
      error: () => {
        this.savingGst = false;
        alertify.error('Save failed');
      },
    });
  }

  save_heading(form: NgForm) {
    if (!form.valid) {
      alertify.error('Enter a tax heading');
      return;
    }
    const temp = form.value as { heading?: string };
    this.savingHeading = true;
    this.service.post('common.php?type=save_tax_heading', JSON.stringify(temp)).subscribe({
      next: (response: { status?: string }) => {
        this.savingHeading = false;
        if (response['status'] === 'success') {
          form.resetForm();
          this.get_tax_heading();
          alertify.success('Heading saved');
        } else {
          alertify.error(response['status'] || 'Save failed');
        }
      },
      error: () => {
        this.savingHeading = false;
        alertify.error('Save failed');
      },
    });
  }

  delete_tax_heading(row: { id?: number; heading?: string }) {
    if (!row || row.id == null) {
      alertify.error('Cannot delete this heading');
      return;
    }
    const label = row.heading ? String(row.heading) : 'this heading';
    alertify.confirm('Delete tax heading "' + label + '"?', () => {
      this.service
        .post('common.php?type=delete_tax_heading', JSON.stringify({ id: row.id }))
        .subscribe({
          next: (response: { status?: string }) => {
            if (response['status'] === 'success') {
              alertify.success('Tax heading deleted');
              this.get_tax_heading();
            } else {
              alertify.error(response['status'] || 'Delete failed');
            }
          },
          error: () => alertify.error('Delete failed'),
        });
    });
  }

  formatGst(val: unknown): string {
    if (val === null || val === undefined || val === '') return '—';
    const n = Number(val);
    if (Number.isNaN(n)) return String(val) + ' %';
    return n + ' %';
  }

  isHeading = false;

  openHeadingModal() {
    this.isHeading = true;
  }

  closeHeadingModal() {
    this.isHeading = false;
  }
}
