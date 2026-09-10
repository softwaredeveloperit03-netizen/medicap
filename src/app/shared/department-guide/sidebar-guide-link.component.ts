import { Component, Input } from '@angular/core';
import { Router } from '@angular/router';

/**
 * Place at the TOP of any module left sidebar.
 * Opens the Guide Book for the current module / section.
 */
@Component({
  selector: 'app-sidebar-guide-link',
  template: `
    <a
      class="compact-nav-item compact-nav-item--guide"
      [routerLink]="['/department-guide']"
      [queryParams]="guideQueryParams"
      title="Module Guide Book"
      aria-label="Open module guide book"
    >
      <i class="fas fa-book-open" aria-hidden="true"></i>
      <span>Guide</span>
    </a>
  `,
  styles: [
    `
      :host {
        display: block;
      }
      .compact-nav-item--guide {
        margin-bottom: 6px;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #fff !important;
        border-radius: 10px;
        font-weight: 700;
      }
      .compact-nav-item--guide i,
      .compact-nav-item--guide span {
        color: #fff !important;
      }
      .compact-nav-item--guide:hover {
        filter: brightness(1.06);
      }
    `,
  ],
})
export class SidebarGuideLinkComponent {
  /** Explicit module key (e.g. preventive, sampling). Defaults from current URL. */
  @Input() moduleId = '';
  /** Current sidebar section / category name */
  @Input() section = '';
  /** Human title for the open module */
  @Input() title = '';

  constructor(private router: Router) {}

  get guideQueryParams(): Record<string, string> {
    const path = (this.router.url || '').split('?')[0];
    const parts = path.split('/').filter(Boolean);
    const autoModule = (this.moduleId || parts[parts.length - 1] || parts[1] || '').trim();
    const section = (this.section || this.title || '').trim();
    const title = (this.title || this.section || autoModule).trim();
    return {
      module: autoModule,
      section,
      title,
      returnUrl: path || '/',
    };
  }
}
