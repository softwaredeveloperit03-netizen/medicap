import { Component, HostListener } from '@angular/core';
import { AppTheme } from '../app-theme.config';
import { AppThemeService } from '../app-theme.service';

@Component({
  selector: 'app-theme-radial-fab',
  templateUrl: './theme-radial-fab.component.html',
  styleUrls: ['./theme-radial-fab.component.css'],
})
export class ThemeRadialFabComponent {
  isOpen = false;

  constructor(public themeService: AppThemeService) {}

  get themes(): AppTheme[] {
    return this.themeService.themes;
  }

  get themeCount(): number {
    return this.themes.length;
  }

  get activeThemeId(): string {
    return this.themeService.activeThemeId;
  }

  /**
   * Half-circle above-left of the FAB (bottom-right corner).
   * Start slightly off dead-center so the first swatch is not
   * stacked on top of the palette button.
   * CSS: 0deg = up; negative = counterclockwise toward left.
   */
  swatchAngle(index: number): string {
    const n = Math.max(this.themeCount - 1, 1);
    // -12° (up-left) → -105° (left) — clear of FAB and screen edge
    const angle = -12 - (index / n) * 93;
    return `${angle}deg`;
  }

  toggle(): void {
    this.isOpen = !this.isOpen;
  }

  close(): void {
    this.isOpen = false;
  }

  selectTheme(theme: AppTheme, event: Event): void {
    event.stopPropagation();
    this.themeService.setTheme(theme.id);
    this.isOpen = false;
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    this.close();
  }
}
