import { Component, OnDestroy, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 
@Component({
  selector: 'app-forplan',
  templateUrl: './forplan.component.html',
  styleUrls: ['./forplan.component.css']
})
export class ForplanComponent implements OnInit, OnDestroy {
  
  ngOnInit() {
    this.getPendingWOs();
    this.get_Eqgetemployee_byDeptipments();
    this.initializeCalendar();
    this.loadLineMasterForDropdown();
  }

  ngOnDestroy() {
    this.stopLiveBoardPolling();
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    if (this.logSearchDebounceTimer) {
      clearTimeout(this.logSearchDebounceTimer);
    }
    if (this.updateSearchDebounceTimer) {
      clearTimeout(this.updateSearchDebounceTimer);
    }
  }

  private unwrapWoPage(response: any): { rows: any[]; total: number } {
    const rows = Array.isArray(response)
      ? response
      : (response?.work_orders || response?.data || []);
    const list = Array.isArray(rows) ? rows : [];
    const total = Array.isArray(response)
      ? list.length
      : Number(response?.total ?? list.length) || 0;
    return { rows: list, total };
  }

  setBookingLineMode(mode: 'mfg' | 'packing') {
    this.bookingLineMode = mode;
    this.lineCandidateCache = {};
    if (!this.lineMasterData?.length) {
      this.loadLineMasterForDropdown();
    }
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onSearchChange(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    this.searchDebounceTimer = setTimeout(() => {
      this.currentPage = 1;
      this.getPendingWOs();
    }, 350);
  }

  onPageChange(page: number): void {
    if (!page || page === this.currentPage) {
      return;
    }
    this.currentPage = page;
    this.getPendingWOs();
  }

  onPageSizeChange(size: number): void {
    if (!size || size === this.pageSize) {
      return;
    }
    this.pageSize = size;
    this.currentPage = 1;
    this.getPendingWOs();
  }

  trackByWo(index: number, wo: any) {
    return wo?.id || wo?.workorder_no || index;
  }

  trackByLine(index: number, line: any) {
    return line?.id || line?.line_no || index;
  }

  /** Line Master dropdown options for current MFG / Packing tab. */
  getLineMasterOptions(): any[] {
    const kind = this.bookingLineMode === 'packing' ? 'packing' : 'mfg';
    const source = Array.isArray(this.lineMasterData) ? this.lineMasterData : [];
    let filtered = source.filter((line: any) => this.lineMatchesType(line, kind));
    // If type filter yields nothing (incomplete Type master data), show all lines.
    if (!filtered.length && source.length) {
      filtered = source;
    }
    return filtered
      .map((line: any) => ({
        ...line,
        area: line.area || line.Section || '',
        line_type_category: line.line_type_category || (kind === 'packing' ? 'Packing' : 'Manufacturing')
      }))
      .sort((a: any, b: any) => String(a.line_no || '').localeCompare(String(b.line_no || ''), undefined, { numeric: true }));
  }

  getLineCandidates(wo: any): any[] {
    // Prefer Line Master list for dropdown; fall back to cached API candidates if master empty.
    const masterLines = this.getLineMasterOptions();
    if (masterLines.length) {
      return masterLines;
    }
    return this.lineCandidateCache[this.lineCandidateCacheKey(wo)] || [];
  }

  private lineCandidateCacheKey(wo: any): string {
    return [
      wo?.product_code || '',
      wo?.workorder_no || '',
      this.bookingLineMode,
      wo?.expected_production_start_date || '',
      wo?.expected_production_end_date || ''
    ].join('|');
  }

  getSelectedLineIdForMode(wo: any): string {
    const lines = this.getLinesForMode(wo);
    return lines.length ? String(lines[0].id) : '';
  }

  getSelectedLineArea(wo: any): string {
    const lines = this.getLinesForMode(wo);
    if (!lines.length) {
      return '—';
    }
    return lines[0].area || lines[0].Section || '—';
  }

  getBomBatchSize(wo: any): string {
    const kg = Number(wo?.batch_size_kg);
    if (!kg || kg <= 0) {
      return '—';
    }
    return kg.toLocaleString(undefined, { maximumFractionDigits: 3 }) + ' KGS';
  }

  getValidationLabel(wo: any): string {
    if (wo?._bookingValidation?.pending) {
      return 'Checking…';
    }
    if (!wo?._bookingValidation || wo._bookingValidation.valid === null) {
      return '—';
    }
    if (wo._bookingValidation.valid) {
      const runs = Number(wo._bookingValidation.equipment_runs || 0);
      if (runs > 1) {
        return 'OK · ' + runs + ' eq. runs';
      }
      return wo._bookingValidation.warnings?.length ? 'OK*' : 'OK';
    }
    return 'Blocked';
  }

  getValidationTitle(wo: any): string {
    const v = wo?._bookingValidation;
    if (!v) {
      return '';
    }
    const parts = [];
    const planUnit = (wo?.planUnit || wo?.order_materials_planUnit || wo?.plan_unit || '').toString().trim();
    const planQty = wo?.plan_qty ?? wo?.planQty ?? '';
    const kg = Number(wo?.batch_size_kg || v.quantities?.batch_size_kg || 0);
    parts.push('Plan ' + planQty + (planUnit ? ' ' + planUnit : ''));
    if (kg > 0) {
      parts.push('Mixer batch ' + kg + ' KGS');
    }
    if (v.equipment_summary) {
      parts.push(v.equipment_summary);
    }
    if (Array.isArray(v.errors) && v.errors.length) {
      parts.push(v.errors.join('; '));
    }
    if (Array.isArray(v.warnings) && v.warnings.length) {
      parts.push(v.warnings.join('; '));
    }
    return parts.join(' | ');
  }

  getValidationClass(wo: any): string {
    if (wo?._bookingValidation?.pending) {
      return 'lb-val-neutral';
    }
    if (!wo?._bookingValidation || wo._bookingValidation.valid === null) {
      return 'lb-val-neutral';
    }
    return wo._bookingValidation.valid ? 'lb-val-ok' : 'lb-val-error';
  }

  loadLineMasterForDropdown() {
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe({
      next: (response: any) => {
        this.lineMasterData = Array.isArray(response) ? response : [];
      },
      error: () => {
        if (!Array.isArray(this.lineMasterData)) {
          this.lineMasterData = [];
        }
      }
    });
  }

  loadLineCandidatesForWo(wo: any, done?: () => void) {
    if (!this.lineMasterData?.length) {
      this.loadLineMasterForDropdown();
    }
    // Keep availability enrichment as optional cache for validation/auto allocate.
    const key = this.lineCandidateCacheKey(wo);
    if (this.lineCandidateCache[key]?.length) {
      if (done) {
        done();
      }
      return;
    }
    const lineType = encodeURIComponent(this.getBookingLineTypeParam());
    const params = [
      `product_code=${encodeURIComponent(wo.product_code || '')}`,
      `workorder_no=${encodeURIComponent(wo.workorder_no || '')}`,
      `start_date=${encodeURIComponent(wo.expected_production_start_date || '')}`,
      `start_time=${encodeURIComponent(wo.expected_production_start_time || '')}`,
      `end_date=${encodeURIComponent(wo.expected_production_end_date || '')}`,
      `end_time=${encodeURIComponent(wo.expected_production_end_time || '')}`,
      `line_type=${lineType}`,
      `plan_qty=${encodeURIComponent(wo.plan_qty || '')}`,
      `batch_size=${encodeURIComponent(wo.batch_size || '')}`
    ].join('&');
    this.service.get(`bmr/line_booking.php?type=getAvailableLines&${params}`).subscribe({
      next: (response: any) => {
        this.lineCandidateCache[key] = Array.isArray(response) ? response : [];
        if (done) {
          done();
        }
      },
      error: () => {
        this.lineCandidateCache[key] = [];
        if (done) {
          done();
        }
      }
    });
  }

  onInlineLineSelected(wo: any, lineId: string) {
    if (!lineId) {
      const remaining = (wo.selectedLines || []).filter((line: any) =>
        !this.lineMatchesType(line, this.bookingLineMode === 'packing' ? 'packing' : 'mfg')
      );
      wo.selectedLines = remaining;
      wo._selectedLineId = '';
      wo._bookingValidation = null;
      return;
    }
    const candidates = this.getLineCandidates(wo);
    let selected = candidates.find((line: any) => String(line.id) === String(lineId));
    if (!selected) {
      selected = (this.lineMasterData || []).find((line: any) => String(line.id) === String(lineId));
    }
    if (!selected) {
      return;
    }
    const enriched = {
      ...selected,
      area: selected.area || selected.Section || '',
      Section: selected.Section || selected.area || ''
    };
    wo._selectedLineId = String(lineId);
    wo.selectedLines = this.mergeLinesForCategory(wo.selectedLines || [], [enriched], this.bookingLineMode);
    if (enriched.suggested_schedule) {
      this.applySuggestedSchedule(wo, enriched.suggested_schedule);
    }
    this.validateWoPlan(wo);
  }

  applySuggestedSchedule(wo: any, schedule: any) {
    if (!schedule) {
      return;
    }
    wo.expected_production_start_date = schedule.expected_production_start_date || wo.expected_production_start_date;
    wo.expected_production_start_time = this.normalizeTimeForPlanningInput(schedule.expected_production_start_time || wo.expected_production_start_time);
    wo.expected_production_end_date = schedule.expected_production_end_date || wo.expected_production_end_date;
    wo.expected_production_end_time = this.normalizeTimeForPlanningInput(schedule.expected_production_end_time || wo.expected_production_end_time);
    wo.no_of_hours_required = schedule.no_of_hours_required ?? wo.no_of_hours_required;
    this.calculateHours(wo);
  }

  private buildValidationPayload(wo: any) {
    const lines = this.getLinesForMode(wo).map((line: any) => {
      const master = (this.lineMasterData || []).find(
        (m: any) => String(m.id) === String(line.id),
      );
      return master ? { ...master, ...line } : line;
    });
    return {
      workorder_no: wo.workorder_no,
      product_code: wo.product_code,
      plan_qty: wo.plan_qty,
      batch_size: wo.batch_size ?? wo.bom_batch_size,
      batch_size_kg: wo.batch_size_kg,
      planUnit: wo.planUnit || wo.order_materials_planUnit || wo.plan_unit || '',
      selectedLines: lines,
      expected_production_start_date: wo.expected_production_start_date,
      expected_production_start_time: wo.expected_production_start_time,
      expected_production_end_date: wo.expected_production_end_date,
      expected_production_end_time: wo.expected_production_end_time,
    };
  }

  private isValidationUnavailable(validation: any): boolean {
    const errors = validation?.errors || [];
    return (
      errors.length === 1 &&
      String(errors[0]).toLowerCase().includes('validation service unavailable')
    );
  }

  validateWoPlan(wo: any) {
    const lines = this.getLinesForMode(wo);
    if (!lines.length) {
      wo._bookingValidation = null;
      return;
    }
    wo._bookingValidation = { pending: true, valid: null, errors: [], warnings: [] };
    this.service
      .post('bmr/line_booking.php?type=validateLineBookingPlan', this.buildValidationPayload(wo))
      .subscribe({
        next: (response: any) => {
          if (response && typeof response === 'object') {
            wo._bookingValidation = { ...response, pending: false };
            const kg = Number(response.quantities?.batch_size_kg);
            if (kg > 0) {
              wo.batch_size_kg = kg;
            }
            return;
          }
          wo._bookingValidation = {
            pending: false,
            valid: true,
            errors: [],
            warnings: ['Validation response empty — server will validate on save'],
          };
        },
        error: (err: any) => {
          const serverMsg =
            err?.error?.errors?.[0] ||
            err?.error?.message ||
            (typeof err?.error === 'string' ? err.error : '');
          wo._bookingValidation = {
            pending: false,
            valid: serverMsg ? false : null,
            errors: serverMsg
              ? [serverMsg]
              : ['Validation service unavailable — save will validate on server'],
            warnings: [],
          };
        },
      });
  }

  autoAllocateLine(wo: any) {
    const payload = {
      workorder_no: wo.workorder_no,
      product_code: wo.product_code,
      product_name: wo.product_name,
      plan_qty: wo.plan_qty,
      batch_size: wo.batch_size,
      batch_size_kg: wo.batch_size_kg,
      planUnit: wo.planUnit,
      line_type: this.getBookingLineTypeParam(),
      expected_production_start_date: wo.expected_production_start_date || '',
      expected_production_start_time: wo.expected_production_start_time || '',
      expected_production_end_date: wo.expected_production_end_date || '',
      expected_production_end_time: wo.expected_production_end_time || ''
    };
    this.service.post('bmr/line_booking.php?type=autoAllocateLineBooking', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        if (response?.status !== 'success' || !response?.allocated_line) {
          alert(response?.message || 'Auto allocation failed');
          return;
        }
        const line = response.allocated_line;
        wo._selectedLineId = String(line.id);
        wo.selectedLines = this.mergeLinesForCategory(wo.selectedLines || [], [line], this.bookingLineMode);
        if (response.suggested_schedule) {
          this.applySuggestedSchedule(wo, response.suggested_schedule);
        }
        wo._bookingValidation = {
          valid: true,
          warnings: line.capacity_validation?.warnings || [],
          errors: [],
          equipment_runs: line.capacity_validation?.equipment_runs || line.equipment_runs || 1,
          equipment_summary: line.capacity_validation?.equipment_summary || line.equipment_summary || '',
        };
        alert(`Auto allocated Line ${line.line_no} (${line.area || line.Section || 'Area NA'})`);
      },
      error: () => alert('Auto allocation failed')
    });
  }

  onScheduleFieldChanged(wo: any) {
    this.calculateHours(wo);
    if (this.getLinesForMode(wo).length) {
      this.lineCandidateCache = {};
      this.validateWoPlan(wo);
    }
  }

  loadLineOccupancyCalendar() {
    const monthStart = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth(), 1);
    const monthEnd = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth() + 1, 0);
    const startDate = this.ymd(monthStart);
    const endDate = this.ymd(monthEnd);
    const lineType = encodeURIComponent(this.calendarLineTypeFilter || 'All');
    this.service.get(`bmr/line_booking.php?type=getLineOccupancyCalendar&start_date=${startDate}&end_date=${endDate}&line_type=${lineType}`)
      .subscribe({
        next: (response: any) => {
          this.lineOccupancyCalendar = response;
        },
        error: () => {
          this.lineOccupancyCalendar = null;
        }
      });
  }

  getCalendarOccupiedLineCount(dateStr: string): number {
    if (!this.lineOccupancyCalendar?.lines) {
      return 0;
    }
    let count = 0;
    for (const line of this.lineOccupancyCalendar.lines) {
      const bookings = line.bookings || [];
      const hit = bookings.some((b: any) => {
        const start = b.booking_start_date || '';
        const end = b.booking_end_date || start;
        return start && dateStr >= start && dateStr <= end;
      });
      if (hit) {
        count++;
      }
    }
    return count;
  }

  getBookingLineTypeParam(): string {
    return this.bookingLineMode === 'packing' ? 'Packing' : 'Manufacturing';
  }

  getLinesForMode(wo: any, mode: 'mfg' | 'packing' = this.bookingLineMode): any[] {
    const lines = wo?.selectedLines || [];
    return lines.filter((line: any) => this.lineMatchesType(line, mode === 'packing' ? 'packing' : 'mfg'));
  }

  getSelectedLineCountForMode(wo: any): number {
    return this.getLinesForMode(wo).length;
  }

  getLineNumbersDisplay(wo: any, mode: 'mfg' | 'packing' | 'all' = this.bookingLineMode): string {
    const lines = mode === 'all'
      ? (wo?.selectedLines || [])
      : this.getLinesForMode(wo, mode);
    const numbers = lines.map((line: any) => line?.line_no).filter(Boolean);
    return numbers.length ? numbers.join(', ') : '—';
  }

  getAllLineNumbersDisplay(wo: any): string {
    const lines = wo?.selectedLines || [];
    const numbers = lines.map((line: any) => line?.line_no).filter(Boolean);
    return numbers.length ? numbers.join(', ') : '—';
  }

  private mergeLinesForCategory(existingLines: any[], newLines: any[], mode: 'mfg' | 'packing'): any[] {
    const kind = mode === 'packing' ? 'packing' : 'mfg';
    const keep = (existingLines || []).filter((line: any) => !this.lineMatchesType(line, kind));
    return [...keep, ...(newLines || [])];
  }

  loadLineMaster() {
    this.lineMasterLoading = true;
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe({
      next: (response: any) => {
        this.lineMasterData = Array.isArray(response) ? response : [];
        this.lineMasterLoading = false;
      },
      error: () => {
        this.lineMasterData = [];
        this.lineMasterLoading = false;
      }
    });
  }

  loadLiveBoard() {
    this.liveBoardLoading = true;
    const lineType = encodeURIComponent(this.liveBoardLineType || 'All');
    this.service.get(`bmr/line_booking.php?type=getLineBookingDashboard&line_type=${lineType}`).subscribe({
      next: (response: any) => {
        this.liveBoardLines = Array.isArray(response) ? response : [];
        this.liveBoardLoading = false;
      },
      error: () => {
        this.liveBoardLines = [];
        this.liveBoardLoading = false;
      }
    });
  }

  startLiveBoardPolling() {
    this.loadLiveBoard();
    this.stopLiveBoardPolling();
    this.liveBoardRefreshTimer = setInterval(() => this.loadLiveBoard(), 10000);
  }

  stopLiveBoardPolling() {
    if (this.liveBoardRefreshTimer) {
      clearInterval(this.liveBoardRefreshTimer);
      this.liveBoardRefreshTimer = null;
    }
  }

  getLiveBoardColor(line: any): string {
    const code = (line?.status_code || '').toLowerCase();
    const label = (line?.status_label || line?.operational_status || '').toLowerCase();
    if (code === 'breakdown' || label.includes('breakdown')) {
      return '#0d6efd';
    }
    if (code === 'maintenance' || label.includes('under maintain') || label.includes('maintenance')) {
      return '#8B4513';
    }
    if (line?.status_color) {
      return line.status_color;
    }
    return line?.occupied ? '#dc3545' : '#28a745';
  }

  getLiveBoardTextColor(line: any): string {
    const bg = this.getLiveBoardColor(line);
    return (bg === '#28a745') ? '#111' : '#fff';
  }

  getLiveBoardTooltip(line: any): string {
    const base = `${line?.line_no || ''} ${line?.line_name || ''} | ${line?.lineType || line?.line_type_category || ''} | Area: ${line?.area || line?.Section || '—'} - ${line?.status_label || ''}`;
    if (line?.status_code !== 'occupied') {
      return base;
    }
    const parts = [base];
    if (line?.active_product_name) {
      parts.push(`Product: ${line.active_product_name}`);
    }
    if (line?.active_product_code) {
      parts.push(`Code: ${line.active_product_code}`);
    }
    if (line?.active_batch_no) {
      parts.push(`Batch: ${line.active_batch_no}`);
    }
    if (line?.active_workorder_no) {
      parts.push(`WO: ${line.active_workorder_no}`);
    }
    return parts.join(' | ');
  }

  private buildUpdateBookingUrl(): string {
    let url =
      'bmr/line_booking.php?type=getUpdatableLineBookings&page=' +
      this.updateCurrentPage +
      '&limit=' +
      this.updatePageSize;
    const q = (this.updateBookingSearch || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  loadUpdatableBookings() {
    this.updateBookingLoading = true;
    this.service.get(this.buildUpdateBookingUrl()).subscribe({
      next: (response: any) => {
        const page = this.unwrapWoPage(response);
        this.updateBookingList = page.rows;
        this.updateTotalRecords = page.total;
        this.updateBookingList.forEach((wo: any) => {
          wo._selectedLineId = this.getSelectedLineIdForMode(wo);
        });
        this.updateBookingBackup = this.updateBookingList;
        if (this.updateBookingList.length === 0 && this.updateCurrentPage > 1 && this.updateTotalRecords > 0) {
          this.updateCurrentPage -= 1;
          this.loadUpdatableBookings();
          return;
        }
        this.updateBookingLoading = false;
      },
      error: () => {
        this.updateBookingList = [];
        this.updateBookingBackup = [];
        this.updateTotalRecords = 0;
        this.updateBookingLoading = false;
      }
    });
  }

  applyUpdateBookingFilter() {
    if (this.updateSearchDebounceTimer) {
      clearTimeout(this.updateSearchDebounceTimer);
    }
    this.updateSearchDebounceTimer = setTimeout(() => {
      this.updateCurrentPage = 1;
      this.loadUpdatableBookings();
    }, 350);
  }

  onUpdatePageChange(page: number): void {
    if (!page || page === this.updateCurrentPage) {
      return;
    }
    this.updateCurrentPage = page;
    this.loadUpdatableBookings();
  }

  onUpdatePageSizeChange(size: number): void {
    if (!size || size === this.updatePageSize) {
      return;
    }
    this.updatePageSize = size;
    this.updateCurrentPage = 1;
    this.loadUpdatableBookings();
  }

  updateStartSrNo(): number {
    return (this.updateCurrentPage - 1) * this.updatePageSize;
  }

  openUpdateBooking(wo: any) {
    if (!wo?.can_update) {
      alert('This line booking was already sent to production and cannot be changed.');
      return;
    }
    this.isUpdateBookingView = true;
    this.bookingLineMode = 'mfg';
    this.View(wo);
  }

  saveUpdatedLineBooking(wo: any) {
    if (!wo?.can_update) {
      alert('This line booking was already sent to production and cannot be changed.');
      return;
    }
    this.normalizeWorkOrderPlanningFields(wo);
    const validation = this.validateWorkOrderForParking(wo);
    if (!validation.valid) {
      alert(validation.message);
      return;
    }
    const payload = {
      work_orders: [{
        workorder_no: wo.workorder_no,
        line_category: this.getBookingLineTypeParam(),
        selectedLines: wo.selectedLines || [],
        expected_production_start_date: wo.expected_production_start_date || '',
        expected_production_start_time: wo.expected_production_start_time || '',
        expected_production_end_date: wo.expected_production_end_date || '',
        expected_production_end_time: wo.expected_production_end_time || '',
        responsible_person: wo.responsible_person || '',
        no_of_hours_required: wo.no_of_hours_required || '',
        plan_qty: wo.plan_qty || '',
        product_code: wo.product_code || '',
        product_name: wo.product_name || ''
      }]
    };
    this.service.post('bmr/line_booking.php?type=updateStpLineBooking', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        if (response?.status === 'success' || response?.updated_count > 0) {
          alert(response?.message || 'Line booking updated successfully');
          this.loadUpdatableBookings();
          this.getLogData();
        } else {
          alert(response?.message || 'Unable to update line booking');
        }
      },
      error: () => alert('Unable to update line booking')
    });
  }

  private tvLinkApi = 'bmr/line_booking_tv_link.php';

  loadDisplayBoardTokens() {
    this.service.get(this.tvLinkApi + '?type=getLineBookingDisplayTokens').subscribe({
      next: (response: any) => {
        this.displayBoardTokens = Array.isArray(response) ? response : [];
        if (this.displayBoardTokens.length > 0) {
          this.setDisplayBoardLink(this.displayBoardTokens[0]);
        }
      },
      error: () => {
        // Fallback to legacy endpoint in line_booking.php
        this.service.get('bmr/line_booking.php?type=getLineBookingDisplayTokens').subscribe({
          next: (response: any) => {
            this.displayBoardTokens = Array.isArray(response) ? response : [];
            if (this.displayBoardTokens.length > 0) {
              this.setDisplayBoardLink(this.displayBoardTokens[0]);
            }
          },
          error: () => {
            this.displayBoardTokens = [];
          }
        });
      }
    });
  }

  generateDisplayBoardLink() {
    const plantId = (localStorage.getItem('plant_id') || '').trim();
    if (!plantId || plantId === 'null' || plantId === 'undefined') {
      alert('Plant ID missing. Please re-login and try Generate TV Link again.');
      return;
    }
    const payload = JSON.stringify({ label: 'Line Booking TV Board' });
    const tryGenerate = (url: string, isFallback = false) => {
      this.service.post(url, payload).subscribe({
        next: (response: any) => {
          if (response?.status === 'success' && response?.display_token) {
            this.setDisplayBoardLink(response);
            this.loadDisplayBoardTokens();
            alert('TV display link generated. Open it on your monitor browser.');
            return;
          }
          if (!isFallback) {
            tryGenerate('bmr/line_booking.php?type=generateLineBookingDisplayToken', true);
            return;
          }
          alert(response?.message || 'Unable to generate TV link');
        },
        error: (err) => {
          if (!isFallback) {
            tryGenerate('bmr/line_booking.php?type=generateLineBookingDisplayToken', true);
            return;
          }
          const msg = err?.error?.message || err?.message || 'Unable to generate TV link. Please upload latest PHP files (line_booking_tv_link.php).';
          alert(msg);
        }
      });
    };
    tryGenerate(this.tvLinkApi + '?type=generateLineBookingDisplayToken');
  }

  setDisplayBoardLink(tokenRow: any) {
    const token = tokenRow?.display_token || '';
    const plantId = tokenRow?.plant_id || localStorage.getItem('plant_id') || '';
    if (!token) {
      this.displayBoardLink = '';
      return;
    }
    const origin = window.location.origin + window.location.pathname;
    this.displayBoardLink = `${origin}#/line-booking-board/${encodeURIComponent(token)}?plant_id=${encodeURIComponent(plantId)}`;
  }

  copyDisplayBoardLink() {
    if (!this.displayBoardLink) {
      alert('Generate a display link first');
      return;
    }
    navigator.clipboard?.writeText(this.displayBoardLink).then(() => {
      alert('TV display link copied to clipboard');
    }).catch(() => {
      prompt('Copy this TV display link:', this.displayBoardLink);
    });
  }

  openDisplayBoardInNewTab() {
    if (!this.displayBoardLink) {
      alert('Generate a display link first');
      return;
    }
    window.open(this.displayBoardLink, '_blank');
  }
    constructor(private service: DataAccessService) { }
  loading: boolean = false;
  isMaterials: boolean = false;
