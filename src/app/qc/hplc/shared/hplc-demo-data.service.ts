import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
import { delay } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { HPLC_DEMO_MODE } from './hplc-demo-mode';
import { HPLC_UNITS } from './hplc-instruments.config';
import { REGEN_METRIC_SPECS, STANDARD_COLUMN_TYPES } from './hplc-workflow.config';

@Injectable({ providedIn: 'root' })
export class HplcDemoDataService {
  private storageKey = 'medicap_hplc_demo_store_v1';

  constructor(private api: DataAccessService) {}

  private readStore(): any {
    try {
      const raw = localStorage.getItem(this.storageKey);
      return raw ? JSON.parse(raw) : {};
    } catch {
      return {};
    }
  }

  private writeStore(store: any): void {
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(store || {}));
    } catch {
      /* ignore */
    }
  }

  /** Persist a module list so hub → form → back keeps saved entries (API + local cache). */
  persistList(key: string, rows: any[]): void {
    const list = Array.isArray(rows) ? rows : [];
    const store = this.readStore();
    store[key] = list;
    this.writeStore(store);
    if (!HPLC_DEMO_MODE) {
      this.api
        .post(
          'qc/hplc.php?type=saveHplcWorkflowEntries',
          JSON.stringify({ key, rows: list })
        )
        .subscribe({ next: () => {}, error: () => {} });
    }
  }

  /** Load list from API (when live), merge with local cache so cross-screen navigations never drop rows. */
  fetchList(key: string, fallback: any[], onDone: (rows: any[]) => void): void {
    if (HPLC_DEMO_MODE) {
      onDone(this.loadList(key, fallback));
      return;
    }
    const local = (() => {
      const store = this.readStore();
      return Array.isArray(store[key]) ? store[key] : [];
    })();

    this.api.get('qc/hplc.php?type=getHplcWorkflowEntries&key=' + encodeURIComponent(key)).subscribe({
      next: (response: any) => {
        let remote: any[] = [];
        if (Array.isArray(response)) {
          remote = response;
        } else if (typeof response === 'string') {
          try {
            const parsed = JSON.parse(response);
            if (Array.isArray(parsed)) {
              remote = parsed;
            }
          } catch {
            remote = [];
          }
        } else if (response && Array.isArray(response.rows)) {
          remote = response.rows;
        } else if (response && Array.isArray(response.data)) {
          remote = response.data;
        }

        const merged = this.mergeWorkflowRows(remote, local);
        if (merged.length) {
          this.persistListLocalOnly(key, merged);
          // Prefer server as source of truth when it has rows; still keep local-only newer rows via merge.
          if (remote.length === 0 && local.length > 0) {
            // Local has data server lost — push local up.
            this.persistList(key, merged);
          }
        }
        onDone(merged.length ? merged : fallback.slice());
      },
      error: () => onDone(local.length ? local : fallback.slice()),
    });
  }

  /** Merge remote + local workflow rows by stable business keys (order_no / column_id / id). */
  private mergeWorkflowRows(remote: any[], local: any[]): any[] {
    const out: any[] = [];
    const seen = new Set<string>();
    const keyOf = (r: any): string => {
      if (!r || typeof r !== 'object') {
        return '';
      }
      if (r.order_no) {
        return 'o:' + String(r.order_no);
      }
      if (r.recv_no) {
        return 'r:' + String(r.recv_no);
      }
      if (r.regen_no) {
        return 'g:' + String(r.regen_no);
      }
      if (r.column_id) {
        return 'c:' + String(r.column_id);
      }
      if (r.id != null) {
        return 'i:' + String(r.id);
      }
      return JSON.stringify(r);
    };
    for (const row of [...(remote || []), ...(local || [])]) {
      const k = keyOf(row);
      if (!k || seen.has(k)) {
        continue;
      }
      seen.add(k);
      out.push(row);
    }
    return out;
  }

  private persistListLocalOnly(key: string, rows: any[]): void {
    const store = this.readStore();
    store[key] = Array.isArray(rows) ? rows : [];
    this.writeStore(store);
  }

  private loadList(key: string, fallback: any[]): any[] {
    const store = this.readStore();
    return Array.isArray(store[key]) && store[key].length > 0 ? store[key] : fallback;
  }

  wrap<T>(d: T): Observable<T> {
    return of(d).pipe(delay(80));
  }

  getBanner(): string {
    return 'HPLC demonstration — column lifecycle, multi-instrument solution prep, analysis worksheets, ICH chromatography interpretation, SST & calibration with audit trail.';
  }

  getKpis() {
    return {
      columns_active: 18,
      columns_regen_due: 3,
      solutions_open: 7,
      analysis_pending: 4,
      sst_fail_month: 1,
      cal_due: 2,
      audit_today: 12,
    };
  }

  getInstruments() {
    return HPLC_UNITS;
  }

  getColumnMaster() {
    return [
      {
        id: 1,
        master_no: 'COL-M-001',
        column_type: 'C18',
        stationary_phase: 'Octadecylsilane',
        usp_code: 'L1',
        dimensions: '4.6 × 250 mm',
        particle_um: 5,
        pore_a: 100,
        end_capped: 'Yes',
        pH_range: '2–8',
        max_pressure_bar: 400,
        manufacturer: 'Agilent',
        catalog: '959961-902',
        pharmacopoeia: 'USP / Ph. Eur.',
        status: 'Approved',
      },
      {
        id: 2,
        master_no: 'COL-M-002',
        column_type: 'C8',
        stationary_phase: 'Octylsilane',
        usp_code: 'L7',
        dimensions: '4.6 × 150 mm',
        particle_um: 5,
        pore_a: 120,
        end_capped: 'Yes',
        pH_range: '2–8',
        max_pressure_bar: 400,
        manufacturer: 'Waters',
        catalog: 'WAT054275',
        pharmacopoeia: 'USP',
        status: 'Approved',
      },
      {
        id: 3,
        master_no: 'COL-M-003',
        column_type: 'NH2',
        stationary_phase: 'Aminopropyl',
        usp_code: 'L8',
        dimensions: '4.6 × 250 mm',
        particle_um: 5,
        pore_a: 100,
        end_capped: 'No',
        pH_range: '3–7.5',
        max_pressure_bar: 350,
        manufacturer: 'Phenomenex',
        catalog: '00G-4374-E0',
        pharmacopoeia: 'USP',
        status: 'Approved',
      },
    ];
  }

  getColumnOrders() {
    return this.loadList('column_orders', [
      { id: 1, order_no: 'CO-2026-014', master_ref: 'COL-M-001', qty: 2, vendor: 'Agilent', po: 'PO-QC-8821', order_date: '2026-05-10', expected: '2026-06-05', status: 'Ordered' },
      { id: 2, order_no: 'CO-2026-015', master_ref: 'COL-M-002', qty: 1, vendor: 'Waters', po: 'PO-QC-8830', order_date: '2026-05-20', expected: '2026-06-15', status: 'Partially received' },
    ]);
  }

  getColumnReceiving() {
    return this.loadList('column_receiving', [
      {
        id: 1,
        recv_no: 'CR-2026-031',
        order_no: 'CO-2026-014',
        serial: 'USRX28471A',
        column_id: 'COL-18-28471',
        master_ref: 'COL-M-001',
        recv_date: '2026-06-02',
        coa_ok: 'Yes',
        visual_ok: 'Yes',
        pressure_test_bar: 120,
        status: 'Released',
      },
    ]);
  }

  getRegenerationLog() {
    return [
      {
        id: 1,
        regen_no: 'REG-2026-008',
        column_id: 'COL-18-11205',
        column_type: 'C18',
        reason: 'Loss of efficiency — N below 1800',
        solvent_sequence: 'IPA → MeOH → Water (pH 7) → ACN',
        duration_h: 4,
        performed_by: 'QC Analyst — R. Mehta',
        verified_by: 'QC Checker — S. Patil',
        regen_date: '2026-05-28',
        status: 'Approved',
        metrics: this.sampleRegenMetrics(),
      },
    ];
  }

  sampleRegenMetrics() {
    return REGEN_METRIC_SPECS.map((s, i) => ({
      ...s,
      before: [1650, 2.4, 1.6, 285, 3.2][i],
      after: [2150, 1.3, 2.8, 195, 0.8][i],
      pass: [true, true, true, true, true][i],
    }));
  }

  getColumnInventoryLog() {
    return [
      { column_id: 'COL-18-11205', type: 'C18', injections: 842, last_used: '2026-05-30', hplc: 'HPLC-01', status: 'Active' },
      { column_id: 'COL-18-28471', type: 'C18', injections: 12, last_used: '2026-06-03', hplc: 'HPLC-02', status: 'Active' },
      { column_id: 'COL-C8-9901', type: 'C8', injections: 1205, last_used: '2026-05-15', hplc: 'HPLC-01', status: 'Regeneration due' },
    ];
  }

  getColumnDestruction() {
    return [
      { id: 1, destroy_no: 'CD-2026-004', column_id: 'COL-18-00412', reason: 'Pressure > 400 bar — packing collapse', destroy_date: '2026-04-20', witness: 'QA + QC', status: 'Closed' },
    ];
  }

  getSolutions(type?: string) {
    const all = this.loadList('solutions', [
      { id: 1, sol_no: 'SOL-MP-2026-102', type: 'Mobile Phase', name: 'ACN : Buffer 40:60 pH 3.0', hplc_id: 'HPLC-01', prep_date: '2026-06-01', expiry: '2026-06-08', status: 'Issued' },
      { id: 2, sol_no: 'SOL-DIL-2026-055', type: 'Diluent', name: '0.1% OPA in diluent', hplc_id: 'HPLC-02', prep_date: '2026-06-01', expiry: '2026-06-03', status: 'Prepared' },
      { id: 3, sol_no: 'SOL-STK-2026-018', type: 'Stock Standard', name: 'API RS 1.0 mg/mL in MeOH', hplc_id: 'HPLC-01', prep_date: '2026-05-30', expiry: '2026-06-29', status: 'Issued' },
    ]);
    return type ? all.filter((s: any) => String(s.type || '').toLowerCase().includes(type.toLowerCase())) : all;
  }

  getSolutionIssuance() {
    return [
      { iss_no: 'ISS-2026-441', sol_no: 'SOL-MP-2026-102', issued_to: 'ASSAY-2026-088', hplc_id: 'HPLC-01', qty_ml: 500, issued_by: 'A. Kumar', date: '2026-06-01' },
    ];
  }

  getAnalysisRuns() {
    return [
      {
        id: 1,
        run_no: 'AR-2026-077',
        method: 'CYT-250-ASSAY-HPLC',
        product: 'Cyclone 250 mg',
        batch: 'B26-T011',
        hplc_id: 'HPLC-01',
        column_id: 'COL-18-11205',
        status: 'Results entered',
        worksheet: 'WS-ASSAY-088.pdf',
      },
    ];
  }

  getPeakResults(runId = 1) {
    return [
      { peak: 'Solvent', rt: 1.82, area: 12400, pct: 0.4, rrt: 0.0, resolution: '—', tailing: '—' },
      { peak: 'Imp-A', rt: 4.21, area: 18200, pct: 0.6, rrt: 0.52, resolution: 3.2, tailing: 1.1 },
      { peak: 'API', rt: 8.12, area: 2890000, pct: 98.2, rrt: 1.0, resolution: 12.4, tailing: 1.05 },
      { peak: 'Imp-B', rt: 9.85, area: 24100, pct: 0.8, rrt: 1.21, resolution: 5.1, tailing: 1.15 },
    ];
  }

  getAssayCalculation() {
    return {
      std_area: 2854000,
      samp_area: 2890000,
      std_conc: 0.5,
      samp_wt: 20.1,
      dilution: 50,
      label_claim: 250,
      assay_pct: 99.4,
      rsd_pct: 0.8,
      spec: '95.0 – 105.0%',
      pass: true,
    };
  }

  getInterpretation(caseId = 1) {
    return {
      case_no: 'CHR-2026-012',
      method: 'Related substances — CYT-250',
      standard_peaks: 3,
      sample_peaks: 5,
      match_score_pct: 96,
      ich_assessment: [
        { item: 'Peak purity (PDA)', result: 'Pass', ref: 'ICH Q2' },
        { item: 'Known impurity identification', result: 'Imp-A, Imp-B match RS', ref: 'ICH Q3B' },
        { item: 'Unidentified peak > 0.10%', result: 'None', ref: 'ICH Q3B' },
        { item: 'RT alignment std vs sample', result: 'ΔRT ≤ 0.02 min', ref: 'USP <621>' },
        { item: 'Resolution critical pair', result: 'Rs ≥ 2.0', ref: 'USP <621>' },
      ],
      conclusion: 'Chromatogram acceptable for release testing. No OOS impurity profile.',
    };
  }

  getSuitabilityLog() {
    return [
      {
        id: 1,
        sst_no: 'SST-2026-201',
        method: 'CYT-250-ASSAY-HPLC',
        hplc_id: 'HPLC-01',
        date: '2026-06-01',
        rsd_pct: 0.9,
        tailing: 1.08,
        plates: 2240,
        resolution: 12.1,
        sn: 45,
        result: 'Pass',
      },
    ];
  }

  getCalibrationLog() {
    return [
      { id: 1, cal_no: 'HCAL-2026-014', hplc_id: 'HPLC-01', type: 'Full IQ/OQ/PQ', due: '2026-12-01', last: '2025-12-02', status: 'Valid' },
      { id: 2, cal_no: 'HCAL-2026-015', hplc_id: 'HPLC-03', type: 'Pump flow + Wavelength', due: '2026-06-15', last: '2025-12-15', status: 'Due' },
    ];
  }

  getCalibrationDetail() {
    return {
      pump_flow: [{ set: 0.5, found: 0.502, tol: '±2%', pass: true }, { set: 1.0, found: 0.998, tol: '±2%', pass: true }],
      wavelength: [{ set: 254, found: 254.2, tol: '±2 nm', pass: true }],
      injector_linearity: [{ vol: 5, area: 142000 }, { vol: 10, area: 284500 }, { vol: 20, area: 569200 }],
      detector_drift: '0.3% / hr — Pass',
    };
  }

  getAuditTrail() {
    return [
      { ts: '2026-06-01 09:12', module: 'Column Receiving', action: 'Create', ref: 'CR-2026-031', user: 'R. Mehta', detail: 'Serial USRX28471A released' },
      { ts: '2026-06-01 10:05', module: 'Solution Prep', action: 'Issue', ref: 'ISS-2026-441', user: 'A. Kumar', detail: '500 mL to ASSAY-2026-088' },
      { ts: '2026-06-01 11:20', module: 'SST', action: 'Approve', ref: 'SST-2026-201', user: 'S. Patil', detail: 'System suitability Pass' },
      { ts: '2026-06-01 14:00', module: 'Analysis', action: 'Upload', ref: 'AR-2026-077', user: 'R. Mehta', detail: 'Worksheet WS-ASSAY-088.pdf' },
      { ts: '2026-06-01 14:45', module: 'Interpretation', action: 'Sign', ref: 'CHR-2026-012', user: 'QA Reviewer', detail: 'ICH assessment signed' },
    ];
  }

  getStandardColumnTypes() {
    return STANDARD_COLUMN_TYPES;
  }
}
