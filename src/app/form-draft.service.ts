import { Injectable } from '@angular/core';

const STORAGE_PREFIX = 'form_draft_';
const DEFAULT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

export interface DraftMeta {
  formId: string;
  savedAt: string;
  value: object;
}

@Injectable({
  providedIn: 'root'
})
export class FormDraftService {

  /**
   * Build storage key: form_draft_{userId}_{plantId?}_{formId}
   */
  getKey(formId: string, userId?: string, plantId?: string): string {
    const user = (userId || '').trim() || 'anonymous';
    const parts = [STORAGE_PREFIX, user, formId];
    if (plantId && String(plantId).trim()) {
      parts.splice(2, 0, String(plantId).trim());
    }
    return parts.join('_');
  }

  saveDraft(key: string, value: object, formId?: string): void {
    try {
      const payload: DraftMeta = {
        formId: formId || key,
        savedAt: new Date().toISOString(),
        value: JSON.parse(JSON.stringify(value))
      };
      localStorage.setItem(key, JSON.stringify(payload));
    } catch (e) {
      console.warn('FormDraftService: save failed', e);
    }
  }

  getDraft(key: string, maxAgeMs: number = DEFAULT_MAX_AGE_MS): DraftMeta | null {
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return null;
      const draft: DraftMeta = JSON.parse(raw);
      if (!draft || typeof draft.value !== 'object') return null;
      if (maxAgeMs > 0 && draft.savedAt) {
        const age = Date.now() - new Date(draft.savedAt).getTime();
        if (age > maxAgeMs) {
          this.clearDraft(key);
          return null;
        }
      }
      return draft;
    } catch {
      return null;
    }
  }

  clearDraft(key: string): void {
    try {
      localStorage.removeItem(key);
    } catch { }
  }

  hasDraft(key: string): boolean {
    return this.getDraft(key) !== null;
  }
}
