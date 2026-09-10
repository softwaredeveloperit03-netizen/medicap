import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-column-destruction',
  templateUrl: './column-destruction.component.html',
  styleUrls: ['../../shared/hplc-shared.css'],
})
export class ColumnDestructionComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  showForm = false;
  form: any = {};

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getColumnDestruction();
  }

  save(): void {
    this.rows = [
      {
        id: this.rows.length + 1,
        destroy_no: 'CD-2026-' + String(5 + this.rows.length),
        destroy_date: new Date().toISOString().slice(0, 10),
        status: 'Pending QA',
        ...this.form,
      },
      ...this.rows,
    ];
    this.showForm = false;
    alert('Destruction record saved (demo).');
  }
}
