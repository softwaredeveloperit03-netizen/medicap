/**
 * Medicap FP → Planning → Production → Store dispensing pipeline.
 * Use offerNextStep() after each successful step so operators can continue the chain.
 */
export const MEDICAP_PRODUCTION_FLOW = {
  unitformulaApproval: '/unitformula/approval',
  masterFormula: '/planning/masterformula',
  batchFormulaLogApproval: '/planning/masterformula/batch-formula-log',
  productionDirectPlan: '/planning/production/new-formulation',
  productionDirectPlanApi: '/planning/production/new',
  batchPlanApproval: '/planning/batch-plan-approval',
  productionBatchPlanning: '/production/batch-planning',
  productionBatchPlanningApproval: '/production/batch-planning/approval',
  qaBmrPending: '/qa/bmr/pending',
  productionBatchQa: '/production/batch/qa',
  storeDispensingRaw: '/store/dispensing/raw',
  storeDispensingRawRequest: '/store/dispensing/raw/request',
} as const;

export type MedicapFlowStep = keyof typeof MEDICAP_PRODUCTION_FLOW;

/** Ordered steps for documentation / QA walkthrough */
export const MEDICAP_FLOW_SEQUENCE: MedicapFlowStep[] = [
  'unitformulaApproval',
  'masterFormula',
  'batchFormulaLogApproval',
  'productionDirectPlan',
  'batchPlanApproval',
  'productionBatchPlanning',
  'productionBatchPlanningApproval',
  'qaBmrPending',
  'productionBatchQa',
  'storeDispensingRawRequest',
];

/**
 * No-op by design: operators navigate to the next step from the menu themselves.
 * Kept so the call sites still document where each stage hands over.
 */
export function offerNextStep(
  nextRoute: string,
  nextLabel: string,
  onDecline?: () => void
): void {
  return;
}

export function productionDirectPlanRoute(plantType?: string): string {
  const pt = (plantType || '').trim();
  return pt === 'Formulation'
    ? MEDICAP_PRODUCTION_FLOW.productionDirectPlan
    : MEDICAP_PRODUCTION_FLOW.productionDirectPlanApi;
}
