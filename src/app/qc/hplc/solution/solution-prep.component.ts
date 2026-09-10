import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-solution-prep',
  templateUrl: './solution-prep.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class SolutionPrepComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  solutionType = 'Solution';
  formKind = 'general';
  instruments: any[] = [];
  rows: any[] = [];
  showForm = false;
  form: any = { hplc_id: 'HPLC-01' };

  constructor(public demo: HplcDemoDataService, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.route.data.subscribe((d) => {
      this.solutionType = d['solutionType'] || 'Solution';
      this.formKind = d['formKind'] || 'general';
      this.demo.fetchList('solutions', this.demo.getSolutions(), (all) => {
        this.rows = (all || []).filter((r) => !this.solutionType || r.type === this.solutionType);
      });
    });
    this.instruments = this.demo.getInstruments();
  }

  save(): void {
    const prefix = this.formKind === 'mp' ? 'SOL-MP' : this.formKind === 'dil' ? 'SOL-DIL' : this.formKind === 'stk' ? 'SOL-STK' : 'SOL';
    const row = {
      id: Date.now(),
      sol_no: prefix + '-2026-' + String(100 + this.rows.length),
      type: this.solutionType,
      prep_date: new Date().toISOString().slice(0, 10),
      status: 'Prepared',
      ...this.form,
    };
    const all = this.demo.getSolutions();
    this.demo.persistList('solutions', [row, ...all]);
    this.rows = this.demo.getSolutions(this.solutionType);
    this.showForm = false;
    alert(this.solutionType + ' saved.');
  }
}
