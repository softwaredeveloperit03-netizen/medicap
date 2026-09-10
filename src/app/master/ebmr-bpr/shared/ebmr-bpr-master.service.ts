import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EbmrBprDepartment,
  EbmrBprGetResponse,
  EbmrBprStage,
} from './ebmr-bpr.models';

@Injectable({ providedIn: 'root' })
export class EbmrBprMasterService {
  private readonly base = 'master/ebmr_bpr_master_api.php';

  constructor(private data: DataAccessService) {}

  listProducts(): Observable<EbmrBprGetResponse> {
    return this.data.get(`${this.base}?type=list_products`) as unknown as Observable<EbmrBprGetResponse>;
  }

  listMasters(department: EbmrBprDepartment): Observable<EbmrBprGetResponse> {
    return this.data.get(
      `${this.base}?type=list_masters&department=${encodeURIComponent(department)}`
    ) as unknown as Observable<EbmrBprGetResponse>;
  }

  getMaster(productCode: string, department: EbmrBprDepartment): Observable<EbmrBprGetResponse> {
    const q = encodeURIComponent(productCode);
    return this.data.get(
      `${this.base}?type=get_master_tree&product_code=${q}&department=${encodeURIComponent(department)}`
    ) as unknown as Observable<EbmrBprGetResponse>;
  }

  saveMaster(payload: {
    product_code: string;
    product_name?: string;
    department: EbmrBprDepartment;
    Stages: EbmrBprStage[];
  }): Observable<EbmrBprGetResponse> {
    return this.data.postJson(
      `${this.base}?type=save_master_tree`,
      JSON.stringify(payload)
    ) as unknown as Observable<EbmrBprGetResponse>;
  }
}
