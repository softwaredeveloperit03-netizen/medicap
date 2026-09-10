import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: { success: (msg: string) => void; error: (msg: string) => void };

/** One row in the saved-batch audit list (`list_save_log`). */
export interface ProcessTypeSaveLogEntry {
  batch_uid: string;
  entry_at: string;
  saved_by_emp_id: string;
  dosage_form: string;
  process_title: string;
  line_count: number;
}

/** One persisted hierarchy row from `batch_lines`. */
export interface ProcessTypeBatchLineRow {
  hierarchy_level?: string;
  sequence_path?: string;
  dosage_form?: string;
  process_title?: string;
  stage_no?: string;
  stage_title?: string;
  step_no?: string;
  step_title?: string;
  substep_no?: string;
  substep_title?: string;
  label_stage?: string;
  label_step?: string;
  label_substep?: string;
  provision_equipments?: string | number;
  provision_line_clearance?: string | number;
  provision_stage_yield?: string | number;
  provision_inprocess_analysis?: string | number;
  provision_weighing?: string | number;
  provision_procedure?: string | number;
  provision_additional_parameter?: string | number;
  additional_param_label?: string;
  additional_param_db_table?: string;
  additional_param_name?: string;
  additional_params_json?: string;
  in_process_checks_by?: string;
}

@Component({
  selector: 'app-process-type-save-log',
  templateUrl: './process-type-save-log.component.html',
  styleUrls: ['./process-type-save-log.component.css'],
})
export class ProcessTypeSaveLogComponent implements OnChanges {
  /** When false, loading is suppressed (same as hiding the panel). */
  @Input() active = false;

  /** Parent increments after schema sync succeeds or after a successful final DB save to refetch. */
  @Input() refreshTick = 0;

  saveLog: ProcessTypeSaveLogEntry[] = [];
  loadingSaveLog = false;

  detailModalOpen = false;
  detailLoading = false;
  detailListHeader: ProcessTypeSaveLogEntry | null = null;
  detailRows: ProcessTypeBatchLineRow[] = [];

  constructor(private readonly api: DataAccessService) {}

  ngOnChanges(changes: SimpleChanges): void {
    if (!this.active) {
      return;
    }

    const a = changes['active'];
    if (a) {
      const initialReady = a.firstChange === true && this.active === true;
      const activatedLater =
        !a.firstChange && a.previousValue !== true && this.active === true;
      if (initialReady || activatedLater) {
        this.loadSaveLog();
        return;
      }
    }

    const t = changes['refreshTick'];
    if (t && t.firstChange === false && t.currentValue !== t.previousValue) {
      this.loadSaveLog();
    }
  }

  loadSaveLog(): void {
    if (!this.active) {
      return;
    }
    this.loadingSaveLog = true;
    this.api.get('master/process_type_stage_master_api.php?type=list_save_log').subscribe({
      next: (res: unknown) => {
        this.loadingSaveLog = false;
        const o = res as {
          status?: string;
          entries?: ProcessTypeSaveLogEntry[];
          message?: string;
        };
        if (o?.status === 'success' && Array.isArray(o.entries)) {
          this.saveLog = o.entries.map((e) => ({
            ...e,
            line_count: Number(e.line_count) || 0,
          }));
        } else {
          this.saveLog = [];
          if (o?.message) {
            alertify.error(o.message);
          }
        }
      },
      error: () => {
        this.loadingSaveLog = false;
        this.saveLog = [];
      },
    });
  }

  openBatchDetail(entry: ProcessTypeSaveLogEntry): void {
    const uid = (entry.batch_uid || '').trim();
    if (!uid) {
      alertify.error('Missing batch id.');
      return;
    }
    this.detailListHeader = entry;
    this.detailRows = [];
    this.detailModalOpen = true;
    this.detailLoading = true;
    this.api
      .get(
        'master/process_type_stage_master_api.php?type=batch_lines&batch_uid=' + encodeURIComponent(uid)
      )
      .subscribe({
        next: (res: unknown) => {
          this.detailLoading = false;
          const o = res as { status?: string; rows?: ProcessTypeBatchLineRow[]; message?: string };
          if (o?.status === 'success' && Array.isArray(o.rows)) {
            this.detailRows = o.rows;
          } else {
            this.detailRows = [];
            alertify.error(o?.message || 'Could not load batch detail.');
          }
        },
        error: () => {
          this.detailLoading = false;
          this.detailRows = [];
          alertify.error('Could not load batch detail.');
        },
      });
  }

  onDetailModalChange(open: boolean): void {
    this.detailModalOpen = open;
    if (!open) {
      this.detailRows = [];
      this.detailListHeader = null;
    }
  }

  /** Main title line per row (stage / step / substep), sequential order preserved in API. */
  rowTitleLine(row: ProcessTypeBatchLineRow): string {
    const lv = (row.hierarchy_level || '').toLowerCase();
    if (lv === 'stage') {
      return `${row.label_stage || 'Stage'} ${row.stage_no || ''}: ${row.stage_title || ''}`;
    }
    if (lv === 'step') {
      return `${row.label_step || 'Step'} ${row.step_no || ''}: ${row.step_title || ''} (under stage ${row.stage_no || ''} ${row.stage_title || ''})`;
    }
    if (lv === 'substep') {
      return `${row.label_substep || 'Substep'} ${row.substep_no || ''}: ${row.substep_title || ''}`;
    }
    return row.sequence_path || '(row)';
  }

  ladderClass(row: ProcessTypeBatchLineRow): string {
    switch ((row.hierarchy_level || '').toLowerCase()) {
      case 'stage':
        return 'ptm-batch-ladder__row--stage';
      case 'step':
        return 'ptm-batch-ladder__row--step';
      default:
        return 'ptm-batch-ladder__row--substep';
    }
  }

  additionalParamsText(row: ProcessTypeBatchLineRow): string {
    const raw = (row.additional_params_json || '').trim();
    if (raw) {
      try {
        const arr = JSON.parse(raw) as { label?: string; db_table?: string; param_name?: string }[];
        if (Array.isArray(arr) && arr.length) {
          return arr
            .map((x) => `${x.label || ''} → ${x.db_table || ''}.${x.param_name || ''}`)
            .filter((s) => s.trim())
            .join('; ');
        }
      } catch {
        /* ignore */
      }
    }
    const leg = row.additional_param_label;
    const tbl = row.additional_param_db_table;
    const col = row.additional_param_name;
    if (leg && tbl && col) {
      return `${leg} → ${tbl}.${col}`;
    }
    return '';
  }
}
