export type EbmrBprDepartment = 'production' | 'packing';

export interface EbmrBprSubstep {
  id: string;
  substep: string;
}

export interface EbmrBprStep {
  id: string;
  step: string;
  Substeps: EbmrBprSubstep[];
}

export interface EbmrBprStage {
  stages: string;
  Steps: EbmrBprStep[];
}

export interface EbmrBprMasterListRow {
  product_code: string;
  product_name: string | null;
  department: EbmrBprDepartment;
  revision: number;
  status: string;
  updated_at: string;
  updated_by_emp_id: string | null;
}

export interface EbmrBprProductRow {
  product_code: string;
  product_name: string;
  dosage_form?: string;
  status?: string;
}

export interface EbmrBprGetResponse {
  status: string;
  msg?: string;
  product_code?: string;
  product_name?: string | null;
  department?: EbmrBprDepartment;
  revision?: number;
  status_flag?: string;
  updated_at?: string;
  updated_by_emp_id?: string | null;
  Stages: EbmrBprStage[];
  masters?: EbmrBprMasterListRow[];
  products?: EbmrBprProductRow[];
}
