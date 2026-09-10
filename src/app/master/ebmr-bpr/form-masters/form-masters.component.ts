import { Component } from '@angular/core';
import { Router } from '@angular/router';

interface FormMasterTile {
  key: string;
  name: string;
  desc: string;
  icon: string;
  color: string;
  route: string;
  queryParams?: any;
}

@Component({
  selector: 'app-ebmr-form-masters',
  templateUrl: './form-masters.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './form-masters.component.css'],
})
export class FormMastersComponent {
  tiles: FormMasterTile[] = [
    {
      key: 'line_clearance',
      name: 'Line Clearance',
      desc: 'Pre-start line clearance checkpoint library — bind to BMR steps',
      icon: 'fas fa-broom',
      color: '#0891b2',
      route: 'checkpoints',
      queryParams: { category: 'line_clearance' },
    },
    {
      key: 'procedure',
      name: 'Procedure',
      desc: 'Hierarchical manufacturing procedures (1.0 · 1.1.0 · 1.1.1.0) — bind to steps',
      icon: 'fas fa-file-lines',
      color: '#0b4f6c',
      route: 'procedure-master',
    },
    {
      key: 'inprocess',
      name: 'In-Process Checks',
      desc: 'Variable-limit IPC checks (numeric, range, selection)',
      icon: 'fas fa-sliders-h',
      color: '#6366f1',
      route: 'inprocess-checks',
    },
    {
      key: 'department',
      name: 'Department Checks',
      desc: 'Production / department verification checkpoints',
      icon: 'fas fa-clipboard-list',
      color: '#0d9488',
      route: 'checkpoints',
      queryParams: { category: 'department' },
    },
    {
      key: 'qa',
      name: 'QA Checks',
      desc: 'Quality assurance review checkpoints',
      icon: 'fas fa-user-shield',
      color: '#7c3aed',
      route: 'checkpoints',
      queryParams: { category: 'qa' },
    },
    {
      key: 'ipqc',
      name: 'IPQC Specification',
      desc: 'In-process QC analysis parameters and limits',
      icon: 'fas fa-vials',
      color: '#b45309',
      route: 'ipqc-spec',
    },
    {
      key: 'work_allocation',
      name: 'Work Allocation',
      desc: 'Stage-wise activity, manpower and responsibility',
      icon: 'fas fa-people-arrows',
      color: '#be123c',
      route: 'work-allocation',
    },
    {
      key: 'yield_table',
      name: 'Yield Table',
      desc: 'Yield reconciliation table layouts — add columns & rows dynamically',
      icon: 'fas fa-chart-line',
      color: '#15803d',
      route: 'yield-table-master',
    },
    {
      key: 'weighing_table',
      name: 'Weighing Table',
      desc: 'Dispensing / weighing table layouts — add columns & rows dynamically',
      icon: 'fas fa-weight-scale',
      color: '#ca8a04',
      route: 'weighing-table-master',
    },
  ];

  constructor(private router: Router) {}

  open(tile: FormMasterTile): void {
    this.router.navigate(['/master/ebmr-bpr', tile.route], { queryParams: tile.queryParams || {} });
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
