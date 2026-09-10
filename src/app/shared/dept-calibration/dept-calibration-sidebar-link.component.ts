import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { resolveDeptBaseFromUrl, resolveDeptCalibrationRoute } from './dept-calibration.constants';

@Component({
  selector: 'app-dept-calibration-sidebar-link',
  template: `
    <a
      class="compact-nav-item dept-calibration-nav-item"
      [routerLink]="calibrationRoute"
      title="Calibration"
      aria-label="Calibration"
    >
      <i class="fas fa-crosshairs" aria-hidden="true"></i>
      <span>Calibration</span>
    </a>
  `,
  styles: [
    `
      :host {
        display: block;
        width: 100%;
      }
      a.dept-calibration-nav-item {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 8px 12px;
        border: 1px solid transparent !important;
        border-radius: 8px;
        background: transparent !important;
        color: #475569 !important;
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.3;
        text-align: left;
        text-decoration: none !important;
        cursor: pointer;
        box-sizing: border-box;
      }
      a.dept-calibration-nav-item i {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: #eef2ff !important;
        color: #1e40af !important;
        font-size: 0.82rem;
      }
      a.dept-calibration-nav-item span {
        flex: 1 1 auto;
        min-width: 0;
        color: #475569 !important;
        font-weight: 600;
      }
      a.dept-calibration-nav-item:hover {
        background: #f1f5f9 !important;
        color: #1e3a8a !important;
      }
      a.dept-calibration-nav-item:hover i {
        background: #dbeafe !important;
      }
      a.dept-calibration-nav-item:hover span {
        color: #1e3a8a !important;
      }
    `,
  ],
})
export class DeptCalibrationSidebarLinkComponent {
  constructor(private router: Router) {}

  get calibrationRoute(): string {
    const deptBase = resolveDeptBaseFromUrl(this.router.url || '/');
    return resolveDeptCalibrationRoute(deptBase);
  }
}
