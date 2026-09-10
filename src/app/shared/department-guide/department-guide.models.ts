export interface GuideField {
  name: string;
  required: boolean;
  note?: string;
}

export interface GuideModule {
  id: string;
  title: string;
  purpose: string;
  /** URL path segments that map to this module (e.g. preventive, sampling) */
  routeKeys?: string[];
  /** High-level module flow (shown as diagram steps) */
  flowSteps: string[];
  /** Numbered operating procedure */
  workflow: string[];
  precautions: string[];
  compulsoryFields: GuideField[];
  optionalFields: GuideField[];
  instructions: string[];
}

export interface DepartmentGuideBook {
  /** Canonical dashboard department name, e.g. Engineering, Quality Control */
  department: string;
  /** Alternate login labels that resolve to this book */
  aliases?: string[];
  title: string;
  overview: string;
  /** Department-level end-to-end flow */
  departmentFlow: string[];
  generalPrecautions: string[];
  modules: GuideModule[];
}

export interface GuideOpenContext {
  moduleId?: string;
  section?: string;
  title?: string;
  returnUrl?: string;
}
