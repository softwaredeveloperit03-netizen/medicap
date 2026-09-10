import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
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

@Component({
  selector: 'app-ebmr-config-stage-step',
  templateUrl: './config-stage-step.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './config-stage-step.component.css'],
})
export class ConfigStageStepComponent implements OnInit {
  @ViewChild('stageFormPanel') stageFormPanel?: ElementRef<HTMLElement>;

  loading = false;
  bindingLoading = false;
  saving = false;

  // Saved configurations (process_type + dosage_form pairs) pulled from Configure Master.
  configs: any[] = [];
  configSearch = '';
  counts: { [k: string]: { stage_count: number; step_count: number } } = {};
  selectedConfig: any = null;
  formOpen = false;

  // Stage master suggestions for the chosen dosage form (optional convenience).
  stageSuggestions: string[] = [];

  // Working model: each stage accommodates multiple steps.
  stages: StageGroup[] = [];
  newStageName = '';

  // View modal
  viewOpen = false;
  viewRows: any[] = [];
  viewConfig: any = null;

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

  stageCount(c: any): number {
    return this.counts[String(c.id)]?.stage_count || 0;
  }
  stepCount(c: any): number {
    return this.counts[String(c.id)]?.step_count || 0;
  }

  /** "Add Stage Step" — open the addition form for a configuration. */
  openForm(config: any, event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    if (!config || !config.id) {
      alertify.error('Invalid configuration.');
      return;
    }
    this.selectedConfig = config;
    this.formOpen = true;
    this.stages = [];
    this.newStageName = '';
    this.stageSuggestions = [];
    this.loadBinding();
    this.loadStageSuggestions(config.dosage_form);
    setTimeout(() => this.scrollToForm(), 80);
    alertify.message('Editing stages for ' + (config.bmr_no || config.dosage_form || 'configuration'));
  }

