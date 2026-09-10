import { Component } from '@angular/core';
import { Router } from '@angular/router';

interface EbTile {
  id: string;
  name: string;
  desc: string;
  icon: string;
  color: string;
  route: string;
  group: string;
  queryParams?: any;
  absolute?: boolean;
}

@Component({
  selector: 'app-ebmr-bpr-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './dashboard.component.css'],
})
export class DashboardComponent {
  tiles: EbTile[] = [
    // Configuration (before masters)
    { id: 'config-master', name: 'Configure Master', desc: 'BMR no, dosage form, process type & approval matrix · bind products', icon: 'fas fa-gears', color: '#0f766e', route: 'config-master', group: 'Configuration' },
    { id: 'config-stage-step', name: 'Configure Stage & Step', desc: 'Bind stages & steps to a configuration (process type + dosage form); add & view', icon: 'fas fa-diagram-project', color: '#0e7490', route: 'config-stage-step', group: 'Configuration' },
    { id: 'map-product', name: 'MAP Product', desc: 'Map stages & steps to a product from Configure Master log', icon: 'fas fa-link', color: '#0369a1', route: 'map-product', group: 'Configuration' },
    // eBMR Templates
    {
      id: 'standard-ebmr-master',
      name: 'Standard eBMR Master',
      desc: 'Generic eBMR profile library — create, version and bind standard batch manufacturing templates',
      icon: 'fas fa-book-medical',
      color: '#08607f',
      route: 'profiles',
      group: 'eBMR Templates',
      queryParams: { type: 'eBMR' },
    },
    {
      id: 'ebmr-final-template',
      name: 'eBMR Final Template',
      desc: 'Prepare & finalize BMR templates from configuration (Generic / Product) → Draft → Review → Approval',
      icon: 'fas fa-file-contract',
      color: '#0b4f6c',
      route: 'bmr-prep',
      group: 'eBMR Templates',
    },
    // Masters
    { id: 'form-masters', name: 'Form Masters', desc: 'Hub for Line Clearance, Procedure, IPC, Dept, QA, IPQC & work allocation masters', icon: 'fas fa-table-list', color: '#1d4ed8', route: 'form-masters', group: 'Masters' },
    { id: 'stage-step', name: 'Stage & Step Master', desc: 'Define manufacturing stages and their steps dynamically', icon: 'fas fa-layer-group', color: '#0b4f6c', route: 'stage-step', group: 'Masters' },
    { id: 'inprocess', name: 'In-Process Checks', desc: 'Variable-limit in-process check designer (numeric, range, selection)', icon: 'fas fa-sliders-h', color: '#6366f1', route: 'inprocess-checks', group: 'Masters' },
    { id: 'line-clearance', name: 'Line Clearance Checkpoints', desc: 'Line clearance checkpoint library', icon: 'fas fa-broom', color: '#0891b2', route: 'checkpoints', group: 'Masters', queryParams: { category: 'line_clearance' } },
    { id: 'dept-check', name: 'Department Checkpoints', desc: 'Production / department verification points', icon: 'fas fa-clipboard-list', color: '#0d9488', route: 'checkpoints', group: 'Masters', queryParams: { category: 'department' } },
    { id: 'qa-check', name: 'QA Checkpoints', desc: 'Quality assurance review checkpoints', icon: 'fas fa-user-shield', color: '#7c3aed', route: 'checkpoints', group: 'Masters', queryParams: { category: 'qa' } },
    { id: 'ipqc-spec', name: 'In-Process QC Specification', desc: 'IPQC analysis parameters and acceptance specifications', icon: 'fas fa-vials', color: '#b45309', route: 'ipqc-spec', group: 'Masters' },
    { id: 'work-alloc', name: 'Process Work Allocation (Master)', desc: 'Stage-wise activity / manpower template (master library)', icon: 'fas fa-people-arrows', color: '#be123c', route: 'work-allocation', group: 'Masters' },
    // Operational modules live under Production → eBMR
    { id: 'prod-ebmr', name: 'Production eBMR Workspace', desc: 'Start production, batch work allocation, under-production eBMR, prep, profiles & reports', icon: 'fas fa-industry', color: '#15803d', route: '/fproduction/ebmr', group: 'Production', absolute: true },
    { id: 'pack-ebpr', name: 'Packing eBPR Batch Execution', desc: 'eBPR batch execution under Packing Activity', icon: 'fas fa-boxes-packing', color: '#0f766e', route: '/packing/bpr/start', group: 'Packing', absolute: true },
  ];

  groups = ['Configuration', 'eBMR Templates', 'Masters', 'Production', 'Packing'];

  constructor(private router: Router) {}

  open(tile: EbTile & { absolute?: boolean }): void {
    if (tile.absolute || (tile.route || '').startsWith('/')) {
      this.router.navigateByUrl(tile.route);
      return;
    }
    this.router.navigate(['/master/ebmr-bpr', tile.route], { queryParams: tile.queryParams || {} });
  }

  tilesOf(group: string): EbTile[] {
    return this.tiles.filter((t) => t.group === group);
  }

  close(): void {
    this.router.navigate(['/master']);
  }
}
