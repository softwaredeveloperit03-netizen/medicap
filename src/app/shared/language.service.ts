import { DOCUMENT } from '@angular/common';
import { Inject, Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';

export interface AppLanguage {
  code: string;
  label: string;
  nativeLabel: string;
  isRtl?: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class LanguageService {
  private readonly storageKey = 'app_language';

  readonly supportedLanguages: AppLanguage[] = [
    { code: 'en', label: 'English', nativeLabel: 'English' },
    { code: 'ru', label: 'Russian', nativeLabel: 'Russkiy' },
    { code: 'ar', label: 'Arabic', nativeLabel: 'Al Arabiyyah', isRtl: true },
    { code: 'fr', label: 'French', nativeLabel: 'Francais' },
    { code: 'es', label: 'Spanish', nativeLabel: 'Espanol' },
    { code: 'hi', label: 'Hindi', nativeLabel: 'Hindi' },
    { code: 'vi', label: 'Vietnamese', nativeLabel: 'Tieng Viet' },
    { code: 'uz-Latn', label: 'Uzbek', nativeLabel: "O'zbekcha" },
    { code: 'fa-IR', label: 'Persian', nativeLabel: 'Farsi', isRtl: true },
    { code: 'fa-AF', label: 'Dari Persian', nativeLabel: 'Dari', isRtl: true }
  ];

  constructor(
    @Inject(TranslateService) private translate: TranslateService,
    @Inject(DOCUMENT) private document: Document
  ) {}

  init(): void {
    this.translate.addLangs(this.supportedLanguages.map((l) => l.code));
    this.translate.setDefaultLang('en');
    const lang = this.getStoredLanguage();
    this.applyLanguage(lang || 'en');
  }

  getCurrentLanguage(): string {
    return this.translate.currentLang || this.getStoredLanguage() || 'en';
  }

  applyLanguage(langCode: string): void {
    const language = this.supportedLanguages.find((l) => l.code === langCode) || this.supportedLanguages[0];
    this.translate.use(language.code);
    try {
      localStorage.setItem(this.storageKey, language.code);
    } catch {
      // ignore storage errors
    }
    this.document.documentElement.lang = language.code;
    this.document.documentElement.dir = language.isRtl ? 'rtl' : 'ltr';
    this.document.body.classList.toggle('rtl-layout', !!language.isRtl);
  }

  private getStoredLanguage(): string {
    try {
      return localStorage.getItem(this.storageKey) || '';
    } catch {
      return '';
    }
  }
}
