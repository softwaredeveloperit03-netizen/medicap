import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css', '../retest-detail/retest-detail.component.css'],
})
export class CalenderComponent implements OnInit {
  results: any[] = [];
  allResults: any[] = [];
  loading = false;
  generated = false;
  materialType = '';
  material_code = '';
  material_name = '';
  grn_no = '';
  searchQuery = '';
  /** Table filter: show rows with due_days <= this (null = all). Calendar grid always uses all rows. */
  dueWithinDays: number | null = 10;

  calendarMonth = new Date();
  calendarMonthValue = '';
  selectedDay: string | null = null;
  selectedDayRows: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.setCalendarMonthFromDate(new Date());
  }

  private toLocalDateKey(value: any): string {
    if (value == null || value === '') {
      return '';
    }
    const raw = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw.slice(0, 10);
    }
    const d = new Date(value);
    if (isNaN(d.getTime())) {
      return '';
    }
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  private localDateKeyFromParts(year: number, monthIndex: number, day: number): string {
    const m = String(monthIndex + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    return `${year}-${m}-${d}`;
  }

  setCalendarMonthFromDate(date: Date) {
    this.calendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    this.calendarMonthValue = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }

  onCalendarMonthChange() {
    if (!this.calendarMonthValue) {
      return;
    }
    const parts = this.calendarMonthValue.split('-');
    const year = +parts[0];
    const month = +parts[1];
    if (!year || !month) {
      return;
    }
    this.calendarMonth = new Date(year, month - 1, 1);
    this.selectedDay = null;
    this.selectedDayRows = [];
  }

  get overdueRows(): any[] {
    return this.filteredResults.filter((r) => r.due_bucket === 'overdue');
  }

  get todayRows(): any[] {
    return this.filteredResults.filter((r) => r.due_bucket === 'today');
  }

  get calendarResults(): any[] {
    return this.allResults.filter((row) => this.rowMatchesFilters(row, false));
  }

  get filteredResults(): any[] {
    return this.allResults.filter((row) => this.rowMatchesFilters(row, true));
  }

  rowMatchesFilters(row: any, applyDueWindow = true): boolean {
    const code = (row.material_code || '').toString().toUpperCase();
    const name = (row.material_name || '').toString().toUpperCase();
    const grn = (row.grn_no || '').toString().toUpperCase();
    const query = (this.searchQuery || '').trim().toUpperCase();
    const codeOk = !this.material_code || code.includes(this.material_code.toUpperCase());
    const nameOk = !this.material_name || name.includes(this.material_name.toUpperCase());
    const grnOk = !this.grn_no || grn.includes(this.grn_no.toUpperCase());
    const typeOk = !this.materialType || row.material_type === this.materialType;
    const searchOk =
      !query ||
      code.includes(query) ||
      name.includes(query) ||
      grn.includes(query) ||
      (row.batch_no || '').toString().toUpperCase().includes(query);
    const dueOk =
      !applyDueWindow ||
      this.dueWithinDays == null ||
      row.due_days == null ||
      row.due_days <= this.dueWithinDays;
    return codeOk && nameOk && grnOk && typeOk && searchOk && dueOk;
  }

  download() {
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.open('store/raw.php?type=downloadRetestCalendar' + q);
  }

  generateCalendar() {
    if (!this.calendarMonthValue) {
      alertify.warning('Please select Calendar Month first.');
      return;
    }
    this.loading = true;
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.getJsonArray('store/raw.php?type=getRetestCalendar' + q).subscribe({
      next: (response: any[]) => {
        this.allResults = response || [];
        this.generated = true;
        this.loading = false;
        this.selectedDay = null;
        this.selectedDayRows = [];
        this.onCalendarMonthChange();
        if (this.allResults.length) {
          const sorted = [...this.allResults].sort((a, b) =>
            this.toLocalDateKey(a.retest_date).localeCompare(this.toLocalDateKey(b.retest_date))
          );
          const inMonth = this.filteredResults.filter((r) => {
            const key = this.toLocalDateKey(r.retest_date);
            return key.startsWith(this.calendarMonthValue);
          });
          if (!inMonth.length) {
            const firstDate = this.toLocalDateKey(sorted[0]?.retest_date);
            if (firstDate) {
              const d = new Date(firstDate + 'T12:00:00');
              if (!isNaN(d.getTime())) {
                this.setCalendarMonthFromDate(d);
              }
            }
          }
          const visible = this.filteredResults.length;
          if (!visible) {
            alertify.warning(
              'Found ' +
                this.allResults.length +
                ' retest entries — none match current filters. Clear Material Type / search fields or change month.'
            );
          } else {
            alertify.success('Retest calendar generated with ' + visible + ' entries.');
          }
        } else {
          alertify.warning('Calendar generated — no pending retest entries found for approved stock.');
        }
      },
      error: () => {
        this.allResults = [];
        this.generated = true;
        this.loading = false;
        alertify.error('Failed to generate retest calendar — server error. Refresh and try again.');
      },
    });
  }

  applyFilters() {
    this.selectedDay = null;
    this.selectedDayRows = [];
  }

  clearFilters() {
    this.material_code = '';
    this.material_name = '';
    this.grn_no = '';
    this.searchQuery = '';
    this.applyFilters();
  }

  monthLabel(): string {
    return this.calendarMonth.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
  }

  prevMonth() {
    this.calendarMonth = new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth() - 1, 1);
    this.calendarMonthValue = `${this.calendarMonth.getFullYear()}-${String(this.calendarMonth.getMonth() + 1).padStart(2, '0')}`;
    this.selectedDay = null;
    this.selectedDayRows = [];
  }

  nextMonth() {
    this.calendarMonth = new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth() + 1, 1);
    this.calendarMonthValue = `${this.calendarMonth.getFullYear()}-${String(this.calendarMonth.getMonth() + 1).padStart(2, '0')}`;
    this.selectedDay = null;
    this.selectedDayRows = [];
  }

  calendarCells(): { date: Date | null; key: string; items: any[] }[] {
    const y = this.calendarMonth.getFullYear();
    const m = this.calendarMonth.getMonth();
    const first = new Date(y, m, 1);
    const startPad = first.getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const cells: { date: Date | null; key: string; items: any[] }[] = [];
    for (let i = 0; i < startPad; i++) {
      cells.push({ date: null, key: 'pad-' + i, items: [] });
    }
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(y, m, d);
      const key = this.localDateKeyFromParts(y, m, d);
      const items = this.calendarResults.filter((r) => this.toLocalDateKey(r.retest_date) === key);
      cells.push({ date, key, items });
    }
    return cells;
  }

  cellClass(items: any[]): string {
    if (!items?.length) return '';
    if (items.some((i) => i.due_bucket === 'overdue')) return 'cal-overdue';
    if (items.some((i) => i.due_bucket === 'today')) return 'cal-today';
    return 'cal-future';
  }

  selectDay(cell: { date: Date | null; items: any[]; key: string }) {
    if (!cell.date) {
      return;
    }
    this.selectedDay = cell.key;
    this.selectedDayRows = cell.items;
  }

  dueClass(dueDays: number): string {
    if (dueDays == null) return '';
    if (dueDays < 0) return 'retest-overdue';
    if (dueDays === 0) return 'retest-today';
    if (dueDays <= 10) return 'retest-soon';
    return 'retest-future';
  }
}
