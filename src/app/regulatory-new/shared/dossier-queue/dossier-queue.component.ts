import { Component, Input, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

export type DossierQueue =
  | 'compilation'
  | 'review'
  | 'approval'
  | 'log'
  | 'calendar'
  | 'reminders';

@Component({
  selector: 'app-dossier-queue',
  templateUrl: './dossier-queue.component.html',
  styleUrls: ['./dossier-queue.component.css'],
  providers: [DatePipe],
})
export class DossierQueueComponent implements OnInit {
  @Input() title = 'Dossier';
  @Input() queue: DossierQueue = 'log';
  @Input() closeRoute = '/regulatory';

  loading = false;
  results: any[] = [];
  selected: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  searchQuery = '';

  /** Calendar mode */
  viewYear: number;
  viewMonth: number;
  calendarDays: { date: string; day: number; inMonth: boolean; count: number }[] = [];
  selectedDate = '';
  weekLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    const now = new Date();
    this.viewYear = now.getFullYear();
    this.viewMonth = now.getMonth();
    this.selectedDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
    this.fromDate = this.datePipe.transform(new Date(now.getFullYear(), now.getMonth(), 1), 'yyyy-MM-dd') || '';
    this.toDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    if (this.queue === 'calendar') {
      this.loadMonth();
    } else {
      this.fetchList();
    }
  }

  get isCalendar(): boolean {
    return this.queue === 'calendar';
  }

  get monthLabel(): string {
    return this.datePipe.transform(new Date(this.viewYear, this.viewMonth, 1), 'MMMM yyyy') || '';
  }

  get filteredResults(): any[] {
    let rows = this.results;
    if (this.isCalendar && this.selectedDate) {
      rows = rows.filter((r) => this.normalizeDate(r.expected_date || r.entry_date) === this.selectedDate);
    }
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return rows;
    }
    return rows.filter((r) =>
      [r.dossier_req_id, r.brand_name, r.generic_name, r.client_name, r.country, r.wf_status, r.status]
        .some((v) => v != null && String(v).toLowerCase().includes(q))
    );
  }

  fetchList(): void {
    this.loading = true;
    let url =
      'regulatory/dossierWorkflow.php?type=getDossierQueue&queue=' + encodeURIComponent(this.queue);
    if (this.queue !== 'reminders') {
      url +=
        '&from_date=' +
        encodeURIComponent(this.fromDate) +
        '&to_date=' +
        encodeURIComponent(this.toDate);
    }
    this.service.get(url).subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        // Fallback to marketing log so page is never empty shell
        this.service.get('marketing/dossier.php?type=getDossiersLog').subscribe({
          next: (fallback: any) => {
            this.results = Array.isArray(fallback) ? fallback : [];
          },
        });
        if (typeof alertify !== 'undefined') {
          alertify.error('Unable to load dossier queue (using fallback if available)');
        }
      },
    });
  }

  setCurrentMonth(): void {
    const now = new Date();
    this.fromDate = this.datePipe.transform(new Date(now.getFullYear(), now.getMonth(), 1), 'yyyy-MM-dd') || '';
    this.toDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
    this.fetchList();
  }

  prevMonth(): void {
    if (this.viewMonth === 0) {
      this.viewMonth = 11;
      this.viewYear -= 1;
    } else {
      this.viewMonth -= 1;
    }
    this.loadMonth();
  }

  nextMonth(): void {
    if (this.viewMonth === 11) {
      this.viewMonth = 0;
      this.viewYear += 1;
    } else {
      this.viewMonth += 1;
    }
    this.loadMonth();
  }

  loadMonth(): void {
    this.fromDate =
      this.datePipe.transform(new Date(this.viewYear, this.viewMonth, 1), 'yyyy-MM-dd') || '';
    this.toDate =
      this.datePipe.transform(new Date(this.viewYear, this.viewMonth + 1, 0), 'yyyy-MM-dd') || '';
    this.loading = true;
    this.service
      .get(
        'regulatory/dossierWorkflow.php?type=getDossierQueue&queue=calendar&from_date=' +
          encodeURIComponent(this.fromDate) +
          '&to_date=' +
          encodeURIComponent(this.toDate)
      )
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.buildCalendar();
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.buildCalendar();
          this.loading = false;
        },
      });
  }

  selectDay(day: { date: string; inMonth: boolean }): void {
    if (!day?.date) {
      return;
    }
    this.selectedDate = day.date;
    if (!day.inMonth) {
      const d = new Date(day.date + 'T00:00:00');
      this.viewYear = d.getFullYear();
      this.viewMonth = d.getMonth();
      this.loadMonth();
    }
  }

  view(row: any): void {
    if (!row?.id) {
      this.selected = row;
      this.isView = true;
      return;
    }
    this.service.get('regulatory/dossierWorkflow.php?type=getDossierById&id=' + row.id).subscribe({
      next: (response: any) => {
        this.selected = response?.id ? response : row;
        this.isView = true;
      },
      error: () => {
        this.selected = row;
        this.isView = true;
      },
    });
  }

  closeView(): void {
    this.isView = false;
    this.selected = null;
  }

  advance(row: any): void {
    if (!row?.id) {
      return;
    }
    const next =
      this.queue === 'compilation'
        ? 'pending_review'
        : this.queue === 'review'
        ? 'pending_approval'
        : this.queue === 'approval'
        ? 'approved'
        : '';
    if (!next) {
      return;
    }
    if (!confirm('Move this dossier to next stage?')) {
      return;
    }
    this.service
      .post(
        'regulatory/dossierWorkflow.php?type=updateDossierWorkflowStatus',
        JSON.stringify({ id: row.id, wf_status: next })
      )
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            if (typeof alertify !== 'undefined') {
              alertify.success('Updated');
            }
            this.fetchList();
          } else if (typeof alertify !== 'undefined') {
            alertify.error(response?.status || 'Update failed');
          }
        },
        error: () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Update failed — deploy backend if not yet uploaded');
          }
        },
      });
  }

  reject(row: any): void {
    if (!row?.id) {
      return;
    }
    if (!confirm('Reject this dossier?')) {
      return;
    }
    this.service
      .post(
        'regulatory/dossierWorkflow.php?type=updateDossierWorkflowStatus',
        JSON.stringify({ id: row.id, wf_status: 'rejected' })
      )
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            if (typeof alertify !== 'undefined') {
              alertify.success('Rejected');
            }
            this.fetchList();
          } else if (typeof alertify !== 'undefined') {
            alertify.error(response?.status || 'Reject failed');
          }
        },
        error: () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Reject failed');
          }
        },
      });
  }

  statusLabel(status: string): string {
    const s = String(status || '').toLowerCase();
    if (s === 'pending_compilation' || s === 'draft' || s === '') {
      return 'Pending Compilation';
    }
    if (s === 'pending_review') {
      return 'Pending Review';
    }
    if (s === 'pending_approval') {
      return 'Pending Approval';
    }
    if (s === 'approved' || s === 'completed') {
      return 'Approved';
    }
    if (s === 'rejected') {
      return 'Rejected';
    }
    return status || '—';
  }

  private buildCalendar(): void {
    const countByDate: Record<string, number> = {};
    for (const row of this.results) {
      const key = this.normalizeDate(row.expected_date || row.entry_date);
      if (!key) {
        continue;
      }
      countByDate[key] = (countByDate[key] || 0) + 1;
    }
    const first = new Date(this.viewYear, this.viewMonth, 1);
    const startPad = first.getDay();
    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
    const cells: { date: string; day: number; inMonth: boolean; count: number }[] = [];
    for (let i = 0; i < startPad; i++) {
      const d = new Date(this.viewYear, this.viewMonth, 1 - (startPad - i));
      const date = this.datePipe.transform(d, 'yyyy-MM-dd') || '';
      cells.push({ date, day: d.getDate(), inMonth: false, count: countByDate[date] || 0 });
    }
    for (let day = 1; day <= daysInMonth; day++) {
      const d = new Date(this.viewYear, this.viewMonth, day);
      const date = this.datePipe.transform(d, 'yyyy-MM-dd') || '';
      cells.push({ date, day, inMonth: true, count: countByDate[date] || 0 });
    }
    while (cells.length % 7 !== 0) {
      const next = cells.length - (startPad + daysInMonth) + 1;
      const d = new Date(this.viewYear, this.viewMonth + 1, next);
      const date = this.datePipe.transform(d, 'yyyy-MM-dd') || '';
      cells.push({ date, day: d.getDate(), inMonth: false, count: countByDate[date] || 0 });
    }
    this.calendarDays = cells;
  }

  private normalizeDate(value: any): string {
    if (!value) {
      return '';
    }
    const raw = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw.substring(0, 10);
    }
    const parsed = new Date(raw);
    if (isNaN(parsed.getTime())) {
      return '';
    }
    return this.datePipe.transform(parsed, 'yyyy-MM-dd') || '';
  }
}