finalDeductions: any[] = []; // This will store the final output

pendingpo: any[] = [];
searchText: string = '';
pendingpoBackup: any[] = [];
currentPage = 1;
pageSize = 25;
totalRecords = 0;
private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
private logSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
private updateSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
editingField: { [key: string]: string } = {}; // Track which field is being edited for each work order
logData: any[] = []; // Store log data
logDataBackup: any[] = []; // Backup for log filtering
logSearchText: string = ''; // Search text for log tab
logCurrentPage = 1;
logPageSize = 25;
logTotalRecords = 0;
bookingLineMode: 'mfg' | 'packing' = 'mfg';
lineMasterData: any[] = [];
lineMasterLoading = false;
liveBoardLines: any[] = [];
liveBoardLoading = false;
liveBoardLineType = 'All';
liveBoardRefreshTimer: any = null;
updateBookingList: any[] = [];
updateBookingBackup: any[] = [];
updateBookingSearch = '';
updateBookingLoading = false;
updateCurrentPage = 1;
updatePageSize = 25;
updateTotalRecords = 0;
displayBoardTokens: any[] = [];
displayBoardLink = '';
isUpdateBookingView = false;
lineCandidateCache: Record<string, any[]> = {};
lineOccupancyCalendar: any = null;
calendarLineTypeFilter = 'All';
selectedPlanningWo: any = null;
showStpPlanningModal: boolean = false;
stpPlannedCalendarMode: boolean = false;
calendarLoading: boolean = false;
calendarReferenceDate: Date = new Date();
calendarMonthLabel: string = '';
calendarDays: any[] = [];
calendarPlannedWOs: any[] = [];
selectedCalendarDate: string = '';
selectedDatePlannedWOs: any[] = [];

