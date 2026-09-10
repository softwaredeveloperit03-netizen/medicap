/** Ordered checkpoint keys for a BMR step (master forms + step-level blocks). */
export const DEFAULT_CHECKPOINT_SEQUENCE: string[] = [
  'line_clearance',
  'procedure',
  'inprocess_checks',
  'dept_checks',
  'qa_checks',
  'ipqc',
  'yield_table',
  'yield_reconciliation',
  'weighing_table',
  'equipment',
  'step_timestamp',
  'holding',
  'custom_blocks',
];

export const MASTER_FORM_CHECKPOINT_KEYS = new Set([
  'line_clearance',
  'procedure',
  'inprocess_checks',
  'dept_checks',
  'qa_checks',
  'ipqc',
  'yield_table',
  'weighing_table',
  'equipment',
]);

export const EXTRA_CHECKPOINT_LABELS: Record<string, string> = {
  step_timestamp: 'Step Time Stamp',
  yield_reconciliation: 'Yield Reconciliation',
  holding: 'Next-Stage Holding',
  custom_blocks: 'Custom Check Points',
};

const VALID_KEYS = new Set(DEFAULT_CHECKPOINT_SEQUENCE);

export function isMasterFormCheckpoint(key: string): boolean {
  return MASTER_FORM_CHECKPOINT_KEYS.has(key);
}

export function normalizeCheckpointSequence(saved: unknown): string[] {
  const out: string[] = [];
  if (Array.isArray(saved)) {
    for (const raw of saved) {
      const key = String(raw || '').trim();
      if (VALID_KEYS.has(key) && !out.includes(key)) out.push(key);
    }
  }
  return out;
}

/** Enabled checkpoints only, preserving saved order then default order for new selections. */
export function syncCheckpointSequenceForConfig(config: any): string[] {
  const enabled = DEFAULT_CHECKPOINT_SEQUENCE.filter((key) => checkpointEnabledInConfig(key, config));
  const prev = normalizeCheckpointSequence(config?.checkpoint_sequence);
  const ordered: string[] = [];
  for (const key of prev) {
    if (enabled.includes(key) && !ordered.includes(key)) ordered.push(key);
  }
  for (const key of DEFAULT_CHECKPOINT_SEQUENCE) {
    if (enabled.includes(key) && !ordered.includes(key)) ordered.push(key);
  }
  return ordered;
}

/** Order for batch execution / report (saved sequence of active blocks). */
export function resolveExecutionCheckpointSequence(template: any): string[] {
  const saved = normalizeCheckpointSequence(template?.checkpoint_sequence);
  if (saved.length) {
    return saved.filter((key) => checkpointActiveOnTemplate(key, template));
  }
  return DEFAULT_CHECKPOINT_SEQUENCE.filter((key) => checkpointActiveOnTemplate(key, template));
}

export function checkpointLabel(
  key: string,
  masterFormLabels?: Record<string, string>
): string {
  if (masterFormLabels && masterFormLabels[key]) return masterFormLabels[key];
  return EXTRA_CHECKPOINT_LABELS[key] || key;
}

/** Whether this checkpoint block is active on a saved step template. */
export function checkpointActiveOnTemplate(key: string, template: any): boolean {
  const t = template || {};
  const mf = t.master_forms || {};
  switch (key) {
    case 'line_clearance':
      return !!(t.line_clearance || []).length;
    case 'procedure':
      return !!String(t.procedure || '').trim();
    case 'inprocess_checks':
      return !!(t.inprocess_checks || []).length;
    case 'dept_checks':
      return !!(t.dept_checks || []).length;
    case 'qa_checks':
      return !!(t.qa_checks || []).length;
    case 'ipqc':
      return !!(t.ipqc || []).length;
    case 'yield_table':
      if ((t.yield?.rows || []).length || t.yield?.enabled) {
        if (!t.master_forms || t.master_forms.yield_table === undefined) return true;
        return !!t.master_forms.yield_table;
      }
      return !!mf.yield_table && !!(t.yield_table?.columns || []).length;
    case 'weighing_table':
      if (!t.master_forms || t.master_forms.weighing_table === undefined) {
        return !!(t.weighing_table?.columns || []).length;
      }
      return !!mf.weighing_table && !!(t.weighing_table?.columns || []).length;
    case 'equipment':
      if (t.equipment_point?.enabled && (!(t.equipment || []).length)) return true;
      if (!(t.equipment || []).length) return false;
      if (!t.master_forms || t.master_forms.equipment === undefined) return true;
      return !!mf.equipment;
    case 'step_timestamp':
      return !!t.step_timestamp?.enabled;
    case 'yield_reconciliation':
      return !!t.yield_reconciliation?.enabled;
    case 'holding':
      return t.holding?.required === 'Yes';
    case 'custom_blocks':
      return !!(t.custom_blocks || []).length;
    default:
      return false;
  }
}

/** Whether this checkpoint is enabled in builder step config. */
export function checkpointEnabledInConfig(key: string, config: any): boolean {
  const c = config || {};
  const mf = c.master_forms || {};
  if (isMasterFormCheckpoint(key)) return !!mf[key];
  if (key === 'step_timestamp') return !!c.step_timestamp?.enabled;
  if (key === 'yield_reconciliation') return !!c.yield_reconciliation?.enabled;
  if (key === 'holding') return c.holding?.required === 'Yes';
  if (key === 'custom_blocks') return !!(c.custom_blocks || []).length;
  return false;
}
