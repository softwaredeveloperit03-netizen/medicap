import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';
import { SST_PARAMETERS } from '../shared/hplc-workflow.config';

@Component({
  selector: 'app-hplc-system-suitability',
  templateUrl: './system-suitability.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class SystemSuitabilityComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  params = SST_PARAMETERS;
  showForm = false;
  form: any = { hplc_id: 'HPLC-01', result: 'Pass' };

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getSuitabilityLog();
  }

  save(): void {
    this.rows = [
      {
        id: this.rows.length + 1,
        sst_no: 'SST-2026-' + String(200 + this.rows.length),
        date: new Date().toISOString().slice(0, 10),
        ...this.form,
      },
      ...this.rows,
    ];
    this.showForm = false;
    alert('SST record saved (demo).');
  }
}
