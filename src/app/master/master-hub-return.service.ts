import { Injectable } from '@angular/core';
import { Router } from '@angular/router';

/** sessionStorage key — set when opening a master from /master hub */
export const MASTER_HUB_RETURN_DEPT_KEY = 'master_hub_return_dept';

/**
 * When users open HR / Purchase / QC masters from the Master hub, Close should return
 * to `/master` with the correct department tab. Falls back to `fallbackRoute` otherwise.
 */
@Injectable({ providedIn: 'root' })
export class MasterHubReturnService {
  constructor(private router: Router) {}

  setReturnDepartment(deptId: string): void {
    try {
      if (deptId) {
        sessionStorage.setItem(MASTER_HUB_RETURN_DEPT_KEY, deptId);
      }
    } catch {
      /* private mode */
    }
  }

  peekReturnDepartment(): string | null {
    try {
      return sessionStorage.getItem(MASTER_HUB_RETURN_DEPT_KEY);
    } catch {
      return null;
    }
  }

  clearReturnDepartment(): void {
    try {
      sessionStorage.removeItem(MASTER_HUB_RETURN_DEPT_KEY);
    } catch {
      /* ignore */
    }
  }

  /**
   * If opened from Master hub, go to `/master?dept=...`. Else navigate to fallback (e.g. `/hr`, `/purchase`).
   */
  closeToMasterHubOr(fallbackRoute: string, fallbackQueryParams?: Record<string, string>): void {
    const dept = this.peekReturnDepartment();
    if (dept) {
      this.router.navigate(['/master'], { queryParams: { dept } });
    } else {
      this.router.navigate([fallbackRoute], { queryParams: fallbackQueryParams });
    }
  }
}
