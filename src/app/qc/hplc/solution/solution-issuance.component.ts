import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-solution-issuance',
  templateUrl: './solution-issuance.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class SolutionIssuanceComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  solutions: any[] = [];
  instruments: any[] = [];
  form: any = { hplc_id: 'HPLC-01' };

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getSolutionIssuance();
    this.solutions = this.demo.getSolutions();
    this.instruments = this.demo.getInstruments();
  }

  issue(): void {
    this.rows = [
      {
        iss_no: 'ISS-2026-' + String(440 + this.rows.length),
        date: new Date().toISOString().slice(0, 10),
        ...this.form,
      },
      ...this.rows,
    ];
    alert('Solution issued (demo).');
  }
}
