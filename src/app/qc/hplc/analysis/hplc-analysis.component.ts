import { Component, OnInit } from '@angular/core';
import { HPLC_BASE, HPLC_DEMO_MODE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-analysis',
  templateUrl: './hplc-analysis.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class HplcAnalysisComponent implements OnInit {
  back = HPLC_BASE;
  demoMode = HPLC_DEMO_MODE;
  runs: any[] = [];
  peaks: any[] = [];
  calc: any;
  activeTab = 'worksheet';
  form: any = { hplc_id: 'HPLC-01' };
  worksheetUploaded = false;
  fileName = '';

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.runs = this.demo.getAnalysisRuns();
    this.loadResults();
  }

  onFileSelect(ev: Event): void {
    const input = ev.target as HTMLInputElement;
    if (input.files?.length) {
      this.fileName = input.files[0].name;
      this.worksheetUploaded = true;
      this.loadResults();
    }
  }

  loadResults(): void {
    this.peaks = this.demo.getPeakResults();
    this.calc = this.demo.getAssayCalculation();
  }

  saveRun(): void {
    this.runs = [
      {
        id: this.runs.length + 1,
        run_no: 'AR-2026-' + String(80 + this.runs.length),
        status: 'Worksheet uploaded',
        worksheet: this.fileName || 'manual-entry',
        ...this.form,
      },
      ...this.runs,
    ];
    alert('Analysis run created (demo).');
  }
}
