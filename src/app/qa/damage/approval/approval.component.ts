import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  raiseFormSelection = '';
  results: any[] = [];
  loading = false;

  selectedReport: any = {};
  remark = '';

  ncrFormModel = {
    deviation_classification: '',
    category_facility: false,
    category_equipment: false,
    category_material: false,
    category_product: false,
    category_process: false,
    category_analytical: false,
    category_document_handling: false,
    category_sops: false,
    category_other: false,
    category_other_specify: '',
    observation_datetime: '',
    product_document_equipment: '',
    lot_number: '',
    area_department: '',
    non_conformance_details: ''
  };

  lirFormModel = {
    incident_oos: false,
    incident_oot: false,
    incident_analytical_deviation: false,
    date_of_incident: '',
    date_of_discovery: '',
    product_material_code: '',
    product_material_name: '',
    lot_number: '',
    expiry_retest_bulk_hold_date: '',
    supplier_manufacturer_name: '',
    supplier_manufacturer_lot: '',
    sample_api: false,
    sample_excipient: false,
    sample_in_process: false,
    sample_finished: false,
    sample_stability: false,
    sample_product_development: false,
    sample_packaging_component: false,
    sample_other: false,
    sample_other_specify: '',
    stability_time_1m: false,
    stability_time_2m: false,
    stability_time_3m: false,
    stability_time_6m: false,
    stability_time_9m: false,
    stability_time_12m: false,
    stability_time_18m: false,
    stability_time_24m: false,
    stability_time_36m: false,
    stability_time_other: '',
    storage_long_term: false,
    storage_intermediate: false,
    storage_accelerated: false,
    test_performed: '',
    test_method_no: '',
    test_method_title: '',
    description_of_occurrence: '',
    other_impacted_lots: '',
    specification_or_limits: ''
  };

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDamageContainerIpqa();
  }

  viewFile(url) {
    url = this.service.url + '../../upload/damage/' + url + '?v=1';
    window.open(url, '_blank');
  }

  getDamageContainerIpqa() {
    this.loading = true;
    this.service.get('store/raw.php?type=getDamageContainerIpqa').subscribe(response => {
      this.results = (Array.isArray(response) ? response : []).map((row) =>
        this.normalizeReport(row)
      );
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  damages = [];
  batches: any = {};

  private normalizeReport(report: any): any {
    if (!report) {
      return report;
    }
    if (typeof report.receiving_details === 'string') {
      try {
        report.receiving_details = JSON.parse(report.receiving_details);
      } catch {
        report.receiving_details = {};
      }
    }
    if (!report.receiving_details || typeof report.receiving_details !== 'object') {
      report.receiving_details = {};
    }
    if (typeof report.damage_details === 'string') {
      try {
        report.damage_details = JSON.parse(report.damage_details);
      } catch {
        report.damage_details = {};
      }
    }
    if (!report.damage_details || typeof report.damage_details !== 'object' || Array.isArray(report.damage_details)) {
      report.damage_details = {};
    }
    report.batch_no = this.resolveLotNumber(report);
    report.lot_number = report.batch_no;
    return report;
  }

  getLotNumber(): string {
    return this.resolveLotNumber(this.selectedReport);
  }

  getSupplierLotNo(): string {
    const batch = this.batches || {};
    return String(
      batch.supplier_batch_no ||
        this.selectedReport?.supplier_batch_no ||
        ''
    ).trim();
  }

  getExpiryDate(): string {
    const batch = this.batches || {};
    const exp = batch.exp_date || batch.expiry_date || '';
    if (!exp) {
      return '';
    }
    const d = new Date(exp);
    if (isNaN(d.getTime())) {
      return String(exp);
    }
    return d.toISOString().slice(0, 10);
  }

  private resolveLotNumber(report: any): string {
    if (!report) {
      return '';
    }

    const direct = String(
      report.lot_number || report.batch_no || report.damage_batch_no || ''
    ).trim();
    if (direct) {
      return direct;
    }

    const details = report.receiving_details || {};
    const fromDetails = String(
      details.damage_batch_no || details.batch_no || ''
    ).trim();
    if (fromDetails) {
      return fromDetails;
    }

    const batchData = report.batches ?? this.batches;
    if (batchData && !Array.isArray(batchData) && typeof batchData === 'object') {
      const fromBatch = String(
        batchData.batch_no || batchData.lot_number || ''
      ).trim();
      if (fromBatch) {
        return fromBatch;
      }
    }

    const batches = Array.isArray(report.batches) ? report.batches : [];
    if (batches.length === 1) {
      return String(batches[0]?.batch_no || '').trim();
    }
    if (batches.length > 1) {
      const batchNos = batches
        .map((batch: any) => String(batch?.batch_no || '').trim())
        .filter((batchNo: string) => !!batchNo);
      return [...new Set(batchNos)].join(', ');
    }

    return '';
  }

  private toDateInput(value: any): string {
    if (!value) {
      return '';
    }
    const d = value instanceof Date ? value : new Date(value);
    if (isNaN(d.getTime())) {
      return '';
    }
    return d.toISOString().slice(0, 10);
  }

  view(result) {
    this.selectedReport = this.normalizeReport(result);
    const damageDetails = this.selectedReport['damage_details'];
    if (damageDetails && Array.isArray(damageDetails.containers)) {
      this.damages = damageDetails.containers;
    } else if (Array.isArray(damageDetails)) {
      this.damages = damageDetails;
    } else {
      this.damages = [];
    }
    this.batches = this.selectedReport['batches'] || {};
    this.raiseFormSelection = '';
    this.ipqaRemark = 'Approve';
    this.isView = true;
  }

  get isNcrForm(): boolean {
    return this.raiseFormSelection === 'NCR';
  }

  get isLirForm(): boolean {
    return this.raiseFormSelection === 'LIR';
  }

  hasSavedNcr(): boolean {
    return !!this.getSavedNcrForm();
  }

  hasSavedLir(): boolean {
    return !!this.getSavedLirForm();
  }

  private getSavedNcrForm(): any {
    const details = this.selectedReport?.damage_details;
    if (!details || typeof details !== 'object') {
      return null;
    }
    const saved = details.ncr_form;
    return saved && typeof saved === 'object' ? saved : null;
  }

  private getSavedLirForm(): any {
    const details = this.selectedReport?.damage_details;
    if (!details || typeof details !== 'object') {
      return null;
    }
    const saved = details.lir_form;
    return saved && typeof saved === 'object' ? saved : null;
  }

  private mergeFormModel<T extends Record<string, any>>(defaults: T, saved: any): T {
    if (!saved || typeof saved !== 'object') {
      return { ...defaults };
    }
    return { ...defaults, ...saved };
  }

  private syncSavedFormToReport(formType: 'NCR' | 'LIR', data: any, damageDetails?: any): void {
    if (!this.selectedReport.damage_details || typeof this.selectedReport.damage_details !== 'object') {
      this.selectedReport.damage_details = {};
    }
    if (damageDetails && typeof damageDetails === 'object') {
      this.selectedReport.damage_details = damageDetails;
    } else if (formType === 'NCR') {
      this.selectedReport.damage_details.ncr_form = { ...data };
    } else {
      this.selectedReport.damage_details.lir_form = { ...data };
    }

    const idx = (this.results || []).findIndex(
      (row: any) => String(row?.id) === String(this.selectedReport?.id)
    );
    if (idx >= 0) {
      this.results[idx] = {
        ...this.results[idx],
        damage_details: this.selectedReport.damage_details,
      };
    }
  }

  private persistDamageForm(formType: 'NCR' | 'LIR', formData: any) {
    return this.service.post(
      'store/raw.php?type=saveDamageIpqaForm&form_type=' +
        formType +
        '&id=' +
        this.selectedReport['id'],
      JSON.stringify(formData)
    );
  }

  onRaiseFormChange(): void {
    if (this.raiseFormSelection === 'NCR') {
      this.initNcrForm();
      return;
    }
    if (this.raiseFormSelection === 'LIR') {
      this.initLirForm();
      return;
    }
  }

  closeRaiseForm(): void {
    this.raiseFormSelection = '';
  }

  initNcrForm() {
    const report = this.selectedReport || {};
    const materialName = report['material_name'] || '';
    const materialCode = report['material_code'] || '';
    const batchNo = this.resolveLotNumber(report);
    const receivingDate = report['receiving_date'] || report['damageOn'] || '';

    let observationDatetime = '';
    if (receivingDate) {
      const d = new Date(receivingDate);
      if (!isNaN(d.getTime())) {
        observationDatetime = d.toISOString().slice(0, 16);
      }
    }
    if (!observationDatetime) {
      observationDatetime = new Date().toISOString().slice(0, 16);
    }

    this.ncrFormModel = this.mergeFormModel(
      {
        deviation_classification: '',
        category_facility: false,
        category_equipment: false,
        category_material: true,
        category_product: false,
        category_process: false,
        category_analytical: false,
        category_document_handling: false,
        category_sops: false,
        category_other: false,
        category_other_specify: '',
        observation_datetime: observationDatetime,
        product_document_equipment: materialCode
          ? `${materialName} (${materialCode})`
          : materialName,
        lot_number: batchNo,
        area_department: localStorage.getItem('department') || '',
        non_conformance_details: ''
      },
      this.getSavedNcrForm()
    );

    if (!this.ncrFormModel.lot_number) {
      this.ncrFormModel.lot_number = batchNo;
    }
    if (!this.ncrFormModel.product_document_equipment) {
      this.ncrFormModel.product_document_equipment = materialCode
        ? `${materialName} (${materialCode})`
        : materialName;
    }
  }

  initLirForm() {
    const report = this.selectedReport || {};
    const incidentDate = this.toDateInput(report['receiving_date'] || report['damageOn']);
    const discoveryDate = this.toDateInput(new Date());

    this.lirFormModel = this.mergeFormModel(
      {
        incident_oos: false,
        incident_oot: false,
        incident_analytical_deviation: false,
        date_of_incident: incidentDate,
        date_of_discovery: discoveryDate,
        product_material_code: report['material_code'] || '',
        product_material_name: report['material_name'] || '',
        lot_number: this.resolveLotNumber(report),
        expiry_retest_bulk_hold_date: this.getExpiryDate(),
        supplier_manufacturer_name: report['vendor_name'] || '',
        supplier_manufacturer_lot: this.getSupplierLotNo(),
        sample_api: false,
        sample_excipient: String(report['material_type'] || '').toLowerCase().includes('raw'),
        sample_in_process: false,
        sample_finished: false,
        sample_stability: false,
        sample_product_development: false,
        sample_packaging_component: String(report['material_type'] || '').toLowerCase().includes('packing'),
        sample_other: false,
        sample_other_specify: '',
        stability_time_1m: false,
        stability_time_2m: false,
        stability_time_3m: false,
        stability_time_6m: false,
        stability_time_9m: false,
        stability_time_12m: false,
        stability_time_18m: false,
        stability_time_24m: false,
        stability_time_36m: false,
        stability_time_other: '',
        storage_long_term: false,
        storage_intermediate: false,
        storage_accelerated: false,
        test_performed: '',
        test_method_no: '',
        test_method_title: '',
        description_of_occurrence: '',
        other_impacted_lots: '',
        specification_or_limits: ''
      },
      this.getSavedLirForm()
    );

    if (!this.lirFormModel.lot_number) {
      this.lirFormModel.lot_number = this.resolveLotNumber(report);
    }
    if (!this.lirFormModel.product_material_code) {
      this.lirFormModel.product_material_code = report['material_code'] || '';
    }
    if (!this.lirFormModel.product_material_name) {
      this.lirFormModel.product_material_name = report['material_name'] || '';
    }
    if (!this.lirFormModel.supplier_manufacturer_name) {
      this.lirFormModel.supplier_manufacturer_name = report['vendor_name'] || '';
    }
    if (!this.lirFormModel.supplier_manufacturer_lot) {
      this.lirFormModel.supplier_manufacturer_lot = this.getSupplierLotNo();
    }
    if (!this.lirFormModel.expiry_retest_bulk_hold_date) {
      this.lirFormModel.expiry_retest_bulk_hold_date = this.getExpiryDate();
    }
  }

  saveNcr(ncrForm) {
    if (!ncrForm.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    if (!this.ncrFormModel.deviation_classification) {
      alertify.error('Please select Classification of Deviation');
      return;
    }

    const hasCategory =
      this.ncrFormModel.category_facility ||
      this.ncrFormModel.category_equipment ||
      this.ncrFormModel.category_material ||
      this.ncrFormModel.category_product ||
      this.ncrFormModel.category_process ||
      this.ncrFormModel.category_analytical ||
      this.ncrFormModel.category_document_handling ||
      this.ncrFormModel.category_sops ||
      this.ncrFormModel.category_other;

    if (!hasCategory) {
      alertify.error('Please select at least one Category');
      return;
    }

    if (this.ncrFormModel.category_other && !String(this.ncrFormModel.category_other_specify || '').trim()) {
      alertify.error('Please specify Other category');
      return;
    }

    const isUpdate = this.hasSavedNcr();
    this.persistDamageForm('NCR', this.ncrFormModel).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          this.syncSavedFormToReport('NCR', this.ncrFormModel, response.damage_details);
          alertify.success(isUpdate ? 'NCR form updated successfully' : 'NCR form saved successfully');
          this.closeRaiseForm();
        } else {
          alertify.error('Failed to save NCR form');
        }
      },
      () => alertify.error('Failed to save NCR form')
    );
  }

  saveLir(lirForm) {
    if (!lirForm.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const hasIncident =
      this.lirFormModel.incident_oos ||
      this.lirFormModel.incident_oot ||
      this.lirFormModel.incident_analytical_deviation;
    if (!hasIncident) {
      alertify.error('Please select at least one Incident Category');
      return;
    }

    const hasSampleType =
      this.lirFormModel.sample_api ||
      this.lirFormModel.sample_excipient ||
      this.lirFormModel.sample_in_process ||
      this.lirFormModel.sample_finished ||
      this.lirFormModel.sample_stability ||
      this.lirFormModel.sample_product_development ||
      this.lirFormModel.sample_packaging_component ||
      this.lirFormModel.sample_other;
    if (!hasSampleType) {
      alertify.error('Please select at least one Sample Type');
      return;
    }

    if (this.lirFormModel.sample_other && !String(this.lirFormModel.sample_other_specify || '').trim()) {
      alertify.error('Please specify Other sample type');
      return;
    }

    const isUpdate = this.hasSavedLir();
    this.persistDamageForm('LIR', this.lirFormModel).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          this.syncSavedFormToReport('LIR', this.lirFormModel, response.damage_details);
          alertify.success(isUpdate ? 'LIR form updated successfully' : 'LIR form saved successfully');
          this.closeRaiseForm();
        } else {
          alertify.error('Failed to save LIR form');
        }
      },
      () => alertify.error('Failed to save LIR form')
    );
  }

  ipqaRemark = 'Approve';

  update(data, status) {
    if (this.ipqaRemark != 'Approve') {
      this.minusDamageContainer();
    }

    if (this.ipqaRemark == 'Saparate Sampling/Testing') {
      this.batches['total_containers'] = this.selectedReport['outer_damage'];
      this.batches['qty_received'] = this.selectedReport['hold_qty'];
    }

    if (!data.valid) {
      alertify.error('All Field Required');
      return;
    }

    let temp = {};

    if (this.ipqaRemark == 'Saparate Sampling/Testing') {
      temp['batches'] = this.batches;
    }

    if (!this.selectedReport['damage_details'] || typeof this.selectedReport['damage_details'] !== 'object') {
      this.selectedReport['damage_details'] = {};
    }
    this.selectedReport['damage_details'].containers = this.damages;
    temp['damage_details'] = { ...this.selectedReport['damage_details'] };
    temp['ipqaRemark'] = this.ipqaRemark;

    this.service.post('store/raw.php?type=updateDamageInspection&status=' + status + '&id=' + this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.closeRaiseForm();
        this.getDamageContainerIpqa();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  minusDamageContainer() {
    let temp = {};
    temp['newQty'] = Number(this.selectedReport['qty']) - Number(this.selectedReport['hold_qty']);
    temp['newContainerQty'] = Number(this.selectedReport['containers']) - Number(this.selectedReport['outer_damage']);

    this.service.post('store/raw.php?type=updateSampllingData&id=' + this.batches['batch_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Stock Deducted From Received Stock');
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
