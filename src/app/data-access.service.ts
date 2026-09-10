import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { catchError, map, switchMap, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
declare let alertify;
import * as XLSX from 'xlsx';
import { environment } from '../environments/environment';
@Injectable({
  providedIn: 'root',
})
export class DataAccessService {
  [x: string]: any;

   
  apiHost = window.location.hostname;

  /** Must be declared before `domain` — `resolveApiDomain()` sets this; a later initializer was resetting it to `local`. */
  apiMode: 'local' | 'server' = (environment as any).defaultApiMode === 'local' ? 'local' : 'server';
  domain = this.resolveApiDomain();
  public url = this.domain;

  private isLiveAppHost(): boolean {
    const host = (window.location.hostname || '').toLowerCase();
    return host.includes('aurenyxgmp.com') || host.includes('medicap.');
  }

  private isLocalDevHost(): boolean {
    const host = (window.location.hostname || '').toLowerCase();
    return host === 'localhost' || host === '127.0.0.1' || host.startsWith('192.168.');
  }

  /** Same-origin proxy path used by `ng serve` (proxy.conf.json → aurenyxgmp.com). */
  private liveApiViaDevProxy(): string {
    return '/php/phpdevlop/phpmedicap/';
  }

  private resolveApiDomain(): string {
    const forceLocal =
      this.isLocalDevHost() &&
      (localStorage.getItem('force_local_php') || '').trim() === '1';

    if (forceLocal) {
      this.apiMode = 'local';
      return this.buildLocalApiUrl();
    }

    this.apiMode = 'server';
    // Always hit live PHP on aurenyxgmp (ng serve included).
    return environment.apiServerUrl;
  }

  /** Call on app bootstrap — wires Angular to live server API + Medicap client/plant defaults. */
  syncServerConnection(): void {
    // Medicap always uses live PHP/DB unless developer explicitly opts into XAMPP
    // with localStorage.force_local_php = '1'.
    const forceLocal =
      this.isLocalDevHost() &&
      (localStorage.getItem('force_local_php') || '').trim() === '1';

    if (forceLocal) {
      this.setApiMode('local');
    } else {
      this.setApiMode('server');
    }

    const clientCode = (environment as any).clientCode || 'GMP22052';
    const plantId = (environment as any).defaultPlantId || '1126';
    localStorage.setItem('client_code', clientCode);
    localStorage.removeItem('force_local_php');
    if (!localStorage.getItem('plant_id') && localStorage.getItem('login') !== 'yes') {
      localStorage.setItem('plant_id', plantId);
    }
  }

  /** Ping live PHP + DB (health.php). Returns ok when server backend and DB are reachable. */
  pingServerHealth(): Observable<{ status: string; profile?: string; db?: string }> {
    const base = environment.apiServerUrl;
    const healthUrl = base + 'health.php?plant_id=' + encodeURIComponent(
      localStorage.getItem('plant_id') || (environment as any).defaultPlantId || '1126'
    );
    return this.http.get<any>(healthUrl).pipe(
      catchError(() => of({ status: 'error' }))
    );
  }

  private buildLocalApiUrl(): string {
    const host = window.location.hostname || 'localhost';
    const protocol = window.location.protocol === 'https:' ? 'https:' : 'http:';
    const path = environment.apiLocalPath.startsWith('/')
      ? environment.apiLocalPath
      : `/${environment.apiLocalPath}`;
    return `${protocol}//${host}${path}`;
  }

  setApiMode(mode: 'local' | 'server'): void {
    localStorage.setItem('api_mode', mode);
    this.apiMode = mode;
    if (mode === 'local') {
      this.domain = this.buildLocalApiUrl();
    } else {
      this.domain = environment.apiServerUrl;
    }
    this.url = this.domain;
  }

  getApiModeLabel(): string {
    return this.apiMode === 'local' ? 'Local PHP' : 'Server PHP';
  }

  /**
   * MRP Processing /entry types must use mrp/processing.php (standalone).
   * Older builds / leftover callers still request marketing/po.php and get HTTP 500.
   */
  private rewriteRelUrl(relUrl: string): string {
    let rel = String(relUrl || '');
    const mrpProcTypes =
      /[?&]type=(getPendingProcessingPOs|getProcessingBatchFormula|getProcessingRemark|updateRemark)(?:&|$)/;
    if (rel.indexOf('marketing/po.php') === 0 && mrpProcTypes.test(rel)) {
      rel = rel.replace(/^marketing\/po\.php/, 'mrp/processing.php');
    }
    // Approve / send from Processing entry also used to hit po.php
    if (
      rel.indexOf('marketing/po.php') === 0 &&
      /[?&]type=updatePendingPOs(?:&|$)/.test(rel) &&
      /[?&]status=Work%20Order%20Processed(?:&|$)/.test(rel)
    ) {
      rel = rel.replace(/^marketing\/po\.php/, 'mrp/processing.php');
    }
    return rel;
  }

  /**
   * Base URL for a relative API path.
   * Prefer Medicap phpmedicap for marketing/po.php (helpers synced).
   * Do not reroute paths already rewritten to mrp/processing.php.
   */
  private resolveRequestBase(relUrl: string): string {
    return this.url;
  }

  /**
   * Some rows store only the filename; others store "upload/poentry/name.pdf",
   * URL-encoded "upload%2Fpoentry%2Fname.pdf", double-encoded, or mixed.
   * We always return a single basename for ../../upload/poentry/<basename>.
   */
  normalizePoEntryFileName(stored: string | null | undefined): string {
    let s = stored != null ? String(stored).trim() : '';
    if (!s || s.toUpperCase() === 'NA') {
      return '';
    }
    if (/^https?:\/\//i.test(s)) {
      try {
        s = new URL(s).pathname || s;
      } catch {
        /* keep s */
      }
    }
    s = s.replace(/\\/g, '/');
    // Turn literal percent-encoded slashes into real slashes (decode may throw on bad sequences)
    s = s.replace(/%2F/gi, '/').replace(/%5C/gi, '/');
    for (let k = 0; k < 5; k++) {
      try {
        const d = decodeURIComponent(s);
        if (d === s) {
          break;
        }
        s = d;
      } catch {
        break;
      }
      s = s.replace(/%2F/gi, '/').replace(/%5C/gi, '/');
    }
    for (let i = 0; i < 10; i++) {
      const low = s.toLowerCase();
      if (low.startsWith('upload/poentry/')) {
        s = s.slice('upload/poentry/'.length);
        continue;
      }
      if (low.startsWith('upload/po_entry/')) {
        s = s.slice('upload/po_entry/'.length);
        continue;
      }
      break;
    }
    const q = s.indexOf('?');
    if (q >= 0) {
      s = s.slice(0, q);
    }
    const h = s.indexOf('#');
    if (h >= 0) {
      s = s.slice(0, h);
    }
    const slash = s.lastIndexOf('/');
    if (slash >= 0) {
      s = s.slice(slash + 1);
    }
    return s.trim();
  }

  /** Full URL to open a PO entry upload (single segment after upload/poentry/). */
  poEntryFileUrl(stored: string | null | undefined): string {
    const base = this.normalizePoEntryFileName(stored);
    if (!base) {
      return '';
    }
    return this.url + '../../upload/poentry/' + encodeURIComponent(base);
  }

  customer_code = 'C-001';
  main_client_code = 'GMP22002';
  grades: any = [];
  observableGrade;
  current_route: string;  
  departments: any = [];
  observableDepartment;
  approver = false;
  checker = false;
  
  units: any = [];
  observableUnit;
  plants: any = [];
  observablePlant;
  materialTypes: any = [];
  observableMaterialTypes;

  isMobile = false;

  username = '';
  observableUsername;
  isSidebarOpen: boolean = false;

  private getCurrentLanguageCode(): string {
    try {
      return this.translate.currentLang || localStorage.getItem('app_language') || 'en';
    } catch {
      return 'en';
    }
  }

  constructor(private http: HttpClient, private router: Router, private translate: TranslateService) {
    this.username = localStorage.getItem('username');

    let width =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    if (width < 850) {
      this.isMobile = true;
    } else {
      this.isMobile = false;
    }

    this.observableGrade = new BehaviorSubject(this.grades);
    this.observableDepartment = new BehaviorSubject(this.departments);
    this.observableUnit = new BehaviorSubject(this.units);
    this.observableMaterialTypes = new BehaviorSubject(this.materialTypes);
    this.observablePlant = new BehaviorSubject(this.plants);

    this.observableUsername = new BehaviorSubject(this.username);
  }

  /** Route label for PHP audit log — strip query string and encode so ?scope= does not break API URLs. */
  private apiDescriptionParam(): string {
    const path = (this.router.url || '').split('?')[0].replace(/\//g, '-').replace(/^-/, '');
    this.current_route = path || '-';
    return encodeURIComponent(this.current_route);
  }

  get(url) {
    // console.log(this.router.url);
    url = this.rewriteRelUrl(url);
    url =
      this.resolveRequestBase(url) +
      url +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' + localStorage.getItem('token') + '&plant_id=' + localStorage.getItem('plant_id') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.get(url);
  }

  /** Normalize any list API response to a safe array (prod/server may return wrapped objects or errors). */
  loadList(url: string): Observable<any[]> {
    return this.get(url).pipe(
      map((r) => DataAccessService.coerceToArray(r)),
      catchError(() => of([] as any[]))
    );
  }

  /** GET as text then parse JSON array (tolerates PHP notices before JSON). */
  getJsonArray(relUrl: string): Observable<any[]> {
    const plantId =
      localStorage.getItem('plant_id') ||
      (environment as any).defaultPlantId ||
      '1126';
    const url =
      this.url +
      relUrl +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' +
      localStorage.getItem('token') +
      '&plant_id=' +
      encodeURIComponent(plantId) +
      '&lang=' +
      encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.get(url, { responseType: 'text' }).pipe(
      map((text) => DataAccessService.parseJsonArrayFromText(text))
    );
  }

  private static parseJsonArrayFromText(text: string | null | undefined): any[] {
    const trimmed = (text ?? '').trim().replace(/^\uFEFF/, '');
    if (!trimmed) {
      return [];
    }
    try {
      const parsed = JSON.parse(trimmed);
      return DataAccessService.coerceToArray(parsed);
    } catch {
      const start = trimmed.indexOf('[');
      const end = trimmed.lastIndexOf(']');
      if (start >= 0 && end > start) {
        try {
          return DataAccessService.coerceToArray(JSON.parse(trimmed.slice(start, end + 1)));
        } catch {
          return [];
        }
      }
      return [];
    }
  }

  private static coerceToArray(parsed: unknown): any[] {
    if (parsed == null) {
      return [];
    }
    if (Array.isArray(parsed)) {
      return parsed;
    }
    if (typeof parsed === 'object') {
      const obj = parsed as Record<string, unknown>;
      if (Array.isArray(obj.data)) {
        return obj.data as any[];
      }
      if (Array.isArray(obj.results)) {
        return obj.results as any[];
      }
      if (Array.isArray(obj.records)) {
        return obj.records as any[];
      }
      if (Array.isArray(obj.storages)) {
        return obj.storages as any[];
      }
      const keys = Object.keys(obj);
      if (keys.length > 0 && keys.every((k) => /^\d+$/.test(k))) {
        return keys
          .sort((a, b) => Number(a) - Number(b))
          .map((k) => obj[k])
          .filter((row) => row != null && typeof row === 'object') as any[];
      }
    }
    return [];
  }

  /** Normalize any storage-conditions API payload to a row array. */
  static normalizeStorageConditionsPayload(response: unknown): any[] {
    if (response == null) {
      return [];
    }
    let parsed: unknown = response;
    if (typeof parsed === 'string') {
      const t = parsed.trim().replace(/^\uFEFF/, '');
      if (!t) {
        return [];
      }
      try {
        parsed = JSON.parse(t);
      } catch {
        const start = t.indexOf('[');
        const end = t.lastIndexOf(']');
        if (start >= 0 && end > start) {
          try {
            parsed = JSON.parse(t.slice(start, end + 1));
          } catch {
            return [];
          }
        } else {
          return [];
        }
      }
    }
    const raw = DataAccessService.coerceToArray(parsed);
    return raw
      .map((row) => DataAccessService.normalizeStorageConditionRow(row))
      .filter((row) => row != null);
  }

  private static normalizeStorageConditionRow(row: unknown): any | null {
    if (row == null || typeof row !== 'object') {
      return null;
    }
    const src = row as Record<string, unknown>;
    const pick = (...keys: string[]): string => {
      for (const key of keys) {
        for (const k of Object.keys(src)) {
          if (k.toLowerCase() === key.toLowerCase()) {
            const v = src[k];
            if (v != null && String(v).trim() !== '') {
              return String(v).trim();
            }
          }
        }
      }
      return '';
    };
    const storage_condition = pick('storage_condition', 'condition');
    const storage_display_name = pick('storage_display_name', 'display_name');
    const temperature = pick('temperature');
    const label = storage_display_name || storage_condition || temperature;
    const value = storage_condition || storage_display_name || temperature;
    if (!label && !value && src['id'] == null) {
      return null;
    }
    return {
      ...src,
      storage_condition: value || label,
      storage_display_name: label || value,
    };
  }

  /**
   * Storage Condition master list — same sources as Master → Storage Condition screen.
   */
  fetchStorageConditionsList(): Observable<any[]> {
    const endpoints = [
      'qa/master.php?type=getStorageConditions',
      'master/master.php?type=get_storage_conditions',
      'common.php?type=get_storage_conditions',
      'common.php?type=getStorageConditions',
      'common.php?type=getStorage',
    ];
    const tryAt = (index: number): Observable<any[]> => {
      if (index >= endpoints.length) {
        return of([]);
      }
      const rel = endpoints[index];
      return this.getJsonArray(rel).pipe(
        switchMap((body) => {
          const rows = DataAccessService.normalizeStorageConditionsPayload(body);
          return rows.length > 0 ? of(rows) : tryAt(index + 1);
        }),
        catchError(() => tryAt(index + 1))
      );
    };
    return tryAt(0);
  }

  getData(url) {
    return this.http.get(url);
  }

  post(url, postData) {
    //   console.log(this.router.url);
    url = this.rewriteRelUrl(url);
    url =
      this.resolveRequestBase(url) +
      url +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' +
      localStorage.getItem('token') + '&plant_id=' + localStorage.getItem('plant_id') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.post(url, postData);
  }

  /** POST multipart/form-data; read body as text so PHP warnings do not break JSON parse. */
  postForm(url, postData) {
    url =
      this.url +
      url +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' +
      localStorage.getItem('token') + '&plant_id=' + localStorage.getItem('plant_id') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.post(url, postData, { responseType: 'text', observe: 'response' });
  }

  /**
   * POST with JSON string body and `Content-Type: application/json` (for eBMR fill API, etc.).
   */
  postJson(relUrl: string, jsonBody: string) {
    const fullUrl =
      this.url +
      relUrl +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' +
      localStorage.getItem('token') +
      '&plant_id=' +
      localStorage.getItem('plant_id') +
      '&lang=' +
      encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.post(fullUrl, jsonBody, {
      headers: new HttpHeaders({
        'Content-Type': 'application/json; charset=UTF-8',
      }),
    });
  }

  /**
   * POST JSON body; response read as **text** so PHP warnings + plain `1` do not break JSON parse.
   */
  postTextResponse(relUrl: string, body: string) {
    const fullUrl =
      this.url +
      relUrl +
      '&description=' +
      this.apiDescriptionParam() +
      '&token=' +
      localStorage.getItem('token') +
      '&plant_id=' +
      localStorage.getItem('plant_id') +
      '&lang=' +
      encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.post(fullUrl, body, {
      responseType: 'text',
      headers: new HttpHeaders({
        'Content-Type': 'application/json; charset=UTF-8',
      }),
    });
  }

  login(url, postData) {
    const token = encodeURIComponent(localStorage.getItem('token') || '');
    // Never put password/PIN in the query string (breaks special chars like # and leaks credentials).
    url =
      this.url +
      url +
      '&token=' +
      token +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http
      .post(url, postData, {
        responseType: 'text',
        headers: new HttpHeaders({ 'Content-Type': 'application/json; charset=UTF-8' }),
      })
      .pipe(
        map((text) => {
          try {
            return this.parsePhpJson(text);
          } catch {
            return {
              status: 'php_error',
              message:
                this.apiMode === 'server'
                  ? 'Live server returned an invalid response. Check connectivity.'
                  : 'PHP backend returned an invalid response. Check XAMPP Apache is running.',
            };
          }
        })
      );
  }

  /** Auth API base: prefer current url; on local host always hit live PHP. */
  private authApiBase(): string {
    const current = String(this.url || this.domain || '').trim();
    if (current) {
      return current.endsWith('/') ? current : current + '/';
    }
    return environment.apiServerUrl;
  }

  /** Idle / session re-auth (Zuma-compatible): token in query, credential in POST body only. */
  reauthSession(credential: string) {
    // Ensure local ng-serve never uses DigiSign/local-only mode for re-auth.
    if (this.isLocalDevHost() && this.apiMode !== 'server') {
      this.setApiMode('server');
    }
    const token = encodeURIComponent(localStorage.getItem('token') || '');
    const plantId = encodeURIComponent(localStorage.getItem('plant_id') || '');
    const relUrl =
      `checkLogin.php?type=reauthSession&token=${token}&plant_id=${plantId}`;
    const body = JSON.stringify({
      credential: String(credential || '').trim(),
      emp_id: localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '',
    });
    return this.http
      .post(this.authApiBase() + relUrl + '&lang=' + encodeURIComponent(this.getCurrentLanguageCode()), body, {
        responseType: 'text',
        headers: new HttpHeaders({ 'Content-Type': 'application/json; charset=UTF-8' }),
      })
      .pipe(
        map((text) => {
          try {
            return this.coerceAuthResponse(this.parsePhpJson(text));
          } catch {
            return {
              status: 'error',
              message: 'Invalid server response during re-authorisation.',
            };
          }
        })
      );
  }

  /** Verify current user's login password or authorization PIN (action / e-sign modals). */
  verifyAuthCredential(credential: string) {
    if (this.isLocalDevHost() && this.apiMode !== 'server') {
      this.setApiMode('server');
    }
    const empId = localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '';
    const plantId = localStorage.getItem('plant_id') || '';
    const cred = String(credential || '').trim();
    // Always use checkLogin verifyPassword (POST body) — DigiSign GET breaks passwords with #/@.
    const relUrl =
      `checkLogin.php?type=verifyPassword&plant_id=${encodeURIComponent(plantId)}` +
      `&lang=${encodeURIComponent(this.getCurrentLanguageCode())}`;
    const body = JSON.stringify({ emp_id: empId, password: cred, credential: cred });
    return this.http
      .post(this.authApiBase() + relUrl, body, {
        responseType: 'text',
        headers: new HttpHeaders({ 'Content-Type': 'application/json; charset=UTF-8' }),
      })
      .pipe(
        map((text) => {
          try {
            return this.coerceAuthResponse(this.parsePhpJson(text));
          } catch {
            return { status: 'error', message: 'Invalid server response.' };
          }
        })
      );
  }

  private coerceAuthResponse(raw: unknown): { status: string; message?: string; auth_method?: string } {
    let parsed: unknown = raw;
    if (typeof raw === 'string') {
      try {
        parsed = JSON.parse(raw.trim().replace(/^\uFEFF/, ''));
      } catch {
        return { status: 'error', message: 'Invalid server response.' };
      }
    }
    if (parsed && typeof parsed === 'object') {
      const o = parsed as Record<string, unknown>;
      const st = String(o.status ?? '').toLowerCase();
      if (st === 'success') {
        return {
          status: 'success',
          auth_method: o.auth_method != null ? String(o.auth_method) : undefined,
        };
      }
      if (st === 'session_expired') {
        return {
          status: 'session_expired',
          message: String(o.message || 'Session expired. Please login again.'),
        };
      }
      return {
        status: 'error',
        message: String(o.message || o.status || 'Invalid password or PIN.'),
      };
    }
    return { status: 'error', message: 'Invalid password or PIN.' };
  }

  /** Idle re-auth: verify current user password (legacy Medicap). */
  verifyPassword(password: string) {
    const emp_id = localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '';
    const plant_id = localStorage.getItem('plant_id') || '';
    const url =
      this.url +
      'checkLogin.php?type=verifyPassword' +
      '&emp_id=' + encodeURIComponent(emp_id) +
      '&password=' + encodeURIComponent(password || '') +
      '&plant_id=' + encodeURIComponent(plant_id) +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.get(url).pipe(map((raw) => this.coerceAuthResponse(raw)));
  }

  /** Invalidate session token on server (checkLogin.php?type=sessionLogout) */
  sessionLogout() {
    const token = localStorage.getItem('token') || '';
    const emp_id = localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '';
    const plant_id = localStorage.getItem('plant_id') || '';
    const url = this.url + 'checkLogin.php?type=sessionLogout';
    const params = token
      ? '&token=' + encodeURIComponent(token)
      : '&emp_id=' + encodeURIComponent(emp_id) + '&plant_id=' + encodeURIComponent(plant_id);
    return this.http.get(url + params + '&lang=' + encodeURIComponent(this.getCurrentLanguageCode()));
  }

  logout() {
    this.empRightsCache = null;
    this.sessionLogout().subscribe(() => {}, () => {});
    try {
      sessionStorage.clear();
    } catch {
      /* ignore */
    }
    localStorage.clear();
    // Hash-only navigation keeps the SPA alive with isLogin=true; force a full reload.
    try {
      const origin = window.location.origin || '';
      const path = window.location.pathname || '/';
      window.location.replace(origin + path);
    } catch {
      window.location.reload();
    }
  }

  /** GET with text body — tolerates PHP warnings before JSON. */
  getText(url: string) {
    const full = this.url + url +
      (url.indexOf('?') >= 0 ? '&' : '?') +
      'token=' + encodeURIComponent(localStorage.getItem('token') || '') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    return this.http.get(full, { responseType: 'text' });
  }

  parsePhpJson(raw: unknown): any {
    if (raw === null || raw === undefined) {
      throw new Error('Empty server response');
    }
    if (typeof raw === 'object') {
      return raw;
    }
    const text = String(raw).trim();
    if (!text) {
      throw new Error('Empty server response');
    }
    if (/^<!DOCTYPE/i.test(text) || /^<html/i.test(text)) {
      throw new Error('Server returned HTML instead of JSON.');
    }
    try {
      return JSON.parse(text);
    } catch {
      const start = text.indexOf('{');
      const end = text.lastIndexOf('}');
      if (start >= 0 && end > start) {
        return JSON.parse(text.slice(start, end + 1));
      }
      const arrStart = text.indexOf('[');
      const arrEnd = text.lastIndexOf(']');
      if (arrStart >= 0 && arrEnd > arrStart) {
        return JSON.parse(text.slice(arrStart, arrEnd + 1));
      }
      throw new Error('Could not parse server response as JSON');
    }
  }

  private parsePostResponse(raw: unknown): any {
    try {
      return this.parsePhpJson(raw);
    } catch {
      if (typeof raw === 'string' && /^success$/i.test(raw.trim())) {
        return { status: 'success' };
      }
      return { status: 'error', message: 'Invalid server response' };
    }
  }

  /** POST application/x-www-form-urlencoded body (PHP reads $_POST). */
  postUrlEncoded(url: string, fields: Record<string, string>) {
    const fullUrl =
      this.url +
      url +
      (url.indexOf('?') >= 0 ? '&' : '?') +
      'token=' + encodeURIComponent(localStorage.getItem('token') || '') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    const body = new HttpParams({ fromObject: fields }).toString();
    return this.http.post(fullUrl, body, {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
      responseType: 'text',
    }).pipe(map((text) => this.parsePostResponse(text)));
  }

  private empRightsCache: any[] | null = null;

  cacheEmpRights(rows: any[]): void {
    const list = Array.isArray(rows) ? rows : [];
    this.empRightsCache = list;
    try {
      localStorage.setItem('emp_rights_cache', JSON.stringify(list));
    } catch {
      /* ignore */
    }
  }

  private loginPlantDisplayName(p: any): string {
    const plantCode = String(p?.plant_code || '').trim();
    const plantName = String(p?.plant_name || p?.plant_full_name || p?.client_name || '').trim();
    const fallbackName = String(p?.display_name || '').trim();
    if (plantCode.toUpperCase() === 'HO' || plantName.toUpperCase() === 'HO') {
      return 'HO';
    }
    if (plantName !== '') {
      return plantName;
    }
    return fallbackName || ('Plant ' + String(p?.plant_id || ''));
  }

  private mapPlantsForLogin(rows: any[]): any[] {
    return (Array.isArray(rows) ? rows : []).map((p) => ({
      ...p,
      display_name: this.loginPlantDisplayName(p),
    }));
  }

  private parseAllPlantsCache(): any[] {
    try {
      const raw = localStorage.getItem('all_plants');
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  }

  buildClientInfoPayload(plantId: string, loginResponse?: Record<string, unknown>): any[] {
    const pid = String(plantId || localStorage.getItem('plant_id') || '');
    const plant = this.parseAllPlantsCache().find((p) => String(p.plant_id) === pid);
    const lr = loginResponse || {};
    return [
      {
        id: pid,
        plant_type: (plant?.plant_type as string) || (lr['plant_type'] as string) || 'Manufacturing',
        software_license_no:
          (lr['licence_no'] as string) ||
          plant?.licence_no ||
          localStorage.getItem('licence_no') ||
          '',
        software_type: 'PaperLess GMP Platinum (Regulated)',
        unit_name:
          plant?.display_name ||
          plant?.plant_name ||
          plant?.plant_full_name ||
          (lr['unit_name'] as string) ||
          (pid === '1126' ? 'Medicap Laboratories' : 'Medicap'),
        logo_rect:
          (lr['logo_path'] as string) ||
          plant?.logo_path ||
          localStorage.getItem('logo_path') ||
          '',
        software_version: (plant?.software_version as string) || (lr['software_version'] as string) || '00',
        version_change_summary:
          (plant?.version_change_summary as string) || (lr['version_change_summary'] as string) || '',
        version_label: 'MASTER COPY',
      },
    ];
  }

  applyClientInfoFromLogin(loginResponse?: Record<string, unknown>, plantId?: string): void {
    const pid = String(
      plantId || loginResponse?.['plant_id'] || localStorage.getItem('plant_id') || ''
    );
    if (!pid) {
      return;
    }
    localStorage.setItem('client_info', JSON.stringify(this.buildClientInfoPayload(pid, loginResponse)));
  }

  private static readonly DEV_PLANT_FALLBACK = [
    { plant_id: '1126', display_name: 'Medicap', plant_name: 'Medicap' },
  ];

  private medicapAdminPlantFallback(clientCode?: string): Observable<any[]> {
    const code = encodeURIComponent(clientCode || localStorage.getItem('client_code') || 'GMP22052');
    const adminBase =
      (environment as any).adminApiUrl ||
      'https://aurenyxgmp.com/admin/api/clients/client_data_without_token.php';
    const adminUrl = adminBase + '?type=get_client_by_id&client_code=' + code;
    return this.http.get(adminUrl).pipe(
      map((response: any) => {
        const rows = Array.isArray(response)
          ? response
          : Array.isArray(response?.plants)
            ? response.plants
            : Array.isArray(response?.data)
              ? response.data
              : [];
        const mapped = this.mapPlantsForLogin(rows);
        return mapped.length ? mapped : DataAccessService.DEV_PLANT_FALLBACK;
      }),
      catchError(() => of(DataAccessService.DEV_PLANT_FALLBACK))
    );
  }

  /** Login plant dropdown — client-scoped for Medicap; login_client for others. */
  loadLoginPlants(clientCode?: string) {
    const code = String(clientCode || 'GMP22052').trim();
    if (code === 'GMP22052') {
      return this.medicapAdminPlantFallback(code);
    }
    return this.getText('login_client.php?type=getPlants&show_corporate=1').pipe(
      map((raw) => {
        try {
          const rows = this.parsePhpJson(raw);
          if (rows && typeof rows === 'object' && !Array.isArray(rows) && rows.status === 'db_error') {
            return null;
          }
          const mapped = this.mapPlantsForLogin(Array.isArray(rows) ? rows : []);
          return mapped.length ? mapped : null;
        } catch {
          return null;
        }
      }),
      switchMap((mapped) =>
        mapped ? of(mapped) : this.medicapAdminPlantFallback(clientCode)
      ),
      catchError(() => this.medicapAdminPlantFallback(clientCode))
    );
  }

  checkPinAvailable(
    pin: string,
    empIdOrOptions?: string | { empId?: string; plantId?: string; currentPin?: string }
  ) {
    const pinEnc = encodeURIComponent(String(pin || '').trim());
    let empId = '';
    let plantId = '';
    let currentPin = '';
    if (typeof empIdOrOptions === 'string') {
      empId = empIdOrOptions;
    } else if (empIdOrOptions) {
      empId = empIdOrOptions.empId || '';
      plantId = empIdOrOptions.plantId || '';
      currentPin = empIdOrOptions.currentPin || '';
    }
    const empEnc = encodeURIComponent(empId.trim());
    const plantEnc = encodeURIComponent(plantId.trim());
    const currentEnc = encodeURIComponent(currentPin.trim());
    return this.get(
      (this.apiMode === 'server'
        ? `checkLogin.php?type=checkPinAvailable&pin=${pinEnc}&emp_id=${empEnc}&plant_id=${plantEnc}&current_pin=${currentEnc}`
        : `login_client.php?type=checkPinAvailable&pin=${pinEnc}&emp_id=${empEnc}&plant_id=${plantEnc}&current_pin=${currentEnc}`)
    );
  }

  loadClientInfoByPlantId(plantId: string) {
    const pid = encodeURIComponent(plantId || '');
    if (this.apiMode === 'server') {
      return of(this.buildClientInfoPayload(plantId));
    }
    const deployEnv = encodeURIComponent((environment as any).deployEnv || 'live');
    return this.getText(`login_client.php?type=getClientInfo&plant_id=${pid}&deploy_env=${deployEnv}`).pipe(
      map((raw) => {
        try {
          const rows = this.parsePhpJson(raw);
          if (Array.isArray(rows) && rows.length && rows[0] && Object.keys(rows[0]).length) {
            return rows;
          }
        } catch {
          /* fall through */
        }
        return this.buildClientInfoPayload(plantId);
      }),
      catchError(() => of(this.buildClientInfoPayload(plantId)))
    );
  }

  refreshClientInfo(plantId?: string, loginResponse?: Record<string, unknown>): Observable<any> {
    const pid = plantId || localStorage.getItem('plant_id') || '';
    this.applyClientInfoFromLogin(loginResponse, pid);
    return this.loadClientInfoByPlantId(pid).pipe(
      tap((res) => {
        if (Array.isArray(res) && res.length && res[0] && Object.keys(res[0]).length) {
          localStorage.setItem('client_info', JSON.stringify(res));
        }
      }),
      catchError(() => of(this.buildClientInfoPayload(pid, loginResponse)))
    );
  }

  open(url) {
    // PDF / new-tab downloads must use absolute live API URL.
    // Relative proxy path (/php/...) works for HttpClient via ng serve proxy,
    // but window.open would open against Angular host (e.g. 192.168.1.108:5555).
    const base =
      this.apiMode === 'server' ? environment.apiServerUrl : this.url;
    const token = encodeURIComponent(localStorage.getItem('token') || '');
    const plantId = encodeURIComponent(localStorage.getItem('plant_id') || '');
    window.open(
      base +
        url +
        '&token=' +
        token +
        '&plant_id=' +
        plantId +
        '&lang=' +
        encodeURIComponent(this.getCurrentLanguageCode())
    );
  }

  t(key: string): string {
    return this.translate.instant(key);
  }

  getCommonDetails() {
    let url =
      this.url +
      'common.php?type=getCommonDetails' +
      '&token=' +
      localStorage.getItem('token')+ '&plant_id=' + localStorage.getItem('plant_id') +
      '&lang=' + encodeURIComponent(this.getCurrentLanguageCode());
    this.http.get(url).subscribe((response) => {
      this.grades = response['grades'];
      this.gradeChange();

      this.departments = response['departments'];
      this.departmentChange();

      this.units = response['units'];
      this.unitChange();
 
      this.materialTypes = response['material_types'];
      this.materialTypesChange();
  
    });
    this.getPlants();
  }
  access = false;
  checkUserAccess(componentName, action) {
    console.log(componentName);
    let url =
      this.url +
      'checkLogin.php?type=UserAccess&data=' +
      localStorage.getItem('emp_id') +
      '&ComponentName=' +
      componentName +
      '&Action=' +
      action;
    this.http.get(url).subscribe((response) => {
      console.log(response);
      this.checker = response[0]['ischecker'];
      this.approver = response[0]['isapprover'];
      if (response['status'] == 'sucess') {
        if (
          response[0]['ischecker'] == 'true' &&
          response[0]['isapprover'] == 'true' &&
          response[0]['isuser'] == 'true'
        ) {
          // this.router.navigate(['/dashboard']);
          this.access = true;
        }
        if (
          response['status'] == 'sucess' &&
          response[0]['ischecker'] == 'true'
        ) {
          // this.router.navigate(['/qc/sampling/raw/checking']);
          this.access = true;
        }
        if (
          response['status'] == 'sucess' &&
          response[0]['isapprover'] == 'true'
        ) {
          // this.router.navigate(['/qc/sampling/raw/approval']);
          this.access = true;
        }
        if (
          response[0]['additional'][1]['department'] == 'Purchase' &&
          response[0]['additional'][1]['user'] == 'Yes'
        ) {
          // this.router.navigate(['/purchase/indend/raw/new']);
          this.access = true;
        } else {
          // this.router.navigate(['/']);
          this.access = false;
        }
      } else {
        this.router.navigate(['/']);
      }
    });
  }
  materialTypesChange() {
    this.observableMaterialTypes.next(this.materialTypes);
  }

 
  gradeChange() {
    this.observableGrade.next(this.grades);
  }

  departmentChange() {
    this.observableDepartment.next(this.departments);
  }

  unitChange() {
    this.observableUnit.next(this.units);
  }
 
  userChange() {
    this.observableUsername.next(this.username);
  }
  
  plantChange() {
    this.observablePlant.next(this.plants);
  }


  getPlants() {
     this.http
      .get(
        'https://aurenyxgmp.com/admin/api/clients/client_data_without_token.php?type=get_client_by_id&client_code=' +
          this.main_client_code
      )
      .subscribe((response) => {
        this.plants = response;
        this.plantChange();
      });
  }

  getStorages() {
    let url =
      this.url +
      'qa/master.php?type=getStorageConditions' +
      '&token=' +
      localStorage.getItem('token') +
      '&user_no=gmpdemo1';
    this.http.get(url).subscribe((response) => {
      this.storages = response['storages'];
      this.storageChange();
    });
  }

  numberOnly(event): boolean {
    const charCode = event.which ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      return false;
    }
    return true;
  }

  getPlantConfigFields(field_type) {
    try {
      const raw = localStorage.getItem('client_info');
      if (!raw) {
        return field_type === 'plant_id' ? localStorage.getItem('plant_id') : null;
      }
      const json_data = JSON.parse(raw);
      if (!Array.isArray(json_data) || !json_data[0]) {
        return field_type === 'plant_id' ? localStorage.getItem('plant_id') : null;
      }
      switch (field_type) {
        case 'plant_type':
          return json_data[0]['plant_type'] ?? null;
        case 'software_license_no':
          return json_data[0]['software_license_no'] ?? null;
        case 'software_type':
          return json_data[0]['software_type'] ?? null;
        case 'plant_name':
          return json_data[0]['unit_name'] ?? null;
        case 'plant_id':
          return localStorage.getItem('plant_id');
        case 'main_client_code':
          return this.main_client_code;
        case 'plant_logo':
          return json_data[0]['logo_rect'] ?? null;
        default:
          return null;
      }
    } catch {
      return field_type === 'plant_id' ? localStorage.getItem('plant_id') : null;
    }
  }

  
  // ===== Get the element by id and send as parameter and convert into pdf.
  convertToPDF(contentToConvert: any, fileName: any) {
    if (contentToConvert) {
      html2canvas(contentToConvert)
        .then((canvas) => {
          const imgWidth = 208 - 10; // PDF document width in mm minus 10mm for margins
          const pageHeight = 295 - 10; // A4 page height in mm minus 10mm for margins
          const imgHeight = (canvas.height * imgWidth) / canvas.width; // Image height based on aspect ratio
          const pageCanvas = document.createElement('canvas');
          const pageCtx = pageCanvas.getContext('2d');

          let pdf = new jsPDF('p', 'mm', 'a4'); // Create a PDF document
          let heightLeft = canvas.height;
          let position = 0;

          pageCanvas.width = canvas.width;
          pageCanvas.height = pageHeight * (canvas.width / imgWidth);

          // Loop to add each section of the canvas to the PDF
          while (heightLeft > 0) {
            pageCtx.clearRect(0, 0, pageCanvas.width, pageCanvas.height);
            pageCtx.drawImage(
              canvas,
              0,
              position,
              pageCanvas.width,
              pageCanvas.height,
              0,
              0,
              pageCanvas.width,
              pageCanvas.height
            );

            const pageDataURL = pageCanvas.toDataURL('image/png');
            pdf.addImage(pageDataURL, 'PNG', 5, 5, imgWidth, pageHeight);

            heightLeft -= pageCanvas.height;
            position += pageCanvas.height;

            if (heightLeft > 0) {
              pdf.addPage();
            }
          }

          pdf.save(`${fileName}.pdf`);
        })
        .catch((error) => {
          console.error('Error capturing element:', error);
        });
    } else {
      console.error('Element not found');
    }
  }

  public navigateToSpecificDeptHome(value) {
    value = value.toLowerCase();
    if (value == 'master') {
      this.router.navigate(['/master']);
    } else if (value == 'management') {
      this.router.navigate(['/management']);
    } else if (value == 'hr') {
      this.router.navigate(['/hr']);
    } else if (value == 'export') {
      this.router.navigate(['/export']);
    } else if (value == 'vendor-dasbhoard') {
      this.router.navigate(['/vendor-dasbhoard']);
    } else if (value == 'ehs') {
      this.router.navigate(['/ehs']);
    } else if (value == 'marketing') {
      this.router.navigate(['/marketing']);
    } else if (value == 'vendor') {
      this.router.navigate(['/vendor']);
    } else if (value == 'account') {
      this.router.navigate(['/account']);
    } else if (value == 'admin') {
      this.router.navigate(['/admin']);
    } else if (value == 'planning') {
      this.router.navigate(['/planning']);
    } else if (value == 'purchase') {
      this.router.navigate(['/purchase']);
    } else if (value == 'autopurchase') {
      this.router.navigate(['/autopurchase']);
    } else if (value == 'security') {
      this.router.navigate(['/security']);
    } else if (value == 'store') {
      this.router.navigate(['/store']);
    } else if (value == 'production') {
      this.router.navigate(['/fproduction']);
    } else if (value == 'production2') {
      this.router.navigate(['/production2']);
    } else if (value == 'packing') {
      this.router.navigate(['/packing']);
    } else if (value == 'dispatch') {
      this.router.navigate(['/dispatch']);
    } else if (value == 'it') {
      this.router.navigate(['/it']);
    } else if (value == 'quality control') {
      this.router.navigate(['/qc']);
    } else if (value == 'ipqc') {
      this.router.navigate(['/ipqc']);
    } else if (value == 'quality assurance') {
      this.router.navigate(['/qa']);
    } else if (value == 'engi-store') {
      this.router.navigate(['/engi-store']);
    } else if (value == 'engineering') {
      this.router.navigate(['/engineering']);
    } else if (value == 'calibration') {
      this.router.navigate(['/calibration']);
    } else if (value == 'rnd') {
      this.router.navigate(['/rnd']);
    } else if (value == 'purchase') {
      this.router.navigate(['/purchase']);
    } else if (value == 'sales-force') {
      this.router.navigate(['/sales-force']);
    } else if (value == 'regulatory') {
      this.router.navigate(['/regulatory']);
    } else if (value == 'vendor-dashboard') {
      this.router.navigate(['/vendor-dashboard']);
    } else if (value == 'fnd') {
      this.router.navigate(['/fnd']);
    } else if (value == 'vendor-panel') {
      if (
        localStorage.getItem('department') == 'Vendor' ||
        localStorage.getItem('department') == 'Vendor'
      ) {
        this.router.navigate(['/vendor-panel']);
      } else {
        alertify.error('Access Denied');
      }
    }
  }

  // this.validateEmail(data.form.value.email_id) =====>Like this
  validateEmail(email: string): boolean {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailPattern.test(email);
  }
  // this.validateEmail(data.form.value.name) =====>Like this
  validateName(name: string): boolean {
    const namePattern = /^[a-zA-Z\s]+$/;
    return namePattern.test(name);
  }
  exportAsExcelFile(jsonData: any[], fileName: string): void {
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(jsonData);
    const workbook: XLSX.WorkBook = {
      Sheets: { data: worksheet },
      SheetNames: ['data'],
    };
    const excelBuffer: any = XLSX.write(workbook, {
      bookType: 'xlsx',
      type: 'array',
    });
    this.saveAsExcelFile(excelBuffer, fileName);
  }

  private saveAsExcelFile(buffer: any, fileName: string): void {
    const EXCEL_TYPE =
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
    const EXCEL_EXTENSION = '.xlsx';
    const data: Blob = new Blob([buffer], { type: EXCEL_TYPE });
    const url = window.URL.createObjectURL(data);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName + EXCEL_EXTENSION;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  exportAsExcelFileLocalized(jsonData: any[], fileNameKey: string): void {
    const translatedName = this.t(fileNameKey) || fileNameKey;
    this.exportAsExcelFile(jsonData, translatedName);
  }

  localizedReportName(baseKey: string): string {
    const reportWord = this.t('reports.title');
    const generatedOn = new Date().toISOString().slice(0, 10);
    return `${this.t(baseKey)}_${reportWord}_${generatedOn}`;
  }
  exportTableToExcel(tableId: string, filename: string = 'data.xlsx'): void {
    // Get the table element
    const table = document.getElementById(tableId);
    // Convert the table to a worksheet
    const worksheet: XLSX.WorkSheet = XLSX.utils.table_to_sheet(table);
    // Create a new workbook and add the worksheet to it
    const workbook: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');
    // Generate a file and trigger a download
    XLSX.writeFile(workbook, filename);
  }
}
