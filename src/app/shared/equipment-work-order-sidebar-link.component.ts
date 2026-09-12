import { Component } from '@angular/core';

@Component({
  selector: 'app-equipment-work-order-sidebar-link',
  template: `
    <a
      class="compact-nav-item equipment-work-order-nav-item"
      routerLink="/equipment-work-order"
      title="Equipment & Facility Work Order"
      aria-label="Equipment & Facility Work Order"
    >
      <i class="fas fa-clipboard-list" aria-hidden="true"></i>
      <span>Work Order</span>
    </a>
  `,
  styles: [
    `
      :host {
        display: block;
        width: 100%;
      }
      a.equipment-work-order-nav-item {
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
      a.equipment-work-order-nav-item i {
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
      a.equipment-work-order-nav-item span {
        flex: 1 1 auto;
        min-width: 0;
        color: #475569 !important;
        font-weight: 600;
      }
      a.equipment-work-order-nav-item:hover {
        background: #f1f5f9 !important;
        color: #1e3a8a !important;
      }
      a.equipment-work-order-nav-item:hover i {
        background: #dbeafe !important;
      }
      a.equipment-work-order-nav-item:hover span {
        color: #1e3a8a !important;
      }
    `,
  ],
})
export class EquipmentWorkOrderSidebarLinkComponent {}