private ymd(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

private parseYmd(value: string): Date | null {
  if (!value) return null;
  const parts = value.split('-').map(v => parseInt(v, 10));
  if (parts.length !== 3 || parts.some(v => Number.isNaN(v))) return null;
  return new Date(parts[0], parts[1] - 1, parts[2]);
}

private formatMonthLabel(date: Date): string {
  return date.toLocaleString('en-US', { month: 'long', year: 'numeric' });
}

initializeCalendar() {
  const today = new Date();
  this.calendarReferenceDate = new Date(today.getFullYear(), today.getMonth(), 1);
  this.selectedCalendarDate = this.ymd(today);
  this.buildCalendarGrid();
  this.loadCalendarPlannedWOs();
  this.loadLineOccupancyCalendar();
}

openPlanningDetails(wo: any) {
  this.selectedPlanningWo = wo;
  this.normalizeWorkOrderPlanningFields(this.selectedPlanningWo);
  this.calculateHours(this.selectedPlanningWo);
}

openStpPlanningModal(wo: any) {
  this.openPlanningDetails(wo);
  this.showStpPlanningModal = true;
}

closeStpPlanningModal() {
  this.showStpPlanningModal = false;
  this.closePlanningDetails();
}

onStpPlanningModalChange(open: boolean) {
  this.showStpPlanningModal = open;
  if (!open) {
    this.closePlanningDetails();
  }
}

/** Trim / normalize schedule fields so validation matches what the user sees in the pickers. */
private planningFieldStr(v: any): string {
  if (v == null) {
    return '';
  }
  return String(v).trim();
}

normalizeDateForPlanningInput(val: any): string {
  const s = this.planningFieldStr(val);
  if (!s) {
    return '';
  }
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    return s.slice(0, 10);
  }
  const m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
  if (m) {
    const d = m[1].padStart(2, '0');
    const mo = m[2].padStart(2, '0');
    const y = m[3];
    return `${y}-${mo}-${d}`;
  }
  return s;
}

normalizeTimeForPlanningInput(val: any): string {
  const s = this.planningFieldStr(val);
  if (!s) {
    return '';
  }
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(s) && !/[ap]m/i.test(s)) {
    return s.length === 5 ? `${s}:00` : s;
  }
  const m = s.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*([AaPp][Mm])?/);
  if (m) {
    let h = parseInt(m[1], 10);
    const min = m[2].padStart(2, '0');
    const ap = m[4] ? m[4].toUpperCase() : '';
    if (ap === 'PM' && h < 12) {
      h += 12;
    }
    if (ap === 'AM' && h === 12) {
      h = 0;
    }
    return `${String(h).padStart(2, '0')}:${min}:00`;
  }
  return s;
}

normalizeWorkOrderPlanningFields(wo: any) {
  if (!wo) {
    return;
  }
  wo.expected_production_start_date = this.normalizeDateForPlanningInput(wo.expected_production_start_date);
  wo.expected_production_end_date = this.normalizeDateForPlanningInput(wo.expected_production_end_date);
  wo.expected_production_start_time = this.normalizeTimeForPlanningInput(wo.expected_production_start_time);
  wo.expected_production_end_time = this.normalizeTimeForPlanningInput(wo.expected_production_end_time);
  if (wo.responsible_person) {
    wo.responsible_person = this.planningFieldStr(wo.responsible_person);
  }
}

onPlanningScheduleChange() {
  if (this.selectedPlanningWo) {
    this.normalizeWorkOrderPlanningFields(this.selectedPlanningWo);
    this.calculateHours(this.selectedPlanningWo);
  }
}

closePlanningDetails() {
  this.selectedPlanningWo = null;
}

selectMonth(offset: number) {
  this.calendarReferenceDate = new Date(
    this.calendarReferenceDate.getFullYear(),
    this.calendarReferenceDate.getMonth() + offset,
    1
  );
  this.buildCalendarGrid();
  this.loadCalendarPlannedWOs();
}

