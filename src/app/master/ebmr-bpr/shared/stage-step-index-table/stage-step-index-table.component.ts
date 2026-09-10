import { Component, EventEmitter, Input, Output } from '@angular/core';
import { StageStepIndexRow } from '../master-step-workflow.util';

export type StageStepIndexMode = 'builder' | 'execution' | 'checking' | 'review' | 'final';

@Component({
  selector: 'app-stage-step-index-table',
  templateUrl: './stage-step-index-table.component.html',
  styleUrls: ['./stage-step-index-table.component.css'],
})
export class StageStepIndexTableComponent {
  @Input() rows: StageStepIndexRow[] = [];
  @Input() mode: StageStepIndexMode = 'builder';
  @Input() emptyText = 'No steps in this stage.';
  @Input() highlightStepIndex: number | null = null;

  @Output() configure = new EventEmitter<StageStepIndexRow>();
  @Output() view = new EventEmitter<StageStepIndexRow>();
  @Output() edit = new EventEmitter<StageStepIndexRow>();
  @Output() open = new EventEmitter<StageStepIndexRow>();
  @Output() check = new EventEmitter<StageStepIndexRow>();
  @Output() review = new EventEmitter<StageStepIndexRow>();
  @Output() sendBack = new EventEmitter<StageStepIndexRow>();

  statusClass(status: string | undefined): string {
    const s = String(status || '').toLowerCase();
    if (s.includes('approved') || s === 'completed' || s === 'frozen') return 'eb-badge-ok';
    if (s.includes('correction') || s.includes('reject')) return 'eb-badge-crit';
    if (s.includes('pending') || s.includes('progress') || s === 'draft') return 'eb-badge-warn';
    return 'eb-badge-muted';
  }
}
