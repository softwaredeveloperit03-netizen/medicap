import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';

declare let alertify: any;

@Component({
  selector: 'app-ebmr-stage-step',
  templateUrl: './stage-step.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './stage-step.component.css'],
})
export class StageStepComponent implements OnInit {
  loading = false;
  dosageForms: string[] = [];
  dosageFilter = '';

  stages: any[] = [];
  selectedStage: any = null;
  steps: any[] = [];

  stageModalOpen = false;
  stageForm: any = {};
  editingStage = false;

  stepModalOpen = false;
  stepForm: any = {};
  editingStep = false;

  // map stages/steps -> BMR configuration
  mapModalOpen = false;
  mapConfigs: any[] = [];
  mapConfigId: any = '';
  mapStages: any[] = [];
  mapLoading = false;

  constructor(private service: DataAccessService, private router: Router, private esign: EsignService) {}

  ngOnInit(): void {
    this.loadDosageForms();
    this.loadStages();
  }

  loadDosageForms(): void {
    this.service.get('master/ebmr_bpr.php?type=getDosageForms').subscribe((r: any) => {
      this.dosageForms = Array.isArray(r) ? r : [];
    });
  }

  loadStages(): void {
    this.loading = true;
    let url = 'master/ebmr_bpr.php?type=getStages';
    if (this.dosageFilter) {
      url += '&dosage_form=' + encodeURIComponent(this.dosageFilter);
    }
    this.service.get(url).subscribe({
      next: (r: any) => {
        this.stages = Array.isArray(r) ? r : [];
        this.loading = false;
        if (this.stages.length && !this.selectedStage) {
          this.selectStage(this.stages[0]);
        } else if (this.selectedStage) {
          const found = this.stages.find((s) => s.id === this.selectedStage.id);
          this.selectedStage = found || null;
          if (this.selectedStage) {
            this.loadSteps();
          } else {
            this.steps = [];
          }
        }
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load stages');
      },
    });
  }

  selectStage(stage: any): void {
    this.selectedStage = stage;
    this.loadSteps();
  }

  loadSteps(): void {
    if (!this.selectedStage) {
      this.steps = [];
      return;
    }
    this.service
      .get('master/ebmr_bpr.php?type=getSteps&stage_id=' + this.selectedStage.id)
      .subscribe((r: any) => {
        this.steps = Array.isArray(r) ? r : [];
      });
  }