buildCalendarGrid() {
  const monthStart = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth(), 1);
  const monthEnd = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth() + 1, 0);
  this.calendarMonthLabel = this.formatMonthLabel(monthStart);
  this.calendarDays = [];

  const firstWeekDay = monthStart.getDay(); // 0 Sunday
  for (let i = 0; i < firstWeekDay; i++) {
    this.calendarDays.push({ inMonth: false });
  }
  for (let day = 1; day <= monthEnd.getDate(); day++) {
    const date = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth(), day);
    const dateStr = this.ymd(date);
    this.calendarDays.push({
      inMonth: true,
      day,
      date: dateStr,
      bookedCount: 0
    });
  }
}

private isDateInPlanningRange(dateStr: string, planning: any): boolean {
  const current = this.parseYmd(dateStr);
  const start = this.parseYmd(planning?.expected_production_start_date || '');
  const end = this.parseYmd(planning?.expected_production_end_date || planning?.expected_production_start_date || '');
  if (!current || !start || !end) return false;
  return current.getTime() >= start.getTime() && current.getTime() <= end.getTime();
}

applyPlannedCountOnCalendarDays() {
  this.calendarDays = this.calendarDays.map(day => {
    if (!day?.inMonth) return day;
    const count = this.calendarPlannedWOs.filter((planning: any) => this.isDateInPlanningRange(day.date, planning)).length;
    const occupiedLines = this.getCalendarOccupiedLineCount(day.date);
    return { ...day, bookedCount: count, occupiedLineCount: occupiedLines };
  });
}

loadSelectedDatePlannedWOs(dateStr: string) {
  if (!dateStr) {
    this.selectedDatePlannedWOs = [];
    return;
  }
  this.selectedCalendarDate = dateStr;
  this.selectedDatePlannedWOs = this.calendarPlannedWOs
    .filter((planning: any) => this.isDateInPlanningRange(dateStr, planning))
    .map((planning: any) => ({ ...planning, sendSelected: !!planning.sendSelected }));
}

loadCalendarPlannedWOs() {
  const monthStart = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth(), 1);
  const monthEnd = new Date(this.calendarReferenceDate.getFullYear(), this.calendarReferenceDate.getMonth() + 1, 0);
  const startDate = this.ymd(monthStart);
  const endDate = this.ymd(monthEnd);
  this.calendarLoading = true;
  this.service.get(`bmr/line_booking.php?type=getStpPlannedCalendar&start_date=${startDate}&end_date=${endDate}`)
    .subscribe((response: any) => {
      this.calendarPlannedWOs = Array.isArray(response) ? response : [];
      this.applyPlannedCountOnCalendarDays();
      this.loadSelectedDatePlannedWOs(this.selectedCalendarDate);
      this.loadLineOccupancyCalendar();
      this.calendarLoading = false;
    }, () => {
      this.calendarPlannedWOs = [];
      this.selectedDatePlannedWOs = [];
      this.calendarLoading = false;
    });
}

getSelectedCalendarSendCount(): number {
  return this.selectedDatePlannedWOs.filter((x: any) => x.sendSelected).length;
}

sendSelectedCalendarForLineApproval() {
  const selected = this.selectedDatePlannedWOs.filter((x: any) => x.sendSelected);
  if (selected.length === 0) {
    alert('Please select at least one planned work order from calendar.');
    return;
  }
  if (!confirm(`Send ${selected.length} work order(s) to Line Approval? After approval they will appear in production (MFG Lines).`)) {
    return;
  }
  const payload = {
    work_orders: selected.map((wo: any) => ({
      workorder_no: wo.workorder_no,
      workorder_id: wo.id,
      selectedLines: wo.selectedLines || [],
      expected_production_start_date: wo.expected_production_start_date || '',
      expected_production_start_time: wo.expected_production_start_time || '',
      expected_production_end_date: wo.expected_production_end_date || '',
      expected_production_end_time: wo.expected_production_end_time || '',
      responsible_person: wo.responsible_person || '',
      no_of_hours_required: wo.no_of_hours_required || '',
      plan_qty: wo.plan_qty || '',
      planUnit: wo.planUnit || '',
      product_code: wo.product_code || '',
      product_name: wo.product_name || '',
      mainGroupName: wo.mainGroupName || '',
    })),
  };
  this.service.post(
    `bmr/line_booking.php?type=sendForLineApproval`,
    JSON.stringify(payload)
  ).subscribe((response: any) => {
    if (response?.status === 'success') {
      alert(response?.message || `Sent ${response?.sent_count || selected.length} work order(s) to Line Approval.`);
      this.loadCalendarPlannedWOs();
      this.getLogData();
      this.getPendingWOs();
    } else {
      const failed = response?.failed?.length
        ? '\n' + response.failed.map((f: any) => `${f.workorder_no}: ${f.message}`).join('\n')
        : '';
      alert('Failed to send for line approval: ' + (response?.message || 'Please try again') + failed);
    }
  }, () => {
    alert('Failed to send for line approval. Please try again.');
  });
}

onCalendarDayClick(day: any) {
  if (!day?.inMonth) return;
  this.loadSelectedDatePlannedWOs(day.date);
}

