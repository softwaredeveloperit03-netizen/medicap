import { Component, HostListener } from '@angular/core';
import { AppTheme } from '../app-theme.config';
import { AppThemeService } from '../app-theme.service';

@Component({
  selector: 'app-theme-picker',
  templateUrl: './theme-picker.component.html',
  styleUrls: ['./theme-picker.component.css'],
})
export class ThemePickerComponent {
  isOpen = false;

  constructor(public themeService: AppThemeService) {}

  get themes(): AppTheme[] {
    return this.themeService.themes;
  }

  get activeThemeId(): string {
    return this.themeService.activeThemeId;
  }

  get activeThemeName(): string {
    return this.themeService.activeTheme.name;
  }

  categoryLabel(category: string): string {
    const labels: Record<string, string> = {
      pharma: 'Pharma',
      corporate: 'Corporate',
      luxury: 'Luxury',
      clinical: 'Clinical',
    };
    return labels[category] || category;
  }

  togglePanel(): void {
    this.isOpen = !this.isOpen;
  }

  closePanel(): void {
    this.isOpen = false;
  }

  selectTheme(theme: AppTheme): void {
    this.themeService.setTheme(theme.id);
    this.isOpen = false;
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    this.closePanel();
  }
}
