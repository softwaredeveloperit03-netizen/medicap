import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-appointment-calendar',
  templateUrl: './appointment-calendar.component.html',
  styleUrls: ['./appointment-calendar.component.css'],
  providers: [DatePipe],
})
export class AppointmentCalendarComponent implements OnInit {
  loading = false;
  results: any[] = [];
  selectedDate = '';
  viewYear: number;
  viewMonth: number; // 0-11
  calendarDays: { date: string; day: number; inMonth: boolean; count: number }[] = [];
  weekLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    const now = new Date();
    this.viewYear = now.getFullYear();
    this.viewMonth = now.getMonth();
    this.selectedDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.loadMonth();
  }

  get monthLabel(): string {
    const d = new Date(this.viewYear, this.viewMonth, 1);
    return this.datePipe.transform(d, 'MMMM yyyy') || '';
  }

  get dayAppointments(): any[] {
    if (!this.selectedDate) {
      return this.results;
    }
    return this.results.filter((r) => this.normalizeDate(r.visitDate) === this.selectedDate);
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

  loadMonth(): void {
    const from = this.datePipe.transform(new Date(this.viewYear, this.viewMonth, 1), 'yyyy-MM-dd') || '';
    const to = this.datePipe.transform(new Date(this.viewYear, this.viewMonth + 1, 0), 'yyyy-MM-dd') || '';
    this.loading = true;
    this.service
      .get(
        'security/gatepass.php?type=getGatepassDetails&from_date=' +
          encodeURIComponent(from) +
          '&to_date=' +
          encodeURIComponent(to)
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
          if (typeof alertify !== 'undefined') {
            alertify.error('Unable to load appointments');
          }
        },
      });
  }

  private buildCalendar(): void {
    const countByDate: Record<string, number> = {};
    for (const row of this.results) {
      const key = this.normalizeDate(row.visitDate);
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

  goBack(): void {
    this.router.navigate(['/reception']);
  }
}
