import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  PLS_FORM_ID, PLS_SAMPLE_CATEGORIES, PLS_SAMPLE_TYPE_LABELS, PLS_LAB_NUMBER_SCHEMES, PLS_API, parsePlsResponse
} from '../pls.constants';
declare let alertify: any;

@Component({
  selector: 'app-pls-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  readonly formId = PLS_FORM_ID;
  readonly sampleCategories = PLS_SAMPLE_CATEGORIES;
  readonly sampleTypeLabels = PLS_SAMPLE_TYPE_LABELS;
  readonly labSchemes = PLS_LAB_NUMBER_SCHEMES;

  form_no = '';
  pendingIntakes: any[] = [];
  materials: any[] = [];
  selectedBatchId = '';
  material_type = 'Raw Material';
  grnLoading = false;
  saving = false;
  submitting = false;

  form: any = {
    flow_type: 'raw_material',
    sample_category: '',
    sample_type_label: 'MEDICAP LABORATORIES',
    sampling_batch_id: 0,
    lab_number_scheme: 'qc',
    controlled_substance: 'No',
    chemical_name: '',
    commercial_name: '',
    manufacturer: '',
    manufacturer_lot: '',
    medicap_code: '',
    medicap_lot: '',
    mfg_retest_applicable: 'No',
    mfg_retest_date: '',
    exp_applicable: 'No',
    exp_date: '',
    containers_received: '',
    fmm_attached: 'No'
  };

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getNextFormNo();
    this.loadPendingIntakes();
    this.loadMaterials();
  }

  getNextFormNo() {
    this.service.get(PLS_API + 'type=getNextFormNo').subscribe((res: any) => {
      this.form_no = res.form_no || '';
    });
  }

  loadPendingIntakes() {
    this.grnLoading = true;
    this.selectedBatchId = '';
    const mt = encodeURIComponent(this.material_type);
    const pendingUrl = 'qc/sampling/raw.php?type=getBatchesForGrnReceiving&material_type=' + mt;
    const receivedUrl = 'qc/sampling/raw.php?type=gerGrnReceivingLog&material_type=' + mt;
    const plsUrl = PLS_API + 'type=getPendingIntakes&material_type=' + mt;

    this.service.get(pendingUrl).subscribe((pending: any) => {
      const pendingList = Array.isArray(pending) ? pending : [];
      this.service.get(receivedUrl).subscribe((received: any) => {
        const receivedList = Array.isArray(received) ? received : [];
        const merged = this.mergeGrnBatches(pendingList, receivedList);
        if (merged.length) {
          this.pendingIntakes = merged;
          this.grnLoading = false;
        } else {
          this.service.get(plsUrl).subscribe((pls: any) => {
            this.pendingIntakes = Array.isArray(pls) ? pls : [];
            this.grnLoading = false;
          }, () => { this.pendingIntakes = []; this.grnLoading = false; });
        }
      }, () => {
        this.service.get(plsUrl).subscribe((pls: any) => {
          this.pendingIntakes = Array.isArray(pls) ? pls : [];
          this.grnLoading = false;
        }, () => { this.pendingIntakes = []; this.grnLoading = false; });
      });
    }, () => {
      this.service.get(plsUrl).subscribe((pls: any) => {
        this.pendingIntakes = Array.isArray(pls) ? pls : [];
        this.grnLoading = false;
      }, () => { this.pendingIntakes = []; this.grnLoading = false; });
    });
  }

  mergeGrnBatches(pending: any[], received: any[]): any[] {
    const map = new Map<number, any>();
    [...pending, ...received].forEach(b => {
      if (b?.id != null) {
        map.set(Number(b.id), b);
      }
    });
    return Array.from(map.values()).sort((a, b) => Number(b.id) - Number(a.id));
  }

  intakeStageLabel(b: any): string {
    if (b?.isGrnReceive === 'YES' || b?.intake_stage === 'in_quarantine') {
      return 'In quarantine';
    }
    return 'Pending receive';
  }

  loadMaterials() {
    this.service.get(PLS_API + 'type=getMaterials').subscribe((res: any) => {
      this.materials = Array.isArray(res) ? res : [];
    });
  }

  onBatchSelect(batchId: string) {
    if (!batchId) {
      return;
    }
    const fromList = this.pendingIntakes.find(b => String(b.id) === String(batchId));
    if (fromList) {
      this.fillFromIntake(fromList);
      return;
    }
    this.service.get(PLS_API + 'type=getIntakeByBatchId&batch_id=' + batchId)
      .subscribe((res: any) => {
        if (res?.status === 'success' && res.intake) {
          this.fillFromIntake(res.intake);
        }
      });
  }

  fillFromIntake(i: any) {
    this.form.sampling_batch_id = i.id;
    this.form.chemical_name = i.material_name || '';
    this.form.medicap_code = i.material_code || '';
    this.form.medicap_lot = i.ar_no || i.batch_no || '';
    this.form.manufacturer_lot = i.batch_no || '';
    this.form.manufacturer = i.manufacturer_name || i.manuNAme || i.mfg_by || '';
    this.form.containers_received = i.total_containers || i.containers || '';
    if (i.exp_date) {
      this.form.exp_applicable = 'Yes';
      this.form.exp_date = String(i.exp_date).substring(0, 10);
    }
    if (i.mfg_date) {
      this.form.mfg_retest_applicable = 'Yes';
      this.form.mfg_retest_date = String(i.mfg_date).substring(0, 10);
    }
    const mat = this.materials.find(m => m.material_code === i.material_code);
    if (mat) {
      this.form.commercial_name = mat.grade || i.grade || '';
    } else if (i.grade) {
      this.form.commercial_name = i.grade;
    }
    this.inferCategory(i.material_type);
    this.inferLabScheme();
  }

  onMaterialSelect(code: string) {
    const m = this.materials.find(x => x.material_code === code);
    if (m) {
      this.form.chemical_name = m.material_name || '';
      this.form.medicap_code = m.material_code || '';
      this.form.commercial_name = m.grade || '';
      this.inferCategory(m.material_type);
      this.inferLabScheme();
    }
  }

  inferCategory(materialType: string) {
    const t = (materialType || '').toLowerCase();
    if (t.includes('api')) {
      this.form.sample_category = 'Raw Material - API';
    } else if (t.includes('solvent')) {
      this.form.sample_category = 'Raw Material - Solvent';
    } else if (t.includes('pack')) {
      this.form.sample_category = 'Packaging Components';
    } else if (t.includes('raw')) {
      this.form.sample_category = 'Raw Material - Excipient';
    }
  }

  inferLabScheme() {
    const cat = this.form.sample_category || '';
    if (cat.includes('Contract')) {
      this.form.lab_number_scheme = 'qc_con';
    } else if (cat.includes('Product Development') || cat.includes('PD')) {
      this.form.lab_number_scheme = 'lab';
    } else {
      this.form.lab_number_scheme = 'qc';
    }
  }

  onCategoryChange() {
    this.inferLabScheme();
    if (this.form.sample_category.includes('Contract')) {
      this.form.flow_type = 'other';
    }
  }

  buildPayload() {
    return {
      form_no: this.form_no,
      ...this.form,
      sampling_batch_id: this.form.sampling_batch_id || (this.selectedBatchId ? parseInt(this.selectedBatchId, 10) : 0)
    };
  }

  saveDraft() {
    this.saving = true;
    this.service.postTextResponse(PLS_API + 'type=saveSample', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.saving = false;
        const res = parsePlsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Draft saved.');
          this.router.navigate(['/qc/processing-laboratory-samples/log']);
        } else {
          alertify.error(res?.message || res?.status || 'Save failed');
        }
      }, (err) => {
        this.saving = false;
        alertify.error(err?.error?.message || err?.message || 'Save failed — check network / server upload');
      });
  }

  submit() {
    if (!this.form.chemical_name || !this.form.medicap_code) {
      alertify.error('Chemical name and Medicap code are required.');
      return;
    }
    this.submitting = true;
    this.service.postTextResponse(PLS_API + 'type=submitSample', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.submitting = false;
        const res = parsePlsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Sample submitted to workflow.');
          this.router.navigate(['/qc/processing-laboratory-samples/log']);
        } else {
          alertify.error(res?.message || res?.status || 'Submit failed');
        }
      }, (err) => {
        this.submitting = false;
        alertify.error(err?.error?.message || err?.message || 'Submit failed — check network / server upload');
      });
  }
}
