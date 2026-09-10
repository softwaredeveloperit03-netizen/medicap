import { EbmrBprStage } from './ebmr-bpr.models';

/** Default production eBMR tree (Zuma-style stage / step / substep). */
export const EBMR_BPR_PRODUCTION_TEMPLATE: EbmrBprStage[] = [
  {
    stages: 'Dispensing',
    Steps: [
      {
        id: '1',
        step: 'Line Clearance',
        Substeps: [{ id: '1.1', substep: 'Verify area and equipment' }],
      },
      {
        id: '2',
        step: 'Weighing',
        Substeps: [{ id: '2.1', substep: 'Weigh and record RM' }],
      },
    ],
  },
  {
    stages: 'Granulation',
    Steps: [
      {
        id: '3',
        step: 'Dry Mixing',
        Substeps: [{ id: '3.1', substep: 'Mix as per BMR' }],
      },
    ],
  },
  {
    stages: 'Compression',
    Steps: [
      {
        id: '4',
        step: 'Tablet Compression',
        Substeps: [{ id: '4.1', substep: 'In-process checks' }],
      },
    ],
  },
];

/** Default packing BPR tree. */
export const EBMR_BPR_PACKING_TEMPLATE: EbmrBprStage[] = [
  {
    stages: 'Primary Packing',
    Steps: [
      {
        id: '1',
        step: 'Line Clearance',
        Substeps: [{ id: '1.1', substep: 'Packing line clearance' }],
      },
      {
        id: '2',
        step: 'Blister / Strip',
        Substeps: [{ id: '2.1', substep: 'Run and sample' }],
      },
    ],
  },
  {
    stages: 'Secondary Packing',
    Steps: [
      {
        id: '3',
        step: 'Cartoning',
        Substeps: [{ id: '3.1', substep: 'Carton packing record' }],
      },
    ],
  },
];

export function ebmrBprTemplateFor(dept: 'production' | 'packing'): EbmrBprStage[] {
  return JSON.parse(
    JSON.stringify(dept === 'packing' ? EBMR_BPR_PACKING_TEMPLATE : EBMR_BPR_PRODUCTION_TEMPLATE)
  );
}
