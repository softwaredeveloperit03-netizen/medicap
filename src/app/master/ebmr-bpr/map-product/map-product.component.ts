import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import { normalizeApplicableFlag } from '../shared/yield-reconciliation.util';

declare let alertify: any;

interface StepRow {
  step_seq?: number;
  step_name: string;
  ipqc_testing: string;
  sampling_by?: string;
  time_stamp?: string;
  yield_reconciliation?: string;
  equipment_point?: string;
  instruction?: string;
  remark?: string;
}

interface StageGroup {
  stage_name: string;
  steps: StepRow[];
  draftStep: StepRow;
}

interface PickGroup {
  stage_name: string;
  steps: {
    step_name: string;
    ipqc_testing: string;
    sampling_by?: string;
    time_stamp?: string;
    yield_reconciliation?: string;
    equipment_point?: string;
    instruction?: string;
    step_seq?: number;
    _checked?: boolean;
  }[];
  _checked?: boolean;
}

@Component({
  selector: 'app-ebmr-map-product',
  templateUrl: './map-product.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', '../config-stage-step/config-stage-step.component.css', './map-product.component.css'],
})
export class MapProductComponent implements OnInit {
  loading = false;
  saving = false;

  configs: any[] = [];
  configSearch = '';
  counts: { [k: string]: { stage_count: number; step_count: number } } = {};
  productCounts: { [k: string]: { stage_count: number; step_count: number } } = {};

  /** product_code selected per config row in the log table */
  selectedProductCode: { [configId: number]: string } = {};
  /** dropdown options per config */
  productsByConfig: { [configId: number]: any[] } = {};

  selectedConfig: any = null;
  selectedProduct: any = null;
  formOpen = false;

  stageSuggestions: string[] = [];
  stages: StageGroup[] = [];
  newStageName = '';

  /** selectable catalogue: config-level stages + stage master */
  pickGroups: PickGroup[] = [];