  /* ---------- stage CRUD ---------- */
  newStage(): void {
    this.editingStage = false;
    this.stageForm = { stage_name: '', dosage_form: this.dosageFilter || '', seq_no: this.stages.length + 1, description: '' };
    this.stageModalOpen = true;
  }
  editStage(stage: any): void {
    this.editingStage = true;
    this.stageForm = { ...stage };
    this.stageModalOpen = true;
  }
  saveStage(): void {
    if (!this.stageForm.stage_name) {
      alertify.error('Stage name is required');
      return;
    }
    const url = this.editingStage
      ? 'master/ebmr_bpr.php?type=updateStage&id=' + this.stageForm.id
      : 'master/ebmr_bpr.php?type=saveStage';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:stage', detail: 'Stage: ' + this.stageForm.stage_name, recordRef: this.stageForm.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(this.stageForm)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editingStage ? 'Stage updated' : 'Stage added');
            this.stageModalOpen = false;
            this.loadStages();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }
  deleteStage(stage: any): void {
    alertify.confirm('Delete Stage', `Delete stage "${stage.stage_name}" and detach its steps?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteStage&id=' + stage.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Stage deleted');
          if (this.selectedStage && this.selectedStage.id === stage.id) {
            this.selectedStage = null;
          }
          this.loadStages();
        }
      });
    }, () => {});
  }

  /* ---------- step CRUD ---------- */
  newStep(): void {
    if (!this.selectedStage) {
      alertify.error('Select a stage first');
      return;
    }
    this.editingStep = false;
    this.stepForm = { stage_id: this.selectedStage.id, step_name: '', seq_no: this.steps.length + 1, description: '' };
    this.stepModalOpen = true;
  }
  editStep(step: any): void {
    this.editingStep = true;
    this.stepForm = { ...step };
    this.stepModalOpen = true;
  }
  saveStep(): void {
    if (!this.stepForm.step_name) {
      alertify.error('Step name is required');
      return;
    }
    this.stepForm.stage_id = this.selectedStage.id;
    const url = this.editingStep
      ? 'master/ebmr_bpr.php?type=updateStep&id=' + this.stepForm.id
      : 'master/ebmr_bpr.php?type=saveStep';
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:step', detail: 'Step: ' + this.stepForm.step_name, recordRef: this.stepForm.id || '' })
      .then((sig) => {
        if (!sig) return;
        this.service.post(url, JSON.stringify(this.stepForm)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success(this.editingStep ? 'Step updated' : 'Step added');
            this.stepModalOpen = false;
            this.loadSteps();
          } else {
            alertify.error((r && r.message) || 'Save failed');
          }
        });
      });
  }
  deleteStep(step: any): void {
    alertify.confirm('Delete Step', `Delete step "${step.step_name}"?`, () => {
      this.service.get('master/ebmr_bpr.php?type=deleteStep&id=' + step.id).subscribe((r: any) => {
        if (r && r.status === 'success') {
          alertify.success('Step deleted');
          this.loadSteps();
        }
      });
    }, () => {});
  }

  /* ---------- map stages/steps to a BMR configuration ---------- */
  openMapModal(): void {
    this.mapConfigId = '';
    this.mapStages = [];
    this.mapModalOpen = true;
    this.service.get('master/ebmr_bpr.php?type=getConfigs').subscribe((r: any) => {
      this.mapConfigs = Array.isArray(r) ? r : [];
    });
  }

  onMapConfigChange(): void {
    this.mapStages = [];
    if (!this.mapConfigId) return;
    const cfg = this.mapConfigs.find((c) => String(c.id) === String(this.mapConfigId));
    const dosage = cfg ? cfg.dosage_form : '';
    this.mapLoading = true;
    // load the configuration's existing map + all stages for its dosage form
    const stagesUrl = 'master/ebmr_bpr.php?type=getStages' + (dosage ? '&dosage_form=' + encodeURIComponent(dosage) : '');
    forkJoin({
      cfg: this.service.get('master/ebmr_bpr.php?type=getConfig&id=' + this.mapConfigId),
      stages: this.service.get(stagesUrl),
    }).subscribe((res: any) => {
      const existing = (res.cfg && res.cfg.stage_map) || [];
      const stages = Array.isArray(res.stages) ? res.stages : [];
      if (!stages.length) { this.mapLoading = false; return; }
      const stepCalls = stages.map((s: any) =>
        this.service.get('master/ebmr_bpr.php?type=getSteps&stage_id=' + s.id)
      );
      forkJoin(stepCalls.length ? stepCalls : [of([])]).subscribe((all: any) => {
        this.mapStages = stages.map((s: any, i: number) => {
          const exStage = existing.find((e: any) => String(e.stage_id) === String(s.id));
          const stepList = Array.isArray(all[i]) ? all[i] : [];
          return {
            stage_id: s.id,
            stage_name: s.stage_name,
            seq_no: s.seq_no,
            _include: !!exStage,
            steps: stepList.map((sp: any) => ({
              step_id: sp.id,
              step_name: sp.step_name,
              seq_no: sp.seq_no,
              _include: exStage ? (exStage.steps || []).some((es: any) => String(es.step_id) === String(sp.id)) : true,
            })),
          };
        });
        this.mapLoading = false;
      });
    }, () => { this.mapLoading = false; alertify.error('Failed to load stages for mapping'); });
  }

  saveMap(): void {
    if (!this.mapConfigId) { alertify.error('Select a configuration'); return; }
    const stage_map = this.mapStages
      .filter((s) => s._include)
      .map((s) => ({
        stage_id: s.stage_id,
        stage_name: s.stage_name,
        seq_no: s.seq_no,
        steps: s.steps.filter((sp: any) => sp._include).map((sp: any) => ({
          step_id: sp.step_id,
          step_name: sp.step_name,
          seq_no: sp.seq_no,
        })),
      }));
    if (!stage_map.length) { alertify.error('Select at least one stage to map'); return; }
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:stage_map', detail: stage_map.length + ' stage(s) mapped to configuration', recordRef: this.mapConfigId })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post('master/ebmr_bpr.php?type=saveConfigStageMap', JSON.stringify({ config_id: this.mapConfigId, stage_map }))
          .subscribe((r: any) => {
            if (r && r.status === 'success') {
              alertify.success('Stages mapped to configuration');
              this.mapModalOpen = false;
            } else {
              alertify.error((r && r.message) || 'Mapping failed');
            }
          });
      });
  }

  close(): void {
    this.router.navigate(['/master/ebmr-bpr']);
  }
}
