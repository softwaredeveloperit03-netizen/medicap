import { Injectable } from '@angular/core';
import {
  APP_THEME_STORAGE_KEY,
  APP_THEMES,
  AppTheme,
  DEFAULT_APP_THEME_ID,
  findAppTheme,
} from './app-theme.config';

@Injectable({ providedIn: 'root' })
export class AppThemeService {
  private currentTheme: AppTheme = APP_THEMES[0];

  constructor() {
    this.applySavedTheme();
  }

  get themes(): AppTheme[] {
    return APP_THEMES;
  }

  get activeTheme(): AppTheme {
    return this.currentTheme;
  }

  get activeThemeId(): string {
    return this.currentTheme.id;
  }

  applySavedTheme(): void {
    let saved = '';
    try {
      saved = localStorage.getItem(APP_THEME_STORAGE_KEY) || '';
    } catch {
      saved = '';
    }
    const theme = findAppTheme(saved) || findAppTheme(DEFAULT_APP_THEME_ID) || APP_THEMES[0];
    this.setTheme(theme.id, false);
  }

  setTheme(themeId: string, persist = true): void {
    const theme = findAppTheme(themeId) || findAppTheme(DEFAULT_APP_THEME_ID) || APP_THEMES[0];
    this.currentTheme = theme;
    if (typeof document !== 'undefined') {
      document.documentElement.setAttribute('data-app-theme', theme.id);
      document.documentElement.style.setProperty('--app-theme-accent-live', theme.accent);
    }
    if (persist) {
      try {
        localStorage.setItem(APP_THEME_STORAGE_KEY, theme.id);
      } catch {
        /* ignore storage errors */
      }
    }
  }
}