getSelectedDateDisplay(): string {
  const d = this.parseYmd(this.selectedCalendarDate);
  if (!d) return '-';
  return d.toLocaleDateString('en-GB');
}

  applyFilter() {
    this.onSearchChange();
  }

  getOrderQty(wo: any): string {
    const candidates = [wo?.order_qty, wo?.orderQty, wo?.planQty, wo?.plan_qty, wo?.batch_size];
    for (const c of candidates) {
      if (c !== '' && c != null) {
        return String(c);
      }
    }
    return '—';
  }

  getOrderUom(wo: any): string {
    const candidates = [wo?.orderUnit, wo?.uom, wo?.planUnit, wo?.plan_unit];
    for (const c of candidates) {
      const s = (c ?? '').toString().trim();
      if (s) {
        return s;
      }
    }
    return 'Nos';
  }

  // ===================================================================
  // MFG / Packing month plan matrices (img 2 / img 3 style grids)
  // Rows: Floor (Section) -> Line -> Work Order, columns: day Plan/Achieved
  // ===================================================================
  // Fixed-column widths per sheet (matches the uploaded Excel layouts)
  readonly pmMfgWidths = [80, 95, 105, 110, 100, 230, 80, 85];
  readonly pmPackWidths = [110, 100, 105, 120, 230, 70, 95, 70];
  readonly pmDayColWidth = 46;

  // Packing shifts (2 x 12h: Day / Night)
  readonly shiftHours = 12;
  readonly packShifts: { key: string; label: string }[] = [
    { key: 'day', label: 'Day' },
    { key: 'night', label: 'Night' }
  ];
  readonly pmShiftColWidth = 44;

  planMatrixMonth = '';
  planMatrixMonthLabel = '';
  matrixLoading = false;
  matrixLoaded = false;
  matrixDateColumns: { key: string; label: string }[] = [];
  mfgRows: any[] = [];
  packingRows: any[] = [];
  // product_code -> { perHour, unit } derived from Line/Product mapping capacity
  private productCapacityMap: Record<string, { perHour: number; unit: string }> = {};

  get mfgTableWidth(): number {
    const fixed = this.pmMfgWidths.reduce((a, b) => a + b, 0);
    return fixed + this.matrixDateColumns.length * 2 * this.pmDayColWidth;
  }

  get packTableWidth(): number {
    const fixed = this.pmPackWidths.reduce((a, b) => a + b, 0);
    // each day = shifts x (Cap + Plan + Achieved)
    return fixed + this.matrixDateColumns.length * this.packShifts.length * 3 * this.pmShiftColWidth;
  }

  ensurePlanMatrixMonth(): void {
    if (!this.planMatrixMonth) {
      const d = new Date();
      this.planMatrixMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    }
    this.updatePlanMatrixLabel();
  }

  openPlanMatrixTab(): void {
    this.ensurePlanMatrixMonth();
    if (!this.matrixLoaded) {
      this.loadProductCapacityMap(() => this.loadPlanMatrices());
    }
  }

  /** Build product_code -> packing speed (per hour) from the Line/Product mapping. */
  private loadProductCapacityMap(done?: () => void): void {
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe(
      (lines: any) => {
        const map: Record<string, { perHour: number; unit: string }> = {};
        (Array.isArray(lines) ? lines : []).forEach((line: any) => {
          (line?.mappedProduct || []).forEach((p: any) => {
            const code = (p?.product_code || '').toString().trim();
            if (!code) {
              return;
            }
            const parsed = this.capacityFromMapped(p);
            if (parsed.perHour > 0 || !map[code]) {
              map[code] = parsed;
            }
          });
        });
        this.productCapacityMap = map;
        if (done) {
          done();
        }
      },
      () => {
        this.productCapacityMap = {};
        if (done) {
          done();
        }
      }
    );
  }

  /**
   * Resolve a mapped product's packing speed into { perHour, unit }.
   * Handles both split fields (product_capacity_value / _rate_unit) and the
   * combined string form (e.g. "10 NOS / hr").
   */
  private capacityFromMapped(p: any): { perHour: number; unit: string } {
    const combined = (p?.product_capacity || '').toString();

    // Numeric value: prefer explicit field, else first number in combined string.
    let val = parseFloat((p?.product_capacity_value ?? '').toString());
    if (isNaN(val)) {
      const m = combined.match(/([\d.]+)/);
      val = m ? parseFloat(m[1]) : NaN;
    }

    // Rate unit: prefer explicit rate, else detect from combined / unit string.
    let rate = (p?.product_capacity_rate_unit || '').toString();
    if (!/sec|min|hr/i.test(rate)) {
      const src = `${combined} ${p?.product_capacity_unit || ''}`.toLowerCase();
      rate = /sec/.test(src) ? '/sec' : /min/.test(src) ? '/min' : '/hr';
    }

    // Display unit (e.g. NOS), stripping any rate part.
    let unit = (p?.product_capacity_unit || '').toString().replace(/\/.*/, '').trim();
    if (!unit && combined) {
      const um = combined.replace(/[\d.]+/, '').replace(/\/.*/, '').trim();
      unit = um;
    }

    return { perHour: this.toPerHour(val, rate), unit };
  }

  /** Convert a capacity value + rate unit (/ sec, / min, / hr) into units per hour. */
  private toPerHour(value: any, rateUnit: any): number {
    const v = parseFloat((value ?? '').toString());
    if (isNaN(v)) {
      return 0;
    }
    const r = (rateUnit ?? '').toString().toLowerCase();
    if (r.includes('sec')) {
      return v * 3600;
    }
    if (r.includes('min')) {
      return v * 60;
    }
    return v; // per hour (default)
  }

  updatePlanMatrixLabel(): void {
    const [y, m] = (this.planMatrixMonth || '').split('-').map(Number);
    this.planMatrixMonthLabel = y && m
      ? new Date(y, m - 1, 1).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })
      : '';
  }

  onPlanMatrixMonthChange(): void {
    this.updatePlanMatrixLabel();
    this.loadPlanMatrices();
  }

  private buildMatrixDateColumns(month: string): { key: string; label: string }[] {
    const [y, m] = (month || '').split('-').map(Number);
    if (!y || !m) {
      return [];
    }
    const days = new Date(y, m, 0).getDate();
    const cols: { key: string; label: string }[] = [];
    for (let d = 1; d <= days; d++) {
      cols.push({ key: `${month}-${String(d).padStart(2, '0')}`, label: String(d) });
    }
    return cols;
  }

  loadPlanMatrices(): void {
    if (!this.planMatrixMonth) {
      return;
    }
    this.matrixLoading = true;
    this.matrixDateColumns = this.buildMatrixDateColumns(this.planMatrixMonth);
    const [y, m] = this.planMatrixMonth.split('-').map(Number);
    const startDate = `${this.planMatrixMonth}-01`;
    const endDate = `${this.planMatrixMonth}-${String(new Date(y, m, 0).getDate()).padStart(2, '0')}`;
    this.service
      .get(`bmr/line_booking.php?type=getStpPlannedCalendar&start_date=${startDate}&end_date=${endDate}`)
      .subscribe(
        (res: any) => {
          const planned = Array.isArray(res) ? res : (res?.work_orders || res?.data || []);
          // Also pull already-approved WOs so approval does NOT remove them from the grids.
          this.service.get('bmr/line_booking.php?type=getApprovedWorkOrdersLog').subscribe(
            (appRes: any) => {
              const approvedRaw = Array.isArray(appRes) ? appRes : (appRes?.work_orders || appRes?.data || []);
              const approved = (Array.isArray(approvedRaw) ? approvedRaw : []).filter((w) => this.woInMatrixMonth(w));
              this.renderMatrices(this.mergeWos(planned, approved));
            },
            () => this.renderMatrices(planned)
          );
        },
        () => {
          this.mfgRows = [];
          this.packingRows = [];
          this.matrixLoading = false;
          this.matrixLoaded = true;
        }
      );
  }

  private renderMatrices(wos: any[]): void {
    this.mfgRows = this.buildMfgRows(wos);
    this.packingRows = this.buildPackingRows(wos);
    this.matrixLoading = false;
    this.matrixLoaded = true;
  }

  /** True when an approved WO belongs to the selected plan month (keeps approved entries visible). */
  private woInMatrixMonth(wo: any): boolean {
    const start = (wo?.expected_production_start_date || '').toString().slice(0, 7);
    if (start) {
      return start === this.planMatrixMonth;
    }
    return true;
  }

  /** Merge planned + approved WOs, de-duplicated by work order number (planned wins). */
  private mergeWos(planned: any[], approved: any[]): any[] {
    const seen = new Set((planned || []).map((w) => w?.workorder_no));
    const out = [...(planned || [])];
    for (const w of approved || []) {
      if (!seen.has(w?.workorder_no)) {
        out.push(w);
        seen.add(w?.workorder_no);
      }
    }
    return out;
  }

  private lineMatchesType(line: any, kind: 'mfg' | 'packing'): boolean {
    const lineType = `${line?.lineType ?? line?.line_type_category ?? line?.Type ?? ''}`.toLowerCase().trim();
    const blob = `${lineType} ${line?.Section ?? ''} ${line?.line_name ?? ''} ${line?.line_no ?? ''}`.toLowerCase();

    const isPacking =
      lineType === 'packing' ||
      /(^|\s|\/)pack(ing)?(\s|$|\/)/.test(lineType) ||
      (!lineType && /(pack|blister|bottl|tube|label)/.test(blob));

    const isMfg =
      lineType.includes('manufactur') ||
      lineType.includes('mfg') ||
      lineType === 'manufacturing/filling' ||
      (!lineType && /(mfg|manu|mix|blend|granul|compress|prod|vessel)/.test(blob));

    if (kind === 'packing') {
      // Prefer explicit Packing type; do not treat Manufacturing/Filling as packing.
      if (lineType.includes('manufactur') || lineType.includes('mfg')) {
        return false;
      }
      return isPacking || (!isMfg && /(pack|blister|bottl)/.test(blob));
    }

    // MFG tab: Manufacturing/Filling and other non-packing lines
    if (isPacking && !lineType.includes('manufactur') && !lineType.includes('mfg') && !lineType.includes('filling')) {
      return false;
    }
    if (lineType.includes('manufactur') || lineType.includes('mfg') || lineType.includes('filling')) {
      return true;
    }
    return !isPacking;
  }

  private pickLine(wo: any, kind: 'mfg' | 'packing'): any {
    const lines = wo?.selectedLines || [];
    return lines.find((l: any) => this.lineMatchesType(l, kind)) || lines[0] || null;
  }

  private emptyDayMap(): Record<string, { plan: string; achieved: string }> {
    const map: Record<string, { plan: string; achieved: string }> = {};
    for (const c of this.matrixDateColumns) {
      map[c.key] = { plan: '', achieved: '' };
    }
    return map;
  }

  private woStartKey(wo: any): string {
    const raw = (wo?.expected_production_start_date || '').toString().slice(0, 10);
    if (raw && this.matrixDateColumns.some((c) => c.key === raw)) {
      return raw;
    }
    return this.matrixDateColumns.length ? this.matrixDateColumns[0].key : '';
  }

  private matrixPlanValue(wo: any): string {
    const qty = Number(wo?.plan_qty || 0);
    const unit = wo?.planUnit || '';
    return qty ? `${qty}${unit ? ' ' + unit : ''}` : '';
  }

  /** Best-effort merge of backend-supplied achieved (from BMR) into the day map. */
  private applyAchievedFromWo(wo: any, dates: Record<string, { plan: string; achieved: string }>): void {
    const arr = wo?.achieved || wo?.dailyAchieved || wo?.achieved_by_date;
    if (Array.isArray(arr)) {
      for (const a of arr) {
        const key = (a?.date || '').toString().slice(0, 10);
        if (dates[key]) {
          dates[key].achieved = (a?.qty ?? a?.achieved ?? '').toString();
        }
      }
    } else if (arr && typeof arr === 'object') {
      for (const rawKey of Object.keys(arr)) {
        const key = rawKey.slice(0, 10);
        if (dates[key]) {
          dates[key].achieved = (arr[rawKey] ?? '').toString();
        }
      }
    }
  }

  /** MFG sheet (img 2): Plant -> Vessel -> WO, with merged Plant/Vessel cells. */
  private buildMfgRows(wos: any[]): any[] {
    const plants = new Map<string, Map<string, any[]>>();
    for (const wo of wos || []) {
      const line = this.pickLine(wo, 'mfg');
      const plant = ((line?.Section || line?.plant || '') + '').trim() || 'Unassigned';
      const vessel = ((line?.line_name || line?.line_no || '') + '').trim() || 'Unassigned';
      if (!plants.has(plant)) {
        plants.set(plant, new Map());
      }
      const vm = plants.get(plant);
      if (!vm.has(vessel)) {
        vm.set(vessel, []);
      }
      const dates = this.emptyDayMap();
      const startKey = this.woStartKey(wo);
      const planVal = this.matrixPlanValue(wo);
      if (startKey && dates[startKey]) {
        dates[startKey].plan = planVal;
      }
      this.applyAchievedFromWo(wo, dates);
      vm.get(vessel).push({
        workorder_no: wo.workorder_no || '',
        company_name: wo.mainGroupName || '',
        formula_no: wo.formula_no || wo.formula || wo.product_code || '',
        product_name: wo.product_name || '',
        total_plan: planVal,
        plan_qty: Number(wo.plan_qty || 0),
        planUnit: wo.planUnit || '',
        dates
      });
    }

    const flat: any[] = [];
    for (const [plant, vm] of plants) {
      let plantRowspan = 0;
      for (const rows of vm.values()) {
        plantRowspan += rows.length;
      }
      let plantShown = false;
      for (const [vessel, rows] of vm) {
        let vesselShown = false;
        for (const r of rows) {
          flat.push({
            ...r,
            plant,
            showPlant: !plantShown,
            plantRowspan,
            vessel,
            showVessel: !vesselShown,
            vesselRowspan: rows.length
          });
          plantShown = true;
          vesselShown = true;
        }
      }
    }
    return flat;
  }

  /** Packing sheet (img 3): flat product list, each day split into shifts with Cap/Plan/Achieved. */
  private buildPackingRows(wos: any[]): any[] {
    const rows: any[] = [];
    for (const wo of wos || []) {
      const code = (wo.product_code || '').toString().trim();
      const cap = this.productCapacityMap[code];
      const perHour = cap?.perHour || this.toPerHour(wo.line_speed, wo.line_speed_unit) || 0;
      const capPerShift = perHour > 0 ? Math.round(perHour * this.shiftHours) : 0;
      const speedLabel = perHour > 0 ? `${perHour}${cap?.unit ? ' ' + cap.unit : ''} /hr` : '';

      const shiftDates = this.emptyShiftDayMap(capPerShift);
      const startKey = this.woStartKey(wo);
      const planVal = this.matrixPlanValue(wo);
      this.distributePackingPlan(shiftDates, Number(wo.plan_qty || 0), startKey, capPerShift);
      this.applyShiftAchievedFromWo(wo, shiftDates);

      rows.push({
        material_code: code,
        formula_no: wo.formula_no || wo.formula || code,
        company: wo.mainGroupName || '',
        manufacturer: wo.manufacturer || wo.client_name || wo.mainGroupName || '',
        fg_description: wo.product_name || '',
        speed: speedLabel,
        packing: wo.packing_type || '',
        days: wo.no_of_hours_required || '',
        total_plan: planVal,
        plan_qty: Number(wo.plan_qty || 0),
        planUnit: wo.planUnit || '',
        capPerShift,
        shiftDates
      });
    }
    return rows;
  }

  /**
   * Spread the plan qty across day/shift cells from the expected start date,
   * filling each shift up to its capacity until the quantity is exhausted.
   */
  private distributePackingPlan(
    shiftDates: Record<string, Record<string, { cap: number; plan: string; achieved: string }>>,
    planQty: number,
    startKey: string,
    capPerShift: number
  ): void {
    let remaining = Number(planQty) || 0;
    if (remaining <= 0) {
      return;
    }
    let startIdx = this.matrixDateColumns.findIndex((c) => c.key === startKey);
    if (startIdx < 0) {
      startIdx = 0;
    }
    const cap = Number(capPerShift) || 0;

    // No capacity known -> put the whole quantity in the first shift of the start day.
    if (cap <= 0) {
      const key = this.matrixDateColumns[startIdx]?.key;
      const fs = this.packShifts[0]?.key;
      if (key && fs && shiftDates[key]) {
        shiftDates[key][fs].plan = String(remaining);
      }
      return;
    }

    for (let di = startIdx; di < this.matrixDateColumns.length && remaining > 0; di++) {
      const key = this.matrixDateColumns[di].key;
      for (const s of this.packShifts) {
        if (remaining <= 0) {
          break;
        }
        const alloc = Math.min(cap, remaining);
        shiftDates[key][s.key].plan = String(alloc);
        remaining -= alloc;
      }
    }
  }

  private emptyShiftDayMap(
    capPerShift: number
  ): Record<string, Record<string, { cap: number; plan: string; achieved: string }>> {
    const map: Record<string, Record<string, { cap: number; plan: string; achieved: string }>> = {};
    for (const c of this.matrixDateColumns) {
      map[c.key] = {};
      for (const s of this.packShifts) {
        map[c.key][s.key] = { cap: capPerShift, plan: '', achieved: '' };
      }
    }
    return map;
  }

  private applyShiftAchievedFromWo(
    wo: any,
    shiftDates: Record<string, Record<string, { cap: number; plan: string; achieved: string }>>
  ): void {
    const arr = wo?.achieved || wo?.dailyAchieved || wo?.achieved_by_date;
    if (!Array.isArray(arr)) {
      return;
    }
    for (const a of arr) {
      const key = (a?.date || '').toString().slice(0, 10);
      const shift = (a?.shift || this.packShifts[0]?.key || 'day').toString().toLowerCase();
      if (shiftDates[key] && shiftDates[key][shift]) {
        shiftDates[key][shift].achieved = (a?.qty ?? a?.achieved ?? '').toString();
      }
    }
  }

  packingBalance(row: any): string {
    let achieved = 0;
    for (const c of this.matrixDateColumns) {
      for (const s of this.packShifts) {
        const v = parseFloat(row?.shiftDates?.[c.key]?.[s.key]?.achieved);
        if (!isNaN(v)) {
          achieved += v;
        }
      }
    }
    const bal = Number(row?.plan_qty || 0) - achieved;
    return isNaN(bal) ? '' : String(bal);
  }

  matrixBalance(row: any): string {
    let achieved = 0;
    for (const c of this.matrixDateColumns) {
      const v = parseFloat(row?.dates?.[c.key]?.achieved);
      if (!isNaN(v)) {
        achieved += v;
      }
    }
    const bal = Number(row?.plan_qty || 0) - achieved;
    return isNaN(bal) ? '' : String(bal);
  }

  saveMatrixAchieved(kind: 'mfg' | 'packing'): void {
    const records: any[] = [];
    if (kind === 'mfg') {
      for (const r of this.mfgRows) {
        for (const c of this.matrixDateColumns) {
          const a = r?.dates?.[c.key]?.achieved;
          if (a !== '' && a != null) {
            records.push({
              workorder_no: r.workorder_no,
              product_code: r.formula_no,
              line: r.vessel,
              floor: r.plant,
              date: c.key,
              achieved: a,
              stage: 'mfg'
            });
          }
        }
      }
    } else {
      for (const r of this.packingRows) {
        for (const c of this.matrixDateColumns) {
          for (const s of this.packShifts) {
            const cell = r?.shiftDates?.[c.key]?.[s.key];
            const hasPlan = cell?.plan !== '' && cell?.plan != null;
            const hasAch = cell?.achieved !== '' && cell?.achieved != null;
            if (hasPlan || hasAch) {
              records.push({
                material_code: r.material_code,
                product_code: r.material_code,
                date: c.key,
                shift: s.key,
                capacity: cell.cap,
                plan: cell.plan,
                achieved: cell.achieved,
                stage: 'packing'
              });
            }
          }
        }
      }
    }
    if (!records.length) {
      alert('Nothing to save. Enter plan / achieved quantities first.');
      return;
    }
    this.service
      .post(
        `bmr/line_booking.php?type=saveStpAchieved`,
        JSON.stringify({ stage: kind, plan_month: this.planMatrixMonth, records })
      )
      .subscribe(
        (res: any) => {
          if (res?.status === 'success') {
            alert('Achieved quantities saved.');
          } else {
            alert('Failed to save achieved: ' + (res?.message || 'Please try again'));
          }
        },
        () => alert('Failed to save achieved. Please try again.')
      );
  }

  /** Save STP plan for a single row filled inline in the Line Booking table. */
  savePlanStpRow(wo: any) {
    if (!wo) {
      alert('No work order selected');
      return;
    }

    this.normalizeWorkOrderPlanningFields(wo);
    const validation = this.validateWorkOrderForParking(wo);
    if (!validation.valid) {
      alert(validation.message);
      return;
    }
    if (this.getLinesForMode(wo).length === 0) {
      alert('Please select a line number before saving');
      return;
    }
    if (wo._bookingValidation?.pending) {
      alert('Line validation is still running. Please wait a moment and try again.');
      return;
    }
    if (
      wo._bookingValidation &&
      wo._bookingValidation.valid === false &&
      !this.isValidationUnavailable(wo._bookingValidation)
    ) {
      alert('Line booking validation failed: ' + (wo._bookingValidation.errors || []).join('; '));
      return;
    }

    const selectedLines = this.getLinesForMode(wo).map((line: any) => {
      const master = (this.lineMasterData || []).find(
        (m: any) => String(m.id) === String(line.id),
      );
      return master ? { ...master, ...line } : line;
    });

    const payload = {
      work_orders: [
        {
          workorder_no: wo.workorder_no,
          workorder_id: wo.id,
          selectedLines,
          expected_production_start_date: wo.expected_production_start_date || '',
          expected_production_start_time: wo.expected_production_start_time || '',
          expected_production_end_date: wo.expected_production_end_date || '',
          expected_production_end_time: wo.expected_production_end_time || '',
          responsible_person: wo.responsible_person || '',
          no_of_hours_required: wo.no_of_hours_required || '',
          plan_qty: wo.plan_qty || '',
          planUnit: wo.planUnit || '',
          product_code: wo.product_code || '',
          product_name: wo.product_name || '',
          mainGroupName: wo.mainGroupName || ''
        }
      ]
    };

    this.service.post(
      `bmr/line_booking.php?type=saveStpPlan`,
      payload,
    ).subscribe((response: any) => {
      if (response?.status === 'success') {
        const successCount = response?.planned_count || 1;
        const failedCount = response?.failed_count || 0;
        const failedDetails = (response?.failed || [])
          .map((item: any) => `${item.workorder_no || 'WO'}: ${item.message || 'Failed'}`)
          .join('\n');
        this.stpPlannedCalendarMode = true;
        this.initializeCalendar();
        this.loadLiveBoard();
        this.loadUpdatableBookings();
        if (failedCount > 0) {
          alert(`Planned ${successCount} work order(s). ${failedCount} failed.\n${failedDetails}`);
        } else {
          alert(`Planned ${successCount} work order(s) in STP. Line is now shown as Occupied on Live Board. Open Calendar to Send for Line Approval.`);
        }
        this.getPendingWOs();
        this.getLogData();
      } else {
        const failedDetails = (response?.failed || [])
          .map((item: any) => `${item.workorder_no || 'WO'}: ${item.message || 'Failed'}`)
          .join('\n');
        alert('Failed to plan for STP: ' + (response?.message || failedDetails || 'Please try again'));
      }
    }, () => {
      alert('Failed to plan for STP. Please try again.');
    });
  }
  private buildQueueUrl(): string {
    let url =
      'bmr/line_booking.php?type=getVerifiedWorkOrders&page=' +
      this.currentPage +
      '&limit=' +
      this.pageSize;
    const q = (this.searchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  getPendingWOs() {
    this.loading = true;
    this.service.get(this.buildQueueUrl()).subscribe({
      next: (response: any) => {
        const rows = Array.isArray(response)
          ? response
          : (response?.work_orders || response?.data || []);
        this.totalRecords = Array.isArray(response)
          ? rows.length
          : Number(response?.total ?? rows.length) || 0;
        this.pendingpo = rows;
        this.pendingpo.forEach((wo: any) => {
          wo._selectedLineId = this.getSelectedLineIdForMode(wo);
        });
        this.pendingpoBackup = this.pendingpo;
        if (this.pendingpo.length === 0 && this.currentPage > 1 && this.totalRecords > 0) {
          this.currentPage -= 1;
          this.getPendingWOs();
          return;
        }
        this.syncSelectedPlanningWoAfterListRefresh();
        this.loading = false;
      },
      error: () => {
        this.pendingpo = [];
        this.totalRecords = 0;
        this.loading = false;
      },
    });
  }

  /**
   * After getPendingWOs(), the list is replaced with fresh API rows. If the STP modal is open,
   * scheduling fields (dates, responsible person) may only exist in-memory until saveStpPlan —
   * merging them back avoids clearing the form when the user saves selected lines first.
   */
  private mergeServerWoWithOpenStpDraft(serverRow: any, draft: any): any {
    const merged = { ...serverRow };
    const keys = [
      'responsible_person',
      'expected_production_start_date',
      'expected_production_start_time',
      'expected_production_end_date',
      'expected_production_end_time',
    ] as const;
    for (const k of keys) {
      const v = draft[k];
      if (v != null && String(v).trim() !== '') {
        merged[k] = v;
      }
    }
    return merged;
  }

  private syncSelectedPlanningWoAfterListRefresh() {
    const cur = this.selectedPlanningWo;
    if (!cur || !this.showStpPlanningModal) {
      return;
    }
    const id = cur.id;
    const woNo = cur.workorder_no;
    const found = this.pendingpo.find(
      (w: any) => (id != null && w.id === id) || (woNo && w.workorder_no === woNo)
    );
    if (found) {
      this.selectedPlanningWo = this.mergeServerWoWithOpenStpDraft(found, cur);
      this.normalizeWorkOrderPlanningFields(this.selectedPlanningWo);
      this.calculateHours(this.selectedPlanningWo);
    } else {
      this.showStpPlanningModal = false;
      this.selectedPlanningWo = null;
    }
  }
  emps;
  get_Eqgetemployee_byDeptipments() {
        this.loading = true;
        this.service.get('common.php?type=get_Eqgetemployee_byDeptipments&depart=Production').subscribe(response => {
      this.emps = response;
        
      
    });      
  }

  
 
 isView=false;
 selectedWo: any = null;
 View(wo:any){
  this.isView=true;
  this.selectedWo=wo;
  this.lineList = this.getLinesForMode(wo);
  
  // If lines are already saved, show saved lines instead of loading available lines
  if (this.getLinesForMode(wo).length > 0) {
    this.selectedWo['Lines'] = this.getLinesForMode(wo);
  } else {
    // Load available lines for this work order's product
    if (wo.product_code && wo.mainGroupName) {
      this.loadAvailableLines(wo);
    } else {
      this.selectedWo['Lines'] = [];
    }
  }
  
  if (this.selectedWo['Deductions'] && this.selectedWo['Deductions'].length > 0) {
    for(let i=0;i<this.selectedWo['Deductions'].length;i++){
      let wo=this.selectedWo['Deductions'][i];
      wo['requiredQty']=Number(wo['deducted_from_MC'])+Number(wo['deducted_from_RM'])+Number(wo['shortage']);
    }
  }
 }
 
 // Load available lines based on product
 loadAvailableLines(wo: any) {
   const lineType = encodeURIComponent(this.getBookingLineTypeParam());
   const params = `product_code=${wo.product_code}&workorder_no=${wo.workorder_no}&start_date=${wo.expected_production_start_date || ''}&start_time=${wo.expected_production_start_time || ''}&end_date=${wo.expected_production_end_date || ''}&end_time=${wo.expected_production_end_time || ''}&line_type=${lineType}`;
   
   this.service.get(`bmr/line_booking.php?type=getAvailableLines&${params}`)
     .subscribe((response: any) => {
       if (response && response.length > 0) {
         this.selectedWo['Lines'] = response;
       } else {
         // Fallback: Get lines from process.php if API doesn't return lines
         this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe((lines: any) => {
           // Filter lines by dosage form
           if (lines && lines.length > 0) {
             const filteredLines = lines.filter((line: any) => {
               const matchesGroup = line.stages && line.stages.length > 0
                 ? line.stages.some((stage: any) => stage.dosage_form === wo.mainGroupName)
                 : false;
               const mode = this.bookingLineMode === 'packing' ? 'packing' : 'mfg';
               return matchesGroup && this.lineMatchesType(line, mode);
             });
             this.selectedWo['Lines'] = filteredLines;
           } else {
             this.selectedWo['Lines'] = [];
           }
         });
       }
     });
 }

 // Helper method to display stages
 getStagesDisplay(stages: any[]): string {
   if (!stages || stages.length === 0) return '';
   return stages.map((s: any) => `${s.dosage_form || ''} - ${s.stage || ''}`).filter((s: string) => s.trim() !== '-').join(', ');
 }
 isView2=false;
 View2(wo:any){
  this.isView2=true;
  this.selectedWo=wo;
  this.selectedWo['Lines'] = this.getLinesForMode(wo);
  
  if (this.selectedWo['Deductions'] && this.selectedWo['Deductions'].length > 0) {
    for(let i=0;i<this.selectedWo['Deductions'].length;i++){
      let wo=this.selectedWo['Deductions'][i];
      wo['requiredQty']=Number(wo['deducted_from_MC'])+Number(wo['deducted_from_RM'])+Number(wo['shortage']);
    }
  }
 }
 ViewMaterials(wo:any){
  this.isMaterials=true;
  this.selectedWo=wo;
  if (this.selectedWo['Deductions'] && this.selectedWo['Deductions'].length > 0) {
    for(let i=0;i<this.selectedWo['Deductions'].length;i++){
      let deduction=this.selectedWo['Deductions'][i];
      deduction['requiredQty']=Number(deduction['deducted_from_MC'] || 0)+Number(deduction['deducted_from_RM'] || 0)+Number(deduction['shortage'] || 0);
    }
  }
 }
selectedEquips=[];
isEquips=false;
ViewEquip(wo:any){
  this.isEquips=true;
  this.selectedWo=wo;
  this.selectedEquips = [];
  // Collect all equipment from all saved lines
  if (this.selectedWo['selectedLines'] && this.selectedWo['selectedLines'].length > 0) {
    for(let i=0; i < this.selectedWo['selectedLines'].length; i++){
      if (this.selectedWo['selectedLines'][i]['equipmentList'] && this.selectedWo['selectedLines'][i]['equipmentList'].length > 0) {
        this.selectedEquips = this.selectedEquips.concat(this.selectedWo['selectedLines'][i]['equipmentList']);
      }
    }
  }
  console.log('selectedEquips (all saved lines):>> ', this.selectedEquips);
}




   isequip=false;
  mappedProduct=[];
  equipmentList=[];
  AddEqup2(index){
 
   
    this.isequip=true;
    this.equipmentList=this.selectedWo['selectedLines'][index]['equipmentList'];
  }
  AddEqup(index){
 
   
    this.isequip=true;
    this.equipmentList=this.selectedWo['Lines'][index]['equipmentList'];
  }

lineList: any[] = [];
selectedEquipmentsForBooking: any[] = [];
isBookingModal: boolean = false;
bookingData: any = {};

proceedLinePlanning(line: any) {
  if (!this.isLineSelected(line)) {
    this.lineList.push(line);
  }
}

removeLine(line: any) {
  this.lineList = this.lineList.filter(l => l.line_no !== line.line_no);
}

isLineSelected(line: any): boolean {
  return this.lineList.some(l => l.line_no === line.line_no);
}

// Open booking modal for a line
openBookingModal(wo: any, line: any) {
  this.selectedWo = wo;
  this.bookingData = {
    workorder_no: wo.workorder_no,
    linemaster_id: line.id,
    line_no: line.line_no,
    product_code: wo.product_code,
    product_name: wo.product_name,
    booking_start_date: wo.expected_production_start_date || '',
    booking_start_time: wo.expected_production_start_time || '',
    booking_end_date: wo.expected_production_end_date || '',
    booking_end_time: wo.expected_production_end_time || '',
    responsible_person: wo.responsible_person || '',
    selected_equipments: [],
    capacity_required: wo.batch_size || '',
    no_of_hours_required: wo.no_of_hours_required || 0,
    status: 'Booked'
  };
  this.selectedEquipmentsForBooking = [];
  this.isBookingModal = true;
  
  // Load available equipments for this line
  if (line.equipmentList && line.equipmentList.length > 0) {
    this.selectedEquipmentsForBooking = line.equipmentList.map((eq: any) => ({
      ...eq,
      selected: false
    }));
  }
}

// Toggle equipment selection
toggleEquipment(equipment: any) {
  equipment.selected = !equipment.selected;
}

// Check line availability before booking
checkAvailability(wo: any, line: any) {
  if (!wo.expected_production_start_date || !wo.expected_production_end_date) {
    alert('Please set production start and end dates first');
    return;
  }
  
  const params = {
    linemaster_id: line.id,
    start_date: wo.expected_production_start_date,
    start_time: wo.expected_production_start_time || '00:00:00',
    end_date: wo.expected_production_end_date,
    end_time: wo.expected_production_end_time || '23:59:59'
  };
  
  this.service.get(`bmr/line_booking.php?type=checkLineAvailability&linemaster_id=${params.linemaster_id}&start_date=${params.start_date}&start_time=${params.start_time}&end_date=${params.end_date}&end_time=${params.end_time}`)
    .subscribe((response: any) => {
      if (response.isAvailable) {
        this.openBookingModal(wo, line);
      } else {
        alert(`Line is not available. Conflicts with: ${response.conflicts.map((c: any) => c.workorder_no).join(', ')}`);
      }
    });
}

// Book line
bookLine() {
  if (!this.bookingData.booking_start_date || !this.bookingData.booking_end_date) {
    alert('Please set booking start and end dates');
    return;
  }
  
  if (!this.bookingData.responsible_person) {
    alert('Please select a responsible person');
    return;
  }
  
  // Get selected equipments
  const selectedEquips = this.selectedEquipmentsForBooking
    .filter((eq: any) => eq.selected)
    .map((eq: any) => ({
      equipment_name: eq.equipment_name,
      equipment_code: eq.equipment_code,
      capacity: eq.capacity,
      from_range: eq.from_range,
      to_range: eq.to_range,
      unit: eq.unit,
      equipment_lineType: eq.equipment_lineType || ''
    }));
  
  this.bookingData.selected_equipments = selectedEquips;
  
  this.loading = true;
  this.service.post(
    `bmr/line_booking.php?type=bookLine`,
    JSON.stringify(this.bookingData)
  ).subscribe((response: any) => {
    this.loading = false;
    if (response.status === 'success') {
      // Show warning if conflicts exist but booking was successful
      if (response.warning) {
        alert(response.warning + '\n\nLine booked successfully despite conflicts.');
      } else {
        alert('Line booked successfully');
      }
      this.isBookingModal = false;
      this.getPendingWOs();
    } else {
      alert('Error: ' + (response.message || 'Failed to book line'));
      if (response.conflicts) {
        alert('Conflicts with: ' + response.conflicts.join(', '));
      }
    }
  });
}

SaveLine(){
  if (this.lineList.length === 0) {
    alert('Please select at least one line');
    return;
  }

  if (!this.selectedWo || !this.selectedWo.workorder_no) {
    alert('Work order information is missing');
    return;
  }

  const mergedLines = this.mergeLinesForCategory(
    this.selectedWo.selectedLines || [],
    this.lineList,
    this.bookingLineMode
  );

  let temp={};
  temp['id']=this.selectedWo['id'];
  temp['lineList']=mergedLines;

  this.service.post(
    `marketing/po.php?type=update_SelectedLines`,
    JSON.stringify(temp)
  ).subscribe((response: any) => {
    if (response.status === 'success') {
      alert('Lines saved successfully! Fill all required fields and proceed to approval when ready.');
      this.selectedWo.selectedLines = mergedLines;
      this.lineList = [];
      this.isView = false;
      this.isUpdateBookingView = false;
      this.getPendingWOs();
      if (this.updateBookingList.length) {
        this.loadUpdatableBookings();
      }
    } else {
      alert('An error has occurred while saving lines: ' + (response.message || response.status));
    }
  });
}

// Park selected lines in mfglines
parkLinesForMfg() {
  const parkData = {
    workorder_no: this.selectedWo.workorder_no,
    workorder_id: this.selectedWo.id,
    lineList: this.lineList,
    product_code: this.selectedWo.product_code || '',
    product_name: this.selectedWo.product_name || '',
    booking_start_date: this.selectedWo.expected_production_start_date || '',
    booking_start_time: this.selectedWo.expected_production_start_time || '',
    booking_end_date: this.selectedWo.expected_production_end_date || '',
    booking_end_time: this.selectedWo.expected_production_end_time || '',
    responsible_person: this.selectedWo.responsible_person || '',
    batch_size: this.selectedWo.batch_size || this.selectedWo.plan_qty || '',
    planUnit: this.selectedWo.planUnit || ''
  };

  this.service.post(
    `bmr/line_booking.php?type=parkLinesForMfg`,
    JSON.stringify(parkData)
  ).subscribe((response: any) => {
    if (response.status === 'success') {
      alert('Lines saved and parked successfully! ' + (response.message || ''));
      this.lineList = []; // Clear selection
      this.isView = false; // Close modal
      this.getPendingWOs(); // Refresh list
    } else {
      alert('Lines saved but parking failed: ' + (response.message || 'Please try again'));
    }
  }, (error) => {
    console.error('Error parking lines:', error);
    alert('Lines saved but parking failed. Please try again.');
  });
}

  /** Submit the work order open in the STP planning modal (single batch). */
  proceedPlanStpFromModal() {
    const wo = this.selectedPlanningWo;
    if (!wo) {
      alert('No work order open');
      return;
    }

    this.normalizeWorkOrderPlanningFields(wo);
    const validation = this.validateWorkOrderForParking(wo);
    if (!validation.valid) {
      alert(validation.message);
      return;
    }

    const payload = {
      work_orders: [
        {
          workorder_no: wo.workorder_no,
          workorder_id: wo.id,
          selectedLines: wo.selectedLines || [],
          expected_production_start_date: wo.expected_production_start_date || '',
          expected_production_start_time: wo.expected_production_start_time || '',
          expected_production_end_date: wo.expected_production_end_date || '',
          expected_production_end_time: wo.expected_production_end_time || '',
          responsible_person: wo.responsible_person || '',
          plan_qty: wo.plan_qty || '',
          planUnit: wo.planUnit || '',
          product_code: wo.product_code || '',
          product_name: wo.product_name || '',
          mainGroupName: wo.mainGroupName || ''
        }
      ]
    };

    this.service.post(
      `bmr/line_booking.php?type=saveStpPlan`,
      JSON.stringify(payload)
    ).subscribe((response: any) => {
      if (response?.status === 'success') {
        const successCount = response?.planned_count || 1;
        const failedCount = response?.failed_count || 0;
        this.stpPlannedCalendarMode = true;
        this.initializeCalendar();
        this.closeStpPlanningModal();
        setTimeout(() => {
          const tabBtn = document.getElementById('stpCalendarTabBtn') as HTMLButtonElement | null;
          if (tabBtn) {
            tabBtn.click();
          }
        }, 0);
        alert(`Planned ${successCount} work order(s) in STP${failedCount > 0 ? `. ${failedCount} failed.` : ''}. Open Calendar, select them, and Send for Line Approval.`);
        this.getPendingWOs();
        this.getLogData();
      } else {
        alert('Failed to plan for STP: ' + (response?.message || 'Please try again'));
      }
    }, () => {
      alert('Failed to plan for STP. Please try again.');
    });
  }

  // Validate all required fields before parking
  validateWorkOrderForParking(wo: any): { valid: boolean; message: string } {
    if (!wo.selectedLines || wo.selectedLines.length === 0) {
      return { valid: false, message: 'No lines selected for this work order' };
    }

    const sd = this.planningFieldStr(wo.expected_production_start_date);
    const st = this.planningFieldStr(wo.expected_production_start_time);
    const ed = this.planningFieldStr(wo.expected_production_end_date);
    const et = this.planningFieldStr(wo.expected_production_end_time);

    if (!sd || !st) {
      return { valid: false, message: 'Please fill Expected Production Start Date and Time' };
    }

    if (!ed || !et) {
      return { valid: false, message: 'Please fill Expected Production End Date and Time' };
    }

    if (!wo.responsible_person || this.planningFieldStr(wo.responsible_person) === '') {
      return { valid: false, message: 'Please select Responsible Person' };
    }

    return { valid: true, message: '' };
  }



// Methods for editable date/time fields
getEditingKey(wo: any, field: string): string {
  return `${wo.id || wo.workorder_no}_${field}`;
}

isEditing(wo: any, field: string): boolean {
  return this.editingField[this.getEditingKey(wo, field)] === field;
}

startEditing(wo: any, field: string) {
  const key = this.getEditingKey(wo, field);
  this.editingField[key] = field;
}

stopEditing(wo: any, field: string) {
  const key = this.getEditingKey(wo, field);
  delete this.editingField[key];
  this.calculateHours(wo);
}

calculateHours(wo: any) {
  if (wo.expected_production_start_date && wo.expected_production_start_time &&
      wo.expected_production_end_date && wo.expected_production_end_time) {
    
    try {
      const st = this.planningFieldStr(wo.expected_production_start_time);
      const et = this.planningFieldStr(wo.expected_production_end_time);
      const stPart = st.length === 5 ? `${st}:00` : st;
      const etPart = et.length === 5 ? `${et}:00` : et;
      const startDate = new Date(`${wo.expected_production_start_date} ${stPart}`);
      const endDate = new Date(`${wo.expected_production_end_date} ${etPart}`);
      
      // Calculate difference in milliseconds
      const diffMs = endDate.getTime() - startDate.getTime();
      
      // Convert to hours
      const diffHours = diffMs / (1000 * 60 * 60);
      
      // Update the hours required field
      wo.no_of_hours_required = diffHours > 0 ? parseFloat(diffHours.toFixed(2)) : 0;
    } catch (error) {
      console.error('Error calculating hours:', error);
      wo.no_of_hours_required = 0;
    }
  } else {
    wo.no_of_hours_required = 0;
  }
}

getDisplayValue(wo: any, field: string): string {
  if (field === 'expected_production_start_date' || field === 'expected_production_end_date') {
    if (!wo[field]) return '';
    
    try {
      if (wo[field] instanceof Date) {
        const year = wo[field].getFullYear();
        const month = String(wo[field].getMonth() + 1).padStart(2, '0');
        const day = String(wo[field].getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
      
      if (typeof wo[field] === 'string' && wo[field].includes('-')) {
        const parts = wo[field].split('-');
        if (parts.length === 3) {
          // Check if format is dd-MM-YYYY (first part is 2 digits)
          if (parts[0].length === 2 && parts[1].length === 2 && parts[2].length === 4) {
            // Convert dd-MM-YYYY to YYYY-MM-DD for input
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
          } else if (parts[0].length === 4) {
            // Already in YYYY-MM-DD format
            return wo[field];
          }
        }
        return wo[field];
      }
      
      // Try to parse as date string
      const date = new Date(wo[field]);
      if (!isNaN(date.getTime())) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
      
      return wo[field];
    } catch (error) {
      return '';
    }
  }
  return wo[field] || '';
}

onFieldChange(wo: any, field: string, value: any) {
  if (field === 'expected_production_start_date' || field === 'expected_production_end_date') {
    // Store in YYYY-MM-DD format for API
    if (value) {
      wo[field] = value; // Keep as YYYY-MM-DD for API calls
    } else {
      wo[field] = '';
    }
    // Recalculate hours
    this.calculateHours(wo);
  } else {
    wo[field] = value;
    if (field === 'expected_production_start_time' || field === 'expected_production_end_time') {
      this.calculateHours(wo);
    }
  }
}

// Update booking data hours when dates change
onBookingDateChange(field: string, value: any) {
  if (field === 'booking_start_date' || field === 'booking_end_date') {
    this.bookingData[field] = value;
    this.calculateBookingHours();
  } else if (field === 'booking_start_time' || field === 'booking_end_time') {
    this.bookingData[field] = value;
    this.calculateBookingHours();
  }
}

// Calculate hours for booking
calculateBookingHours() {
  if (this.bookingData.booking_start_date && this.bookingData.booking_start_time &&
      this.bookingData.booking_end_date && this.bookingData.booking_end_time) {
    
    try {
      const startDate = new Date(this.bookingData.booking_start_date + ' ' + this.bookingData.booking_start_time);
      const endDate = new Date(this.bookingData.booking_end_date + ' ' + this.bookingData.booking_end_time);
      
      const diffMs = endDate.getTime() - startDate.getTime();
      const diffHours = diffMs / (1000 * 60 * 60);
      
      this.bookingData.no_of_hours_required = diffHours > 0 ? parseFloat(diffHours.toFixed(2)) : 0;
    } catch (error) {
      console.error('Error calculating booking hours:', error);
      this.bookingData.no_of_hours_required = 0;
    }
  } else {
    this.bookingData.no_of_hours_required = 0;
  }
}

// Get log data for parked for approval actions - returns work orders with Parked for Approval status
private buildLogUrl(): string {
  let url =
    'bmr/line_booking.php?type=getAdvancePlanningHistory&page=' +
    this.logCurrentPage +
    '&limit=' +
    this.logPageSize;
  const q = (this.logSearchText || '').trim();
  if (q) {
    url += '&search=' + encodeURIComponent(q);
  }
  return url;
}

getLogData() {
  this.loading = true;
  this.service.get(this.buildLogUrl()).subscribe({
    next: (response: any) => {
      const page = this.unwrapWoPage(response);
      this.logData = page.rows;
      this.logTotalRecords = page.total;
      this.logDataBackup = this.logData;
      if (this.logData.length === 0 && this.logCurrentPage > 1 && this.logTotalRecords > 0) {
        this.logCurrentPage -= 1;
        this.getLogData();
        return;
      }
      this.loading = false;
    },
    error: (err) => {
      this.loading = false;
      console.error('Error fetching log data:', err);
      alert('Error loading log data');
      this.logData = [];
      this.logDataBackup = [];
      this.logTotalRecords = 0;
    }
  });
}

applyLogFilter() {
  if (this.logSearchDebounceTimer) {
    clearTimeout(this.logSearchDebounceTimer);
  }
  this.logSearchDebounceTimer = setTimeout(() => {
    this.logCurrentPage = 1;
    this.getLogData();
  }, 350);
}

onLogPageChange(page: number): void {
  if (!page || page === this.logCurrentPage) {
    return;
  }
  this.logCurrentPage = page;
  this.getLogData();
}

onLogPageSizeChange(size: number): void {
  if (!size || size === this.logPageSize) {
    return;
  }
  this.logPageSize = size;
  this.logCurrentPage = 1;
  this.getLogData();
}

logStartSrNo(): number {
  return (this.logCurrentPage - 1) * this.logPageSize;
}

// Print log data
printLog() {
  const printContent = document.getElementById('logTable');
  if (!printContent) {
    alert('Log table not found');
    return;
  }

  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    alert('Please allow popups to print');
    return;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Parked For Approval Log</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
          th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
          th { background-color: #4CAF50; color: white; }
          tr:nth-child(even) { background-color: #f2f2f2; }
          h2 { text-align: center; margin-bottom: 20px; }
          .print-date { text-align: right; margin-bottom: 10px; }
        </style>
      </head>
      <body>
        <h2>Parked For Approval Log</h2>
        <div class="print-date">Printed on: ${new Date().toLocaleString()}</div>
        ${printContent.innerHTML}
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
}

























}


