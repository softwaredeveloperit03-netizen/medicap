/** Master Software Customisation — single source for navigation paths */
export const SOFTWARE_CUSTOMISATION_BASE = '/master/software-customisation';

export const SC_PATHS = {
  hub: SOFTWARE_CUSTOMISATION_BASE,
  materialMasterForm: `${SOFTWARE_CUSTOMISATION_BASE}/material-master-form`,
  specificationForm: `${SOFTWARE_CUSTOMISATION_BASE}/specification-form`,
  receivingForm: `${SOFTWARE_CUSTOMISATION_BASE}/receiving-form`,
  softwareType: `${SOFTWARE_CUSTOMISATION_BASE}/software-type`,
  moaType: `${SOFTWARE_CUSTOMISATION_BASE}/moa-type`,
} as const;