  viewOpen = false;
  viewRows: any[] = [];
  viewConfig: any = null;
  viewProduct: any = null;

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.loadConfigs();
  }

  blankStep(): StepRow {
    return {
      step_name: '',
      ipqc_testing: 'No',
      sampling_by: 'Production',
      time_stamp: 'Not Applicable',
      yield_reconciliation: 'Not Applicable',
      equipment_point: 'Not Applicable',
      step_seq: undefined,
      instruction: '',
      remark: '',
    };
  }

  normalizeTimeStamp(v: any): string {
    return normalizeApplicableFlag(v);
  }

  normalizeYieldRecon(v: any): string {
    return normalizeApplicableFlag(v);
  }

  normalizeEquipmentPoint(v: any): string {
    return normalizeApplicableFlag(v);
  }

  get viewYieldSteps(): any[] {
    return (this.viewRows || []).filter(
      (r) => (r.step_name || '').trim() && this.normalizeYieldRecon(r.yield_reconciliation) === 'Applicable'
    );
  }

  get viewEquipmentSteps(): any[] {
    return (this.viewRows || []).filter(
      (r) => (r.step_name || '').trim() && this.normalizeEquipmentPoint(r.equipment_point) === 'Applicable'
    );
  }

  blankStage(name = ''): StageGroup {
    return { stage_name: name, steps: [], draftStep: this.blankStep() };
  }

  loadConfigs(): void {
    this.loading = true;
    this.service.get('master/ebmr_bpr.php?type=getConfigs').subscribe({
      next: (r: any) => {
        this.configs = Array.isArray(r) ? r : [];
        this.loading = false;
        this.loadCounts();
        this.configs.forEach((c) => this.ensureProductsLoaded(c));
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load configurations');
      },
    });
  }

  loadCounts(): void {
    this.service.get('master/ebmr_bpr.php?type=getConfigStageStepCounts').subscribe({
      next: (r: any) => {
        this.counts = r && typeof r === 'object' ? r : {};
      },
      error: () => {
        this.counts = {};
      },
    });
    this.service.get('master/ebmr_bpr.php?type=getConfigProductStageCounts').subscribe({
      next: (r: any) => {
        this.productCounts = r && typeof r === 'object' ? r : {};
      },
      error: () => {
        this.productCounts = {};
      },
    });
  }

  get filteredConfigs(): any[] {
    const q = (this.configSearch || '').trim().toLowerCase();
    if (!q) {
      return this.configs;
    }
    return this.configs.filter((c) =>
      [c.bmr_no, c.process_type, c.dosage_form, c.title].join(' ').toLowerCase().includes(q)
    );
  }

  configStageCount(c: any): number {
    return this.counts[String(c.id)]?.stage_count || 0;
  }

  productMapCount(c: any): number {
    const code = this.selectedProductCode[c.id];
    if (!code) {
      return 0;
    }
    return this.productCounts[String(c.id) + '|' + code]?.stage_count || 0;
  }

  productMapStepCount(c: any): number {
    const code = this.selectedProductCode[c.id];
    if (!code) {
      return 0;
    }
    return this.productCounts[String(c.id) + '|' + code]?.step_count || 0;
  }

  ensureProductsLoaded(config: any): void {
    if (!config?.id || this.productsByConfig[config.id]) {
      return;
    }
    this.service.get('master/ebmr_bpr.php?type=getConfigProducts&id=' + config.id).subscribe({
      next: (bound: any) => {
        const list = Array.isArray(bound) ? bound : [];
        if (list.length) {
          this.productsByConfig[config.id] = list;
          if (!this.selectedProductCode[config.id] && list[0]?.product_code) {
            this.selectedProductCode[config.id] = list[0].product_code;
          }
        } else {
          this.loadDosageProducts(config);
        }
      },
      error: () => this.loadDosageProducts(config),
    });
  }

  loadDosageProducts(config: any): void {
    const df = encodeURIComponent(config.dosage_form || '');
    this.service.get('master/ebmr_bpr.php?type=getProductsForBinding&dosage_form=' + df).subscribe({
      next: (r: any) => {
        const list = (Array.isArray(r) ? r : []).map((p: any) => ({
          product_code: p.product_code,
          product_name: p.product_name,
          product_type: 'Generic',
        }));
        this.productsByConfig[config.id] = list;
        if (!this.selectedProductCode[config.id] && list[0]?.product_code) {
          this.selectedProductCode[config.id] = list[0].product_code;
        }
      },
      error: () => {
        this.productsByConfig[config.id] = [];
      },
    });
  }

  onProductChange(config: any, productCode: string): void {
    this.selectedProductCode[config.id] = productCode;
  }

  getProductLabel(config: any): string {
    const code = this.selectedProductCode[config.id];
    const list = this.productsByConfig[config.id] || [];
    const p = list.find((x: any) => x.product_code === code);
    return p ? p.product_name : '';
  }

  openMap(config: any): void {
    const code = this.selectedProductCode[config.id];
    if (!code) {
      alertify.error('Select a product first.');
      return;
    }
    const list = this.productsByConfig[config.id] || [];
    const product = list.find((p: any) => p.product_code === code);
    if (!product) {
      alertify.error('Selected product not found.');
      return;
    }

    this.selectedConfig = config;
    this.selectedProduct = product;
    this.formOpen = true;
    this.stages = [];
    this.newStageName = '';
    this.pickGroups = [];
    this.stageSuggestions = [];

    this.loadBinding();
    this.loadPickCatalogue();

    if (config.dosage_form) {
      this.service
        .get('master/ebmr_bpr.php?type=getStages&dosage_form=' + encodeURIComponent(config.dosage_form))
        .subscribe({
          next: (r: any) => {
            this.stageSuggestions = Array.isArray(r) ? r.map((s: any) => s.stage_name).filter(Boolean) : [];
          },
        });
    }
  }

  closeForm(): void {
    this.formOpen = false;
    this.selectedConfig = null;
    this.selectedProduct = null;
    this.stages = [];
    this.pickGroups = [];
  }

  loadBinding(): void {
    if (!this.selectedConfig || !this.selectedProduct) {
      return;
    }
    const url =
      'master/ebmr_bpr.php?type=getConfigStageStep&config_id=' +
      this.selectedConfig.id +
      '&product_code=' +
      encodeURIComponent(this.selectedProduct.product_code);
    this.service.get(url).subscribe((r: any) => {
      this.stages = this.rowsToStageGroups(Array.isArray(r) ? r : []);
    });
  }

  loadPickCatalogue(): void {
    if (!this.selectedConfig) {
      return;
    }
    const groups: PickGroup[] = [];
    const addStep = (stageName: string, step: any) => {
      let g = groups.find((x) => x.stage_name === stageName);
      if (!g) {
        g = { stage_name: stageName, steps: [] };
        groups.push(g);
      }
      if ((step.step_name || '').trim() && !g.steps.some((s) => s.step_name === step.step_name)) {
        g.steps.push({
          step_name: step.step_name,
          ipqc_testing: step.ipqc_testing || 'No',
          sampling_by: step.sampling_by || 'Production',
          time_stamp: this.normalizeTimeStamp(step.time_stamp),
          yield_reconciliation: this.normalizeYieldRecon(step.yield_reconciliation),
          equipment_point: this.normalizeEquipmentPoint(step.equipment_point),
          instruction: step.instruction || '',
          step_seq: step.step_seq,
        });
      }
    };

    // Config-level Configure Stage & Step (template)
    this.service
      .get(
        'master/ebmr_bpr.php?type=getConfigStageStep&config_id=' +
          this.selectedConfig.id +
          '&scope=config'
      )
      .subscribe((cfgRows: any) => {
        (Array.isArray(cfgRows) ? cfgRows : []).forEach((d: any) => addStep(d.stage_name, d));

        // Stage & Step master for dosage form
        this.service
          .get(
            'master/ebmr_bpr.php?type=getStages&dosage_form=' +
              encodeURIComponent(this.selectedConfig.dosage_form || '')
          )
          .subscribe((stages: any) => {
            const stageList = Array.isArray(stages) ? stages : [];
            if (!stageList.length) {
              this.pickGroups = groups.filter((g) => g.steps.length || g.stage_name);
              return;
            }
            let pending = stageList.length;
            stageList.forEach((st: any) => {
              this.service.get('master/ebmr_bpr.php?type=getSteps&stage_id=' + st.id).subscribe({
                next: (steps: any) => {
                  (Array.isArray(steps) ? steps : []).forEach((stp: any) =>
                    addStep(st.stage_name || stp.stage_name, {
                      step_name: stp.step_name,
                      ipqc_testing: 'No',
                      time_stamp: 'Not Applicable',
                      yield_reconciliation: 'Not Applicable',
                      equipment_point: 'Not Applicable',
                      instruction: stp.description || '',
                      step_seq: stp.seq_no,
                    })
                  );
                  pending--;
                  if (pending <= 0) {
                    this.pickGroups = groups.filter((g) => g.stage_name);
                  }
                },
                error: () => {
                  pending--;
                  if (pending <= 0) {
                    this.pickGroups = groups.filter((g) => g.stage_name);
                  }
                },
              });
            });
          });
      });
  }

  rowsToStageGroups(data: any[]): StageGroup[] {
    const groups: StageGroup[] = [];
    for (const d of data) {
      const stageName = d.stage_name || '';
      let g = groups.find((x) => x.stage_name === stageName);
      if (!g) {
        g = this.blankStage(stageName);
        groups.push(g);
      }
      if ((d.step_name || '').trim() !== '') {
        g.steps.push({
          step_seq: d.step_seq != null ? Number(d.step_seq) : undefined,
          step_name: d.step_name || '',
          ipqc_testing: d.ipqc_testing || 'No',
          sampling_by: d.sampling_by || 'Production',
          time_stamp: this.normalizeTimeStamp(d.time_stamp),
          yield_reconciliation: this.normalizeYieldRecon(d.yield_reconciliation),
          equipment_point: this.normalizeEquipmentPoint(d.equipment_point),
          instruction: d.instruction || '',
          remark: d.remark || '',
        });
      }
    }
    return groups;
  }

  importPicked(): void {
    let added = 0;
    for (const pg of this.pickGroups) {
      const pickedSteps = pg.steps.filter((s) => s._checked);
      if (!pg._checked && !pickedSteps.length) {
        continue;
      }
      let g = this.stages.find((x) => x.stage_name.toLowerCase() === pg.stage_name.toLowerCase());
      if (!g) {
        g = this.blankStage(pg.stage_name);
        this.stages.push(g);
      }
      const stepsToAdd = pickedSteps.length ? pickedSteps : pg._checked ? pg.steps : [];
      for (const st of stepsToAdd) {
        if (!g.steps.some((x) => x.step_name.toLowerCase() === st.step_name.toLowerCase())) {
          g.steps.push({
            step_name: st.step_name,
            step_seq: st.step_seq,
            ipqc_testing: st.ipqc_testing || 'No',
            sampling_by: st.sampling_by || 'Production',
            time_stamp: this.normalizeTimeStamp(st.time_stamp),
            yield_reconciliation: this.normalizeYieldRecon(st.yield_reconciliation),
            equipment_point: this.normalizeEquipmentPoint(st.equipment_point),
            instruction: st.instruction || '',
          });
          added++;
        }
      }
      pg._checked = false;
      pg.steps.forEach((s) => (s._checked = false));
    }
    if (added) {
      alertify.success(added + ' step(s) added to mapping');
    } else {
      alertify.warning('Select at least one stage or step to import.');
    }
  }

  addStage(): void {
    const name = (this.newStageName || '').trim();
    if (!name) {
      alertify.error('Enter a stage name.');
      return;
    }
    if (this.stages.some((s) => s.stage_name.toLowerCase() === name.toLowerCase())) {
      alertify.error('That stage already exists.');
      return;
    }
    this.stages.push(this.blankStage(name));
    this.newStageName = '';
  }

  removeStage(i: number): void {
    this.stages.splice(i, 1);
  }

  addStep(stage: StageGroup): void {
    const name = (stage.draftStep.step_name || '').trim();
    if (!name) {
      alertify.error('Enter a step name.');
      return;
    }
    stage.steps.push({
      step_name: name,
      step_seq: stage.draftStep.step_seq,
      ipqc_testing: stage.draftStep.ipqc_testing || 'No',
      sampling_by: stage.draftStep.ipqc_testing === 'Yes' ? (stage.draftStep.sampling_by || 'Production') : 'Production',
      time_stamp: this.normalizeTimeStamp(stage.draftStep.time_stamp),
      yield_reconciliation: this.normalizeYieldRecon(stage.draftStep.yield_reconciliation),
      equipment_point: this.normalizeEquipmentPoint(stage.draftStep.equipment_point),
      instruction: (stage.draftStep.instruction || '').trim(),
      remark: (stage.draftStep.remark || '').trim(),
    });
    stage.draftStep = this.blankStep();
  }

  removeStep(stage: StageGroup, j: number): void {
    stage.steps.splice(j, 1);
  }

  get totalSteps(): number {
    return this.stages.reduce((n, s) => n + s.steps.length, 0);
  }

  private flattenRows(): any[] {
    const rows: any[] = [];
    this.stages.forEach((s, si) => {
      const stageSeq = si + 1;
      if (!s.steps.length) {
        rows.push({
          stage_seq: stageSeq,
          stage_name: s.stage_name,
          step_seq: 0,
          step_name: '',
          ipqc_testing: 'No',
          sampling_by: 'Production',
          time_stamp: 'Not Applicable',
          yield_reconciliation: 'Not Applicable',
          equipment_point: 'Not Applicable',
          instruction: '',
          remark: '',
        });
        return;
      }
      s.steps.forEach((st, sj) => {
        rows.push({
          stage_seq: stageSeq,
          stage_name: s.stage_name,
          step_seq: st.step_seq != null && !isNaN(Number(st.step_seq)) ? Number(st.step_seq) : sj + 1,
          step_name: st.step_name,
          ipqc_testing: st.ipqc_testing || 'No',
          sampling_by: st.ipqc_testing === 'Yes' ? (st.sampling_by || 'Production') : 'Production',
          time_stamp: this.normalizeTimeStamp(st.time_stamp),
          yield_reconciliation: this.normalizeYieldRecon(st.yield_reconciliation),
          equipment_point: this.normalizeEquipmentPoint(st.equipment_point),
          instruction: st.instruction || '',
          remark: st.remark || '',
        });
      });
    });
    return rows;
  }

  save(): void {
    if (!this.selectedConfig || !this.selectedProduct) {
      alertify.error('Select configuration and product.');
      return;
    }
    if (!this.stages.length) {
      alertify.error('Add at least one stage.');
      return;
    }
    const rows = this.flattenRows();
    this.saving = true;
    const payload = {
      config_id: this.selectedConfig.id,
      product_code: this.selectedProduct.product_code,
      product_name: this.selectedProduct.product_name,
      rows,
    };
    const detail =
      'Product stage map: ' +
      this.selectedProduct.product_code +
      ' → ' +
      (this.selectedConfig.bmr_no || '') +
      ' (' +
      (this.selectedConfig.process_type || '') +
      ' / ' +
      (this.selectedConfig.dosage_form || '') +
      ')';
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:bmr_product_stage_map',
        detail,
        recordRef: this.selectedConfig.id + ':' + this.selectedProduct.product_code,
      })
      .then((sig) => {
        if (!sig) {
          this.saving = false;
          return;
        }
        this.service.post('master/ebmr_bpr.php?type=saveConfigStageStep', JSON.stringify(payload)).subscribe({
          next: (r: any) => {
            this.saving = false;
            if (r && r.status === 'success') {
              alertify.success(
                'Product mapping saved (' + this.stages.length + ' stages, ' + this.totalSteps + ' steps)'
              );
              this.loadBinding();
              this.loadCounts();
            } else {
              alertify.error((r && r.message) || 'Save failed');
            }
          },
          error: () => {
            this.saving = false;
            alertify.error('Server error while saving.');
          },
        });
      })
      .catch(() => {
        this.saving = false;
      });
  }

  viewMapping(config: any): void {
    const code = this.selectedProductCode[config.id];
    if (!code) {
      alertify.error('Select a product first.');
      return;
    }
    const list = this.productsByConfig[config.id] || [];
    const product = list.find((p: any) => p.product_code === code);
    this.viewConfig = config;
    this.viewProduct = product;
    this.service
      .get(
        'master/ebmr_bpr.php?type=getConfigStageStep&config_id=' +
          config.id +
          '&product_code=' +
          encodeURIComponent(code)
      )
      .subscribe({
        next: (r: any) => {
          this.viewRows = Array.isArray(r) ? r : [];
          this.viewOpen = true;
        },
        error: () => {
          this.viewRows = [];
          this.viewOpen = true;
        },
      });
  }

  get viewGrouped(): { stage: string; steps: any[] }[] {
    const out: { stage: string; steps: any[] }[] = [];
    for (const r of this.viewRows) {
      let g = out.find((x) => x.stage === r.stage_name);
      if (!g) {
        g = { stage: r.stage_name, steps: [] };
        out.push(g);
      }
      g.steps.push(r);
    }
    return out;
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
