import { PackingEbmrStage } from '../services/packing-ebmr-master.service';

/**
 * Default packing-line master for formulation plants: documentation, line clearance,
 * excipients / PM staging, primary & secondary packing, checks, hold/release.
 * IDs are stable strings for reference in SOP; adjust per site.
 */
export const PACKING_EBMR_PHARMA_TEMPLATE: PackingEbmrStage[] = [
  {
    stages: '1. Batch documentation & release to packing',
    Steps: [
      { id: '1', step: 'Verify BPR / packing order vs ERP — product, batch, quantity', Substeps: [] },
      { id: '2', step: 'Confirm artwork / label reference (approved code, revision, language)', Substeps: [] },
      {
        id: '3',
        step: 'Check prior batch clearance on line / area log',
        Substeps: [
          { id: '1', substep: 'Sign line clearance checklist' },
          { id: '2', substep: 'Record previous product & batch on log' },
        ],
      },
    ],
  },
  {
    stages: '2. Material staging — excipients, bulk, primary & secondary PM',
    Steps: [
      {
        id: '4',
        step: 'Issue and verify excipients / bulk against BPR (quantity, AR numbers, retest status)',
        Substeps: [
          { id: '1', substep: 'Weighing area: dual verification of labels' },
          { id: '2', substep: 'Quarantine release reference / COA available' },
        ],
      },
      { id: '5', step: 'Primary packaging materials (containers, closures, foils) — issuance vs BPR', Substeps: [] },
      { id: '6', step: 'Secondary materials (cartons, leaflets, shipper) — issuance vs BPR', Substeps: [] },
    ],
  },
  {
    stages: '3. Primary packing',
    Steps: [
      { id: '7', step: 'Set up dosing / filling / sealing per validated parameters', Substeps: [] },
      { id: '8', step: 'In-process checks: fill weight / count, seal integrity sample', Substeps: [] },
      { id: '9', step: 'Reject / rework segregation procedure if applicable', Substeps: [] },
    ],
  },
  {
    stages: '4. Serialization, coding & aggregation (if applicable)',
    Steps: [
      { id: '10', step: 'Line setup for serial codes / 2D printing — verify sample print', Substeps: [] },
      { id: '11', step: 'Aggregation to bundle / case — scan verification', Substeps: [] },
      { id: '12', step: 'Reject handling for code failures', Substeps: [] },
    ],
  },
  {
    stages: '5. Secondary packing & finished presentation',
    Steps: [
      { id: '13', step: 'Cartoning / labelling — match artwork proof', Substeps: [] },
      { id: '14', step: 'Manual pack steps (leaflet, desiccant, bundle)', Substeps: [] },
    ],
  },
  {
    stages: '6. Line clearance after packing & area hygiene',
    Steps: [
      {
        id: '15',
        step: 'Post-batch line clearance — remove residue, verify counts',
        Substeps: [{ id: '1', substep: 'Sign equipment usage / cleaning log if required' }],
      },
    ],
  },
  {
    stages: '7. QA control, hold & release to FG store',
    Steps: [
      { id: '16', step: 'Finished goods transfer to quarantine / sampled as per plan', Substeps: [] },
      { id: '17', step: 'Batch documentation completeness check for packing section', Substeps: [] },
    ],
  },
];
