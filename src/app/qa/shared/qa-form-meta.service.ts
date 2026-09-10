import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { map, tap, catchError, switchMap } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

export interface QaFormMeta {
  form_code: string;
  form_name?: string;
  sop_no?: string;
  format_no?: string;
  effective_date?: string;
  revision_no?: string;
}

export interface QaFormIndexRow extends QaFormMeta {
  id?: number;
  route?: string;
  selected?: boolean;
}

@Injectable({ providedIn: 'root' })
export class QaFormMetaService {
  readonly api = 'qa_sop/qa_form_master.php';
  private cache = new Map<string, BehaviorSubject<QaFormMeta | null>>();
  private indexCache: QaFormIndexRow[] | null = null;

  constructor(private service: DataAccessService) {}

  getFormIndex(): Observable<QaFormIndexRow[]> {
    return this.service.get(this.api + '?type=getFormIndex').pipe(
      map((r: any) => (Array.isArray(r) ? r : []))
    );
  }

  getFormMetaByFormatNo(formatNo: string): Observable<QaFormMeta> {
    const fn = (formatNo || '').trim();
    if (!fn) {
      return of({ form_code: '', revision_no: '01', effective_date: '' });
    }
    const load = this.indexCache
      ? of(this.indexCache)
      : this.getFormIndex().pipe(tap((rows) => (this.indexCache = rows)));
    return load.pipe(
      map((rows) => {
        const hit = rows.find((r) => (r.format_no || '').trim() === fn);
        if (hit?.form_code) {
          return hit;
        }
        return { form_code: '', format_no: fn, revision_no: '01', effective_date: '' };
      }),
      switchMap((m) => (m.form_code ? this.getFormMeta(m.form_code) : of(m)))
    );
  }

  saveInitialEffectiveDate(formCode: string, effectiveDate: string, revisionNo = '01'): Observable<any> {
    return this.service.post(
      this.api + '?type=saveInitialEffectiveDate',
      JSON.stringify({ form_code: formCode, effective_date: effectiveDate, revision_no: revisionNo })
    );
  }

  saveRegistryMeta(payload: {
    form_code: string;
    sop_no?: string;
    format_no?: string;
    form_name?: string;
    revision_no?: string;
  }): Observable<any> {
    return this.service.post(this.api + '?type=saveRegistryMeta', JSON.stringify(payload)).pipe(
      tap(() => {
        const fc = payload.form_code;
        if (fc && this.cache.has(fc)) {
          this.refreshFormMeta(fc).subscribe();
        }
        this.indexCache = null;
      })
    );
  }

  getFormMeta(formCode: string): Observable<QaFormMeta> {
    if (!formCode) {
      return of({ form_code: '', revision_no: '01', effective_date: '' });
    }
    if (!this.cache.has(formCode)) {
      this.cache.set(formCode, new BehaviorSubject<QaFormMeta | null>(null));
      this.refreshFormMeta(formCode).subscribe();
    }
    const subj = this.cache.get(formCode)!;
    return subj.asObservable().pipe(
      map((m) => m || { form_code: formCode, revision_no: '01', effective_date: '' })
    );
  }

  refreshFormMeta(formCode: string): Observable<QaFormMeta> {
    return this.service.get(this.api + '?type=getFormMeta&form_code=' + encodeURIComponent(formCode)).pipe(
      tap((m: QaFormMeta) => {
        if (this.cache.has(formCode)) {
          this.cache.get(formCode)!.next(m);
        } else {
          this.cache.set(formCode, new BehaviorSubject<QaFormMeta | null>(m));
        }
      }),
      catchError(() => of({ form_code: formCode, revision_no: '01', effective_date: '' }))
    );
  }

  getNextRevision(formCode: string): Observable<string> {
    return this.service.get(this.api + '?type=getNextRevision&form_code=' + encodeURIComponent(formCode)).pipe(
      map((r: any) => r?.next_revision_no || '02')
    );
  }

  getMetaHistory(formCode?: string): Observable<any[]> {
    let url = this.api + '?type=getFormMetaHistory';
    if (formCode) {
      url += '&form_code=' + encodeURIComponent(formCode);
    }
    return this.service.get(url).pipe(map((r: any) => (Array.isArray(r) ? r : [])));
  }

  submitChangeRequest(payload: any): Observable<any> {
    return this.service.post(this.api + '?type=submitChangeRequest', JSON.stringify(payload));
  }

  getChangeRequests(status = 'pending_qa'): Observable<any[]> {
    return this.service.get(this.api + '?type=getChangeRequests&status=' + encodeURIComponent(status)).pipe(
      map((r: any) => (Array.isArray(r) ? r : []))
    );
  }

  approveChangeRequest(id: number, qaRemarks = ''): Observable<any> {
    return this.service.post(this.api + '?type=approveChangeRequest', JSON.stringify({ id, qa_remarks: qaRemarks }));
  }

  rejectChangeRequest(id: number, qaRemarks: string): Observable<any> {
    return this.service.post(this.api + '?type=rejectChangeRequest', JSON.stringify({ id, qa_remarks: qaRemarks }));
  }

  formatEffectiveDate(d?: string): string {
    if (!d) return '—';
    return String(d).substring(0, 10);
  }

  /** QMS Change Control initiation — same endpoint as QA → QMS → Change Control → New */
  submitChangeControlInitiation(formData: FormData): Observable<any> {
    return this.service.post('changecontrol.php?type=saveform', formData).pipe(
      map((r: any) => {
        if (typeof r === 'string') {
          try {
            return JSON.parse(r);
          } catch {
            return { status: r };
          }
        }
        return r;
      })
    );
  }
}
