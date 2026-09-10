import { Injectable } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Observable } from 'rxjs';

export interface PackingEbmrSubstep {
  id: string;
  substep: string;
}

export interface PackingEbmrStep {
  id: string;
  step: string;
  Substeps: PackingEbmrSubstep[];
}

export interface PackingEbmrStage {
  stages: string;
  Steps: PackingEbmrStep[];
}

export interface PackingEbmrMasterListRow {
  product_code: string;
  product_name: string | null;
  revision: number;
  updated_at: string;
  updated_by_emp_id: string | null;
}

export interface PackingEbmrGetResponse {
  status: string;
  msg?: string;
  product_code?: string;
  product_name?: string | null;
  revision?: number;
  updated_at?: string;
  updated_by_emp_id?: string | null;
  Stages: PackingEbmrStage[];
}

@Injectable({ providedIn: 'root' })
export class PackingEbmrMasterService {
  constructor(private data: DataAccessService) {}

  listMasters(): Observable<PackingEbmrGetResponse & { masters?: PackingEbmrMasterListRow[] }> {
    return this.data.get(
      'bmr/packing_ebmr_master_api.php?type=list_masters'
    ) as unknown as Observable<PackingEbmrGetResponse & { masters?: PackingEbmrMasterListRow[] }>;
  }

  getMaster(productCode: string): Observable<PackingEbmrGetResponse> {
    const q = encodeURIComponent(productCode);
    return this.data.get(
      'bmr/packing_ebmr_master_api.php?type=get_master_tree&product_code=' + q
    ) as unknown as Observable<PackingEbmrGetResponse>;
  }

  saveMaster(payload: {
    product_code: string;
    product_name?: string;
    Stages: PackingEbmrStage[];
  }): Observable<PackingEbmrGetResponse> {
    return this.data.postJson(
      'bmr/packing_ebmr_master_api.php?type=save_master_tree',
      JSON.stringify(payload)
    ) as unknown as Observable<PackingEbmrGetResponse>;
  }
}
