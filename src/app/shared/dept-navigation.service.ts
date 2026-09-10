import { Injectable } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute, ActivatedRouteSnapshot, Params, Router } from '@angular/router';

@Injectable({ providedIn: 'root' })
export class DeptNavigationService {
  static readonly RETURN_PARAM = 'returnUrl';

  constructor(
    private router: Router,
    private location: Location
  ) {}

  currentUrl(): string {
    // Keep query string so hubs can pass a chained returnUrl to child screens
    // e.g. /planning/stplan?returnUrl=/planning
    const url = (this.router.url || '/').split('#')[0].trim();
    return url.startsWith('/') ? url : `/${url}`;
  }

  returnQuery(returnUrl?: string): Params | null {
    const url = (returnUrl || this.currentUrl()).trim();
    if (!url || url === '/') {
      return null;
    }
    return { [DeptNavigationService.RETURN_PARAM]: url };
  }

  returnQueryParams(returnUrl?: string): Params {
    return this.returnQuery(returnUrl) || {};
  }

  private findReturnUrl(snapshot: ActivatedRouteSnapshot): string | null {
    let node: ActivatedRouteSnapshot | null = snapshot;
    while (node) {
      const value = node.queryParamMap.get(DeptNavigationService.RETURN_PARAM);
      if (value) {
        return value;
      }
      node = node.firstChild;
    }
    node = snapshot.parent;
    while (node) {
      const value = node.queryParamMap.get(DeptNavigationService.RETURN_PARAM);
      if (value) {
        return value;
      }
      node = node.parent;
    }
    return null;
  }

  /** Prefer returnUrl query, then explicit fallback, then browser history. */
  goBack(route: ActivatedRoute, fallback = '/'): void {
    const current = this.normalizePath(this.currentUrl());
    const returnUrl = this.findReturnUrl(route.snapshot);
    const normalizedReturn = returnUrl ? this.normalizePath(returnUrl) : null;
    // Skip returnUrl when it points at the page we are already on (avoids Close no-op).
    if (returnUrl && normalizedReturn && normalizedReturn !== current) {
      const target = returnUrl.startsWith('/') ? returnUrl : `/${returnUrl}`;
      this.router.navigateByUrl(target);
      return;
    }
    const normalizedFallback = this.normalizePath(fallback);
    // Always honor fallback (including main launcher `/`). Skipping `/` used to
    // force history.back(), so closing the department hub returned to the nested
    // module the user just left instead of the main dashboard.
    if (normalizedFallback && normalizedFallback !== current) {
      this.router.navigateByUrl(fallback.startsWith('/') ? fallback : `/${fallback}`);
      return;
    }
    if (typeof window !== 'undefined' && window.history.length > 1) {
      this.location.back();
      return;
    }
    this.router.navigateByUrl(normalizedFallback || '/');
  }

  private normalizePath(url: string): string {
    const path = (url || '').split('?')[0].split('#')[0].trim();
    if (!path) {
      return '/';
    }
    return path.startsWith('/') ? path : `/${path}`;
  }
}
