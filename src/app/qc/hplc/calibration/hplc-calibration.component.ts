import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-calibration',
  templateUrl: './hplc-calibration.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class HplcCalibrationComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  detail: any;
  instruments: any[] = [];
  showForm = false;
  form: any = { hplc_id: 'HPLC-01', cal_type: 'Full IQ/OQ/PQ' };
  activeSection = 'log';

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getCalibrationLog();
    this.instruments = this.demo.getInstruments();
    this.detail = this.demo.getCalibrationDetail();
  }

  save(): void {
    this.rows = [
      {
        id: this.rows.length + 1,
        cal_no: 'HCAL-2026-' + String(20 + this.rows.length),
        last: new Date().toISOString().slice(0, 10),
        status: 'Scheduled',
        ...this.form,
      },
      ...this.rows,
    ];
    this.showForm = false;
    alert('Calibration scheduled (demo).');
  }
}