  private scrollToForm(): void {
    const el = this.stageFormPanel?.nativeElement;
    if (el && typeof el.scrollIntoView === 'function') {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  private loadStageSuggestions(dosageForm: string): void {
    if (!dosageForm) {
      this.stageSuggestions = [];
      return;
    }
    const encoded = encodeURIComponent(dosageForm);
    // Prefer ebmrbpr_stage master; fall back to process stage master names.
    this.service.get('master/ebmr_bpr.php?type=getStages&dosage_form=' + encoded).subscribe({
      next: (r: any) => {
        const fromEbmr = Array.isArray(r) ? r.map((s: any) => s.stage_name).filter(Boolean) : [];
        if (fromEbmr.length) {
          this.stageSuggestions = fromEbmr;
          return;
        }
        this.service.get('bmr/process.php?type=getProcessesmaster&dosage_form=' + encoded).subscribe({
          next: (tree: any) => {
            const names: string[] = [];
            (Array.isArray(tree) ? tree : []).forEach((pt: any) => {
              (pt.stages || []).forEach((st: any) => {
                const n = (st.stage || '').trim();
                if (n && names.indexOf(n) === -1) {
                  names.push(n);
                }
              });
            });
            this.stageSuggestions = names;
          },
          error: () => {
            this.stageSuggestions = [];
          },
        });
      },
      error: () => {
        this.stageSuggestions = [];
      },
    });
  }

  closeForm(): void {
    this.formOpen = false;
    this.selectedConfig = null;
    this.stages = [];
    this.newStageName = '';
  }

  loadBinding(): void {
    if (!this.selectedConfig) {
      return;
    }
    this.bindingLoading = true;
    this.service
      .get(
        'master/ebmr_bpr.php?type=getConfigStageStep&scope=config&config_id=' +
          this.selectedConfig.id
      )
      .subscribe({
        next: (r: any) => {
          const data = Array.isArray(r) ? r : [];
          const groups: StageGroup[] = [];
          for (const d of data) {
            const stageName = (d.stage_name || '').trim();
            if (!stageName) {
              continue;
            }
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
          this.stages = groups;
          this.bindingLoading = false;
        },
        error: () => {
          this.stages = [];
          this.bindingLoading = false;
          alertify.error('Failed to load existing stages/steps');
        },
      });
  }

  /** Load full stage/step tree from Process Stage Master into the editor. */
  loadFromProcessMaster(): void {
    if (!this.selectedConfig?.dosage_form) {
      alertify.error('Configuration has no dosage form.');
      return;
    }
    const encoded = encodeURIComponent(this.selectedConfig.dosage_form);
    this.bindingLoading = true;
    this.service.get('bmr/process.php?type=getProcessesmaster&dosage_form=' + encoded).subscribe({
      next: (tree: any) => {
        const list = Array.isArray(tree) ? tree : [];
        if (!list.length) {
          this.bindingLoading = false;
          alertify.error('No Process Stage Master data for this dosage form.');
          return;
        }
        // Prefer first process type (or Wet Granulation)
        let pt = list.find((p: any) =>
          String(p.process_type || '')
            .toLowerCase()
            .includes('wet granulation')
        );
        if (!pt) {
          pt = list[0];
        }
        const groups: StageGroup[] = [];
        (pt.stages || []).forEach((st: any) => {
          const stageName = (st.stage || '').trim();
          if (!stageName) {
            return;
          }
          const g = this.blankStage(stageName);
          (st.steps || []).forEach((step: any, idx: number) => {
            const stepName = (step.step || '').trim();
            if (!stepName) {
              return;
            }
            g.steps.push({
              step_seq: idx + 1,
              step_name: stepName,
              ipqc_testing: /ipc|ipqc|ipqa|in-?process/i.test(stepName) ? 'Yes' : 'No',
              sampling_by: 'Production',
              time_stamp: 'Not Applicable',
              yield_reconciliation: 'Not Applicable',
              equipment_point: 'Not Applicable',
              instruction: '',
              remark: '',
            });
          });
          groups.push(g);
        });
        this.stages = groups;
        this.bindingLoading = false;
        alertify.success(
          'Loaded ' + groups.length + ' stages from Process Stage Master (' + (pt.process_type || '') + ')'
        );
        setTimeout(() => this.scrollToForm(), 50);
      },
      error: () => {
        this.bindingLoading = false;
        alertify.error('Failed to load Process Stage Master');
      },
    });
  }

  /* ---------- stages (each accommodates multiple steps) ---------- */
  addStage(): void {
    if (!this.selectedConfig) {
      alertify.error('Select a configuration first (click Add Stage Step).');
      return;
    }
    const name = (this.newStageName || '').trim();
    if (!name) {
      alertify.error('Enter a stage name.');
      return;
    }
    if (this.stages.some((s) => s.stage_name.toLowerCase() === name.toLowerCase())) {
      alertify.error('That stage already exists.');
      return;
    }
    this.stages = [...this.stages, this.blankStage(name)];
    this.newStageName = '';
    alertify.success('Stage added: ' + name);
  }

  removeStage(i: number): void {
    this.stages = this.stages.filter((_, idx) => idx !== i);
  }

  addStep(stage: StageGroup): void {
    if (!stage) {
      return;
    }
    const name = (stage.draftStep.step_name || '').trim();
    if (!name) {
      alertify.error('Enter a step name.');
      return;
    }
    stage.steps = [
      ...stage.steps,
      {
        step_name: name,
        step_seq: stage.draftStep.step_seq,
        ipqc_testing: stage.draftStep.ipqc_testing || 'No',
        sampling_by:
          stage.draftStep.ipqc_testing === 'Yes'
            ? stage.draftStep.sampling_by || 'Production'
            : 'Production',
        time_stamp: this.normalizeTimeStamp(stage.draftStep.time_stamp),
        yield_reconciliation: this.normalizeYieldRecon(stage.draftStep.yield_reconciliation),
        equipment_point: this.normalizeEquipmentPoint(stage.draftStep.equipment_point),
        instruction: (stage.draftStep.instruction || '').trim(),
        remark: (stage.draftStep.remark || '').trim(),
      },
    ];
    stage.draftStep = this.blankStep();
  }

  removeStep(stage: StageGroup, j: number): void {
    stage.steps = stage.steps.filter((_, idx) => idx !== j);
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
          sampling_by: st.ipqc_testing === 'Yes' ? st.sampling_by || 'Production' : 'Production',
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
    if (!this.selectedConfig) {
      alertify.error('Select a configuration first.');
      return;
    }
    if (!this.stages.length) {
      alertify.error('Add at least one stage.');
      return;
    }
    const rows = this.flattenRows();
    this.saving = true;
    const payload = { config_id: this.selectedConfig.id, rows };
    const detail =
      'Stage/Step binding for ' +
      (this.selectedConfig.bmr_no || '') +
      ' (' +
      (this.selectedConfig.process_type || '') +
      ' / ' +
      (this.selectedConfig.dosage_form || '') +
      ')';
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:bmr_stage_step',
        detail,
        recordRef: this.selectedConfig.id,
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
                'Stage & Step saved (' + this.stages.length + ' stages, ' + this.totalSteps + ' steps)'
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

  viewStageStep(config?: any, event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    const cfg = config || this.selectedConfig;
    if (!cfg) {
      alertify.error('Select a configuration first.');
      return;
    }
    this.viewConfig = cfg;
    this.service
      .get('master/ebmr_bpr.php?type=getConfigStageStep&scope=config&config_id=' + cfg.id)
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
