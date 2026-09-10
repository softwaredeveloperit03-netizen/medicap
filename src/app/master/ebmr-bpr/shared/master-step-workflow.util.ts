import { CfrSigner } from 'src/app/shared/cfr-signature-block/cfr-signature-block.component';

export type MasterStepWorkflowStatus =
  | 'Draft'
  | 'Pending Check'
  | 'Pending Review'
  | 'Pending Approval'
  | 'Approved'
  | 'Correction Required'
  | 'Frozen';

export interface MasterSignerSnapshot {
  emp_id?: string;
  emp_name?: string;
  department?: string;
  designation?: string;
  signed_at?: string;
  signature_token?: string;
  auth_method?: string;
  auth_type?: string;
  esign_id?: number;
  reason?: string;
  meaning?: string;
}

export interface StageStepIndexRow {
  sr: number;
  stageName: string;
  stepName: string;
  details?: string;
  status?: string;
  stepRef?: any;
  stageIndex?: number;
  stepIndex?: number;
  canConfigure?: boolean;
  canView?: boolean;
  canEdit?: boolean;
  canOpen?: boolean;
  canCheck?: boolean;
  canReview?: boolean;
  canSendBack?: boolean;
}

export function normalizeMasterWorkflowStatus(v: any): MasterStepWorkflowStatus {
  const s = String(v || 'Draft').trim();
  const allowed: MasterStepWorkflowStatus[] = [
    'Draft',
    'Pending Check',
    'Pending Review',
    'Pending Approval',
    'Approved',
    'Correction Required',
    'Frozen',
  ];
  return (allowed.includes(s as MasterStepWorkflowStatus) ? s : 'Draft') as MasterStepWorkflowStatus;
}

export function stepWorkflowStatus(step: any): MasterStepWorkflowStatus {
  return normalizeMasterWorkflowStatus(step?.workflow_status);
}

export function stepCanConfigure(step: any, stageFrozen?: boolean): boolean {
  if (stageFrozen) return false;
  const st = stepWorkflowStatus(step);
  return st === 'Draft' || st === 'Correction Required';
}

export function stepCanEdit(step: any, stageFrozen?: boolean): boolean {
  if (stageFrozen) return false;
  return stepWorkflowStatus(step) === 'Correction Required';
}

export function stepIsLocked(step: any, stageFrozen?: boolean): boolean {
  if (stageFrozen) return true;
  const st = stepWorkflowStatus(step);
  return st === 'Pending Check' || st === 'Pending Review' || st === 'Pending Approval' || st === 'Approved' || st === 'Frozen';
}

export function stageAllStepsApproved(steps: any[]): boolean {
  const list = Array.isArray(steps) ? steps : [];
  if (!list.length) return false;
  return list.every((t) => {
    const st = stepWorkflowStatus(t);
    return st === 'Approved' || st === 'Frozen';
  });
}

export function stepDetailsSummary(step: any): string {
  const c = step?.config || {};
  const parts: string[] = [];
  const mf = c.master_forms || {};
  Object.keys(mf).forEach((k) => {
    if (mf[k]) parts.push(k.replace(/_/g, ' '));
  });
  if (c.step_timestamp?.enabled) parts.push('time stamp');
  if (c.yield_reconciliation?.enabled) parts.push('yield recon');
  if ((c.equipment || []).length) parts.push((c.equipment || []).length + ' equip');
  if (step?.correction_remark) parts.push('correction');
  return parts.slice(0, 4).join(' · ') || '—';
}

function signerToCfr(role: string, snap: MasterSignerSnapshot | null | undefined): CfrSigner {
  const signed = !!(snap && (snap.emp_id || snap.emp_name));
  return {
    role,
    name: snap?.emp_name || '',
    empId: snap?.emp_id || '',
    designation: snap?.designation || '',
    department: snap?.department || '',
    dateTime: snap?.signed_at || '',
    signed,
    hashToken: snap?.signature_token || '',
    authMethod: snap?.auth_method || '',
    signatureType: snap?.auth_type
      ? String(snap.auth_type).toUpperCase() === 'PIN'
        ? 'Authorization PIN'
        : 'Password'
      : snap?.auth_method || 'Electronic Signature',
    reason: snap?.reason || '',
  };
}

export function masterStepSigners(step: any): CfrSigner[] {
  return [
    signerToCfr('Prepared By', step?.prepared),
    signerToCfr('Checked By', step?.checked),
    signerToCfr('Reviewed By', step?.reviewed),
    signerToCfr('Approved By', step?.approved),
  ];
}

export function esignResultToSnapshot(sig: any, meaning?: string): MasterSignerSnapshot {
  return {
    emp_id: sig?.emp_id || '',
    emp_name: sig?.emp_name || '',
    department: sig?.department || '',
    designation: sig?.designation || '',
    signed_at: sig?.signed_at || '',
    signature_token: sig?.signature_token || '',
    auth_method: sig?.auth_method || '',
    auth_type: sig?.auth_type || '',
    esign_id: sig?.esign_id || 0,
    reason: sig?.reason || '',
    meaning: meaning || sig?.meaning || '',
  };
}

export function buildStageStepIndexRows(
  stageName: string,
  steps: any[],
  opts?: {
    stageIndex?: number;
    stageFrozen?: boolean;
    mode?: 'builder' | 'execution' | 'checking' | 'review' | 'final';
  }
): StageStepIndexRow[] {
  const mode = opts?.mode || 'builder';
  const frozen = !!opts?.stageFrozen;
  return (steps || []).map((step, i) => {
    const st = mode === 'execution' ? String(step?.status || 'Pending') : stepWorkflowStatus(step);
    const canEdit = mode === 'builder' && stepCanEdit(step, frozen);
    const canConfigure =
      mode === 'builder'
        ? stepCanConfigure(step, frozen) || canEdit
        : mode === 'execution'
          ? !['Completed', 'Approved'].includes(String(step?.status || ''))
          : false;
    const canCheck = mode === 'checking' && st === 'Pending Check';
    const canReview = mode === 'review' && st === 'Pending Review';
    return {
      sr: i + 1,
      stageName,
      stepName: step?.step_name || '—',
      details: stepDetailsSummary(step),
      status: st,
      stepRef: step,
      stageIndex: opts?.stageIndex,
      stepIndex: i,
      canConfigure,
      canView: true,
      canEdit,
      canOpen: mode === 'execution',
      canCheck,
      canReview,
      canSendBack: canCheck || canReview,
    };
  });
}
