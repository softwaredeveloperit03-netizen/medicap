import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { ReactiveFormsModule,FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';

declare let alertify;

@Component({
  selector: 'app-sample',
  templateUrl: './sample.component.html',
  styleUrls: ['./sample.component.css']
})
export class SampleComponent implements OnInit {

  isNew = false;
  retestMode = false;
  listCloseRoute = '/qc/sampling/raw';
  activityListTitle = 'Raw Material Sampling Activity';
  formTitle = 'Raw Material Sampling Form';
  searchQuery = '';
  private allRetestResults: any[] = [];
  results;
  material_type = 'Raw Material';
  undertest_qty=0;
  selectedSampling:any = [];
  index:any=[];
  units;
  laminars;
  sampling;
  sampleData;
  isStart = false;
  start_date;
  stop_date;
  identication_qty:any;
  actual_indentification:any;
  withdrawal_identication:any;
  actual_composite:any;
  laf_start_date;
  laf_stop_date;
  isStop = false;
  isStopLAF = false;
  infoForm: FormGroup;
  checkListForm: FormGroup;
  samplingData;
spec_tests;
additional_qty = 0;


isCurrentDate(date: string | Date): boolean {
  const today = new Date();
  const givenDate = new Date(date);
  return today.toDateString() === givenDate.toDateString();
}



  checklist=[
    { observation: 'All are the containers properly segregated?', check:''},
    { observation: 'All are the containers properly Labeled (Supplier/Mfg., Approved label, Quarantine Label?', check:''},
    { observation: 'Is the information given on Quarantine label as per GRN?', check:''},
    { observation: 'Are there any damage/leakage/outer seals intact of the Drums/Containers/Bags?', check:''},
    { observation: 'Is sampling area properly cleaned', check:''},
    { observation: 'Is there any extraneous material observed on surface of Polybags', check:''},
    { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check:''},
    { observation: 'Whether “Sampled” labels with Container No. are affix on polybags/Bottles containing sample?', check:''},
    { observation: 'Whether “Sample for Analysis” labels with container No. are affix on polybags/Bottles containing sample?', check:''},
    { observation: 'Whether the used sampling tools are kept in Polybags and closed properly for transferring it to laboratory for cleaning?', check:''},
    { observation: 'Are all the safety precautions have been taken during Sampling?', check:''},
    { observation: 'Are all the containers taken for sampling kept back to designated places?', check:''},
    { observation: 'Whether tanker number mentioned in tanker receipt intimation is matched with tanker to be sampled?', check:''},
    { observation: 'Are the entire compartment sealed of tanker?', check:''},
    { observation: 'Is the tanker cleaning record available with tanker?', check:''},
  ];
  composite_qty: any;
  indentification_qty: any;
  reserve_composite: any;
  Complete_Analysis: any;
  withdrawal_composite: any;
  checkPointData: any;
  total_qty1: any;
  reserve_qty1: any;






  constructor(private service: DataAccessService, private route: ActivatedRoute) { }

  ngOnInit() {
    this.retestMode = !!this.route.snapshot.data['retestMode'];
    const grn = this.route.snapshot.queryParamMap.get('grn_no');
    if (grn) {
      this.searchQuery = grn;
    }
    if (this.retestMode) {
      this.listCloseRoute = '/qc/sampling/retest';
      this.activityListTitle = 'Retest Sampling Activity';
      this.formTitle = 'Retest Sampling Form';
      this.getRetestPendingSamplingForm();
    } else {
      this.getPendingSamplingForm();
    }
    this.getUnits();
    this.getCheckPointData();
    this.getsampler();
  }
  SamplingEquipment;


  getCheckPointData(){
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Sampling&form=Sampling Form').subscribe(response => {
     this.checkPointData = response;
   });
 }
 getSamplingEquipment(){
    this.service.get('qc/sampling/raw.php?type=getSamplingEquipment').subscribe(response => {
     this.SamplingEquipment = response;
   });
 }



 checkUnit(value,index){
  if(isNaN(value)){
    alertify.error('Please Enter Numeric Value');
    this.selectedSampling['container_details'][index].container_no = '';
  }
  if(this.sample_unit == " "){
    alertify.error('Please Select Sampling Unit');
    this.selectedSampling['container_details'][index].container_no = '';
  }

 }






  equipments: any[] = [];
  getsampler() {
    this.service.get('common.php?type=get_Equipments_sampling&depart=Quality%20Control&eq_type=Sampling%20Equipment').subscribe((response: any) => {
      this.equipments = Array.isArray(response) ? response : [];
    });
  }
  getPendingSamplingForm() {
    this.service.get('qc/sampling/raw.php?type=getPendingSamplingForm&material_type=' + encodeURIComponent(this.material_type)).subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.applyListFilter();
    });
  }

  getRetestPendingSamplingForm() {
    this.service.getJsonArray('qc/retest.php?type=getAwaitingSamplingRetests').subscribe({
      next: (response) => {
        const list = Array.isArray(response) ? response : [];
        this.allRetestResults = list.map((row) => this.normalizeRetestSamplingRow(row));
        this.applyListFilter();
      },
      error: () => {
        this.allRetestResults = [];
        this.results = [];
      },
    });
  }

  applyListFilter() {
    if (!this.retestMode) {
      return;
    }
    const q = (this.searchQuery || '').trim().toUpperCase();
    if (!q) {
      this.results = [...this.allRetestResults];
      return;
    }
    this.results = this.allRetestResults.filter((row: any) => {
      return (
        (row.material_code || '').toString().toUpperCase().includes(q) ||
        (row.material_name || '').toString().toUpperCase().includes(q) ||
        (row.grn_no || '').toString().toUpperCase().includes(q) ||
        (row.batch_no || '').toString().toUpperCase().includes(q) ||
        (row.ar_no || '').toString().toUpperCase().includes(q)
      );
    });
  }

  private normalizeRetestSamplingRow(row: any): any {
    if (!row || typeof row !== 'object') {
      return row;
    }
    const materialType = (row.material_type || 'Raw Material').toString().trim();
    return {
      ...row,
      material_type: materialType,
      grn_grade: row.grn_grade || row.grade || 'NA',
      grn_date: row.grn_date || row.entry_date || row.request_date || '',
      allocation_date: row.allocationOn || row.alloocationOn || row.entry_date || '',
      sampling_person: row.sampling_person || row.samplngPersonName || row.sampledBy || '',
      entry_by: row.entry_by || row.sampledBy || '',
    };
  }

  reloadPendingList() {
    if (this.retestMode) {
      this.getRetestPendingSamplingForm();
      return;
    }
    this.getPendingSamplingForm();
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  areaCleaningAgents: any[] = [];

  view(index) {
    this.selectedSampling = this.results[index];
    if (this.retestMode && this.selectedSampling?.material_type) {
      this.material_type = this.selectedSampling.material_type;
    }
     this.isNew = true;
    this.areaCleaningAgents = this.parseAgents(this.selectedSampling?.['cleaningAgentsUsed']);
    this.getSpecifications();
  }

  private parseAgents(raw: any): any[] {
    if (!raw) {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    }
    return [];
  }


  getSpecifications() {
    this.service.get('qc/specification/raw.php?type=getSpecificationByProduct&material_code=' + this.selectedSampling['material_code']).subscribe(response => {
      this.samplingData = response || {};
      this.calculation();
    });
  }

  private toNum(value: any, fallback = 0): number {
    const n = Number.parseFloat((value ?? '').toString().trim());
    return Number.isFinite(n) ? n : fallback;
  }

  private firstNonEmpty(...values: any[]): string {
    for (const v of values) {
      const s = (v ?? '').toString().trim();
      if (s) {
        return s;
      }
    }
    return '';
  }

  /** Spec unit, then sampling/material unit from the pending form. */
  private resolveSampleUnit(): string {
    return this.firstNonEmpty(
      this.sample_unit,
      this.samplingData?.unit,
      this.samplingData?.samplingUnit,
      this.selectedSampling?.sample_unit,
      this.selectedSampling?.samplingUnit,
      this.selectedSampling?.baseUnit,
      this.selectedSampling?.unit
    );
  }

  /** Retest sampling: read qty from approved specification (sample_qty or retest-applicable tests). */
  private resolveRetestQtyFromSpec(): number {
    const data = this.samplingData || {};
    const direct = this.toNum(data['RetestQty'] ?? data['retest_qty'], NaN);
    if (Number.isFinite(direct) && direct > 0) {
      return this.round2(direct);
    }
    const tests = Array.isArray(data['tests']) ? data['tests'] : [];
    let sum = 0;
    for (const test of tests) {
      if (String(test?.retest || '').trim().toLowerCase() === 'applicable') {
        sum += this.toNum(test?.sample_qty, 0);
      }
    }
    if (sum > 0) {
      return this.round2(sum);
    }
    return this.round2(this.toNum(data['sample_qty'], 0));
  }

  private applySampleUnit(unit: string): void {
    this.sample_unit = unit || ' ';
    if (this.selectedSampling) {
      this.selectedSampling['sample_unit'] = unit;
    }
    const containers = this.selectedSampling?.container_details || [];
    for (let i = 0; i < containers.length; i++) {
      containers[i].unit = unit;
    }
    for (const row of this.composite_sample_data || []) {
      row.unit = unit;
    }
    for (const row of this.retenation_sample_data || []) {
      row.unit = unit;
    }
    for (const row of this.micro_sample_data || []) {
      row.unit = unit;
    }
  }

  private round2(n: number): number {
    return Math.round((n + Number.EPSILON) * 100) / 100;
  }

  private computeContainersToSample(totalContainers: number): number {
    const N = Math.max(0, Math.floor(totalContainers || 0));
    if (N <= 0) return 0;
    if (this.Sampling_criteria === '100%') return N;
    // default: √n + 1
    return Math.min(N, Math.ceil(Math.sqrt(N) + 1));
  }

  /** Planned containers for the list screen — sampling_containers only exists once the form is opened. */
  containersToBeSampled(result: any): number {
    return this.computeContainersToSample(this.toNum(result?.containers, 0));
  }

  private recalcKgEquivalent(): void {
    const density = 1.08;
    if (this.sample_unit === 'gm') {
      this.undertest_qty_kg = this.round2(this.undertest_qty / 1000);
    } else if (this.sample_unit === 'Nos' || this.sample_unit === 'Kg') {
      this.undertest_qty_kg = this.round2(this.undertest_qty);
    } else if (this.sample_unit === 'ml') {
      const quantity_liters = this.undertest_qty / 1000;
      this.undertest_qty_kg = this.round2(quantity_liters * density);
    } else if (this.sample_unit === 'Ltr') {
      this.undertest_qty_kg = this.round2(this.undertest_qty * density);
    } else {
      this.undertest_qty_kg = this.round2(this.undertest_qty);
    }
  }


  Sampling_criteria = '√n +1';
  reserve_qty;

 composite_sample_data =[]
 retenation_sample_data =[]
 micro_sample_data = []
 micro_qty = 0;

 sample_unit = " ";

  which_one = '';
 any_discripancy = 'NO';
 undertest_qty_kg=0;
 dicripancy_remark = '';
 samplerId = '';
 balance_eqip_code = '';
 plant_id: any = '';
 inspectionReportComment = '';
 physicalObservation = '';
 appearance = '';
 presenceOfForeignParticles = '';
 perfumeOderVerification = '';
 samplingRemark = '';

 totalsample_qty = 0;
  retest_qty = 0;
  identification_total_qty = 0;

  recalculateAll(): void {
    this.calculation();
  }

  calculation() {
    const totalContainers = this.toNum(this.selectedSampling?.containers ?? this.samplingData?.chemical_qty, 0);
    const sampling_containers = this.computeContainersToSample(totalContainers);
    this.selectedSampling['sampling_containers'] = sampling_containers;

    this.composite_sample_data = [];
    this.retenation_sample_data = [];
    this.micro_sample_data = [];

    // values from specification: identification = per container (Qty in g), others as totals
    this.indentification_qty = this.toNum(this.samplingData?.indentification_qty, 0);
    if (this.retestMode) {
      this.reserve_composite = 0;
      this.retest_qty = this.resolveRetestQtyFromSpec();
      this.Complete_Analysis = 0;
      this.micro_qty = 0;
      this.indentification_qty = 0;
      this.additional_qty = this.toNum(this.samplingData?.additional_sample, 0);
    } else {
      this.reserve_composite = this.toNum(this.samplingData?.control_sample, 0);
      const completeAnalysis = this.toNum(this.samplingData?.composite ?? this.samplingData?.sample_qty ?? this.samplingData?.Complete_Analysis, 0);
      this.Complete_Analysis = completeAnalysis;
      this.micro_qty = this.toNum(this.samplingData?.micro_qty, 0);
      this.retest_qty = this.toNum(this.samplingData?.RetestQty ?? this.samplingData?.retest_qty, 0);
      this.additional_qty = this.toNum(this.samplingData?.additional_sample, 0);
    }

    this.identification_total_qty = this.round2(this.indentification_qty * sampling_containers);
    this.totalsample_qty = this.round2(this.Complete_Analysis + this.retest_qty + this.reserve_composite + this.additional_qty);

    const sampleUnit = this.resolveSampleUnit();
    this.selectedSampling['sample_unit'] = sampleUnit;
    this.sample_unit = sampleUnit || ' ';

    // per container withdrawal for (Comp + Control + Addi.)
    this.withdrawal_composite = sampling_containers > 0 ? this.round2(this.totalsample_qty / sampling_containers) : 0;

    const prev = (this.selectedSampling?.container_details || []) as any[];
    const containers: any[] = [];
    for (let i = 0; i < sampling_containers; i++) {
      const prevRow = prev[i] || {};
      const temp: any = {};
      temp['container_no'] = prevRow.container_no || '';
      temp['identication_qty'] = this.round2(this.indentification_qty);
      temp['withdrawal_composite'] = this.round2(this.withdrawal_composite);
      temp['total_qty'] = this.round2(temp['identication_qty'] + temp['withdrawal_composite']).toFixed(2);
      // Checking/approval screens read the withdrawn quantity as sample_qty.
      temp['sample_qty'] = temp['total_qty'];
      temp['unit'] = sampleUnit;
      temp['status'] = prevRow.status || 'pending';
      temp['remark'] = prevRow.remark || '';
      containers.push(temp);
      this.total_qty1 = temp['total_qty'];
    }

    // Total to be withdrawn from containers = (Identification per container * sampled containers) + (Comp+Control+Addi.) + Micro
    this.undertest_qty = this.round2((this.indentification_qty * sampling_containers) + this.totalsample_qty + this.toNum(this.micro_qty, 0));
    this.recalcKgEquivalent();

    // Composite sample + Additional sample tables are for recording (not for withdrawal math)
    const prevComp = (this.composite_sample_data?.[0] || {}) as any;
    const prevAdd = (this.retenation_sample_data?.[0] || {}) as any;
    const prevMicro = (this.micro_sample_data?.[0] || {}) as any;
    const temp1: any = { ccontainer_no: prevComp.ccontainer_no || '', per_contener: 0, sample_qty: this.round2(this.Complete_Analysis), unit: sampleUnit, status: prevComp.status || 'pending', remark: prevComp.remark || '' };
    const temp2: any = { rcontainer_no: prevAdd.rcontainer_no || '', per_contener: 0, sample_qty: this.round2(this.additional_qty), unit: sampleUnit, status: prevAdd.status || 'pending', remark: prevAdd.remark || '' };
    const temp3: any = { mcontainer_no: prevMicro.mcontainer_no || '', per_contener: 0, sample_qty: this.round2(this.micro_qty), unit: sampleUnit, status: prevMicro.status || 'pending', remark: prevMicro.remark || '' };
    this.composite_sample_data.push(temp1);
    this.retenation_sample_data.push(temp2);
    this.micro_sample_data.push(temp3);

    this.selectedSampling['container_details'] = containers;
    this.syncQuantitySampled();
  }




   addUnit(){
    this.applySampleUnit(this.resolveSampleUnit());
   }







   controlsamplereceive;
   retenations;
   compos
   ogsample;
   godLogic = false;

   calculatesample(){
    if (this.retestMode) {
      this.reserve_composite = 0;
      this.retest_qty = this.resolveRetestQtyFromSpec();
    }
    const sampling_containers = this.toNum(this.selectedSampling?.sampling_containers, 0);
    this.identification_total_qty = this.round2(this.toNum(this.indentification_qty, 0) * sampling_containers);
    this.totalsample_qty = this.round2(this.toNum(this.Complete_Analysis, 0) + this.toNum(this.retest_qty, 0) + this.toNum(this.reserve_composite, 0) + this.toNum(this.additional_qty, 0));
    this.withdrawal_composite = sampling_containers > 0 ? this.round2(this.totalsample_qty / sampling_containers) : 0;

    // update per-container rows
    const sampleUnit = this.resolveSampleUnit();
    this.applySampleUnit(sampleUnit);
    const containers = this.selectedSampling?.container_details || [];
    for (let i = 0; i < containers.length; i++) {
      containers[i].identication_qty = this.round2(this.toNum(this.indentification_qty, 0));
      containers[i].withdrawal_composite = this.round2(this.withdrawal_composite);
      containers[i].total_qty = this.round2(containers[i].identication_qty + containers[i].withdrawal_composite).toFixed(2);
      containers[i].sample_qty = containers[i].total_qty;
      containers[i].unit = sampleUnit;
    }

    // Total withdrawal = (identification per container * sampled containers) + totalsample_qty + micro
    this.undertest_qty = this.round2((this.toNum(this.indentification_qty, 0) * sampling_containers) + this.totalsample_qty + this.toNum(this.micro_qty, 0));
    if (this.micro_sample_data?.[0]) {
      this.micro_sample_data[0].sample_qty = this.round2(this.toNum(this.micro_qty, 0));
    }
    this.recalcKgEquivalent();
    this.syncQuantitySampled();
   }

  /** Header “Quantity Sampled” — planned withdrawal qty + UOM (saved sample_qty after submit). */
  private syncQuantitySampled(): void {
    if (!this.selectedSampling) {
      return;
    }
    const qty = this.firstNonEmpty(
      this.undertest_qty > 0 ? this.undertest_qty : '',
      this.totalsample_qty > 0 ? this.totalsample_qty : '',
      this.samplingData?.totalsample_qty,
      this.samplingData?.sample_qty,
      this.selectedSampling['sample_qty'],
      this.selectedSampling['sampleForTesting'],
      this.selectedSampling['qty_received']
    );
    if (qty !== '' && Number(qty) > 0) {
      this.selectedSampling['sample_qty'] = qty;
    }
  }






















  getLaminars() {
    this.service.get('qc/sampling.php?type=getLaminars').subscribe(response => {
      this.laminars = response;
    });
  }


  /** Local 'YYYY-MM-DDTHH:mm' — the format the checking/approval datetime-local inputs can render. */
  private formatDate(d: Date | undefined): string {
    if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
    const pad = (n: number) => (n < 10 ? '0' + n : '' + n);
    return (
      d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
      'T' + pad(d.getHours()) + ':' + pad(d.getMinutes())
    );
  }

  saveSamplingInfo(data, checklist) {
    const temp = data.value || {};
    if (!data.valid) {
      alertify.error('Please fill all required fields');
      return;
    }
    if (!this.samplerId) {
      alertify.error('Please select Sampling Equipment');
      return;
    }
    if (!this.stop_date) {
      alertify.error('Please stop sampling before submitting');
      return;
    }

    let flag = 0;
    const containers = this.selectedSampling['container_details'];
    for (let i = 0; i < containers.length; i++) {
      if (containers[i].status === 'pending') {
        flag = 1;
        break;
      }
    }

    if (flag === 1) {
      alertify.error('All containers sample required');
      return;
    }

    // Calculated and display values (sampling formula)
    temp['undertest_qty'] = this.undertest_qty;
    temp['undertest_qty_kg'] = this.undertest_qty_kg;
    temp['container_details'] = this.selectedSampling['container_details'];
    temp['checklist'] = this.checklist;
    temp['composite_sample_data'] = this.composite_sample_data;
    temp['retenation_sample_data'] = this.retenation_sample_data;
    temp['micro_sample_data'] = this.micro_sample_data;
    temp['micro_qty'] = this.micro_qty;
    temp['equipment_code'] = this.equipment_code;
    temp['checkPointData'] = this.checkPointData;

    // Sampling formula and plan
    temp['Sampling_criteria'] = this.Sampling_criteria;
    temp['sampling_containers'] = this.selectedSampling['sampling_containers'];
    temp['indentification_qty'] = this.indentification_qty;
    temp['identification_total_qty'] = this.identification_total_qty;
    temp['Complete_Analysis'] = this.Complete_Analysis;
    temp['retest_qty'] = this.retest_qty;
    temp['reserve_composite'] = this.reserve_composite;
    temp['additional_qty'] = this.additional_qty;
    temp['totalsample_qty'] = this.totalsample_qty;
    temp['withdrawal_composite'] = this.withdrawal_composite;
    temp['sample_unit'] = this.sample_unit;

    // Equipment and sampler
    temp['balance_eqip_code'] = this.balance_eqip_code || temp['balance_eqip_code'] || 0;
    temp['samplerId'] = temp['samplerId'] || this.samplerId || this.equipment_code || '';

    // Times (from component state)
    temp['start_time'] = this.formatDate(this.start_date) || temp['start_time'];
    temp['stop_date'] = this.formatDate(this.stop_date) || temp['stop_date'];
    temp['laf_start_date'] = this.formatDate(this.laf_start_date) || temp['laf_start_date'];
    temp['laf_stop_date'] = this.formatDate(this.laf_stop_date) || temp['laf_stop_date'];
    // Preserve the field names expected by qc/sampling/raw.php.
    temp['end_time'] = temp['stop_date'] || '';
    temp['LAF_start_date'] = temp['laf_start_date'] || this.selectedSampling['LAF_start_date'] || '';
    temp['LAF_stop_date'] = temp['laf_stop_date'] || '';

    // Identify the sampling row and carry the values required by the existing save API.
    temp['id'] = this.selectedSampling['id'];
    temp['specification_no'] = this.samplingData?.specification_no || this.selectedSampling['specification_no'] || '';
    temp['sampledContainers'] = this.selectedSampling['sampling_containers'];
    temp['sample_qty'] = this.undertest_qty;
    temp['samplingUnit'] = this.sample_unit;
    temp['convertedQtyToBaseUnit'] = this.undertest_qty_kg;
    temp['physicalObservation'] = this.physicalObservation || '';
    temp['appearance'] = this.appearance || '';
    temp['samplingRemark'] = this.samplingRemark || '';
    temp['presenceOfForeignParticles'] = this.presenceOfForeignParticles || '';
    temp['perfumeOderVerification'] = this.perfumeOderVerification || '';

    // Discrepancy and remarks
    temp['any_discripancy'] = this.any_discripancy;
    temp['dicripancy_remark'] = this.dicripancy_remark || '';
    temp['which_one'] = this.which_one;
    temp['inspectionReportComment'] = this.inspectionReportComment || '';

    // UI state for checking view
    temp['godLogic'] = this.godLogic;
    temp['plant_id'] = this.selectedSampling['plant_id'] != null ? this.selectedSampling['plant_id'] : (this.plant_id || '');

    this.service.post('qc/sampling/raw.php?type=saveSamplingInfo&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Information saved successfully');
        this.isNew = false;
        this.reloadPendingList();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  updateContainer(value,index) {


    if(value == 'one'){
      this.selectedSampling['container_details'][index].status = 'done';
    }
    if(value == 'two'){
      this.composite_sample_data[index].status = 'done';
    }
    if(value == 'three'){
      this.retenation_sample_data[index].status = 'done';
    }
    if(value == 'four'){
      this.micro_sample_data[index].status = 'done';
    }

  }
  equipment_code = '' ;
  updateSamplingTime(value) {
    // alert(111);
    // this.identication_qty = "qwerty"
    let noOfContainer = this.selectedSampling['sampling_containers'];
    console.log(noOfContainer);
    let specificationNo = this.selectedSampling['specification_no'];
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      /* this.start_time = h + ':' + m; */
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'stop') {
      this.stop_date = new Date();
      this.isStop = true;
    } else if (value == 'stoplaf') {
      this.laf_stop_date = new Date();
      this.isStopLAF = true;
    }


    let sampling_no = this.selectedSampling['sampling_no'];
    this.service.get('qc/sampling.php?type=getSamplingDetails&material_code='+this.selectedSampling['material_code']+'&sampling_no='+sampling_no).subscribe(response => {
      this.sampling = response[0];
      console.log(this.sampling.equipment_code)
      console.log(this.sampling['activity'].start_date);
       this.equipment_code = this.sampling.equipment_code;
      // load saved values if available (from getSamplingDetails)
      this.indentification_qty = this.toNum(this.sampling?.indentification_qty, this.indentification_qty);
      this.reserve_composite = this.toNum(this.sampling?.control_sample, this.reserve_composite);
      this.Complete_Analysis = this.toNum(this.sampling?.composite ?? this.sampling?.Composite, this.Complete_Analysis);
      this.retest_qty = this.toNum(this.sampling?.RetestQty ?? this.sampling?.retest_qty, this.retest_qty);
      this.additional_qty = this.toNum(this.sampling?.additional_sample, this.additional_qty);
      this.micro_qty = this.toNum(this.sampling?.micro_qty, this.micro_qty);
       if(this.reserve_composite == '') this.reserve_composite = 0 ;
       if(this.Complete_Analysis == '') this.Complete_Analysis = 0 ;

      // recalc cleanly using current rules
      this.calculatesample();
      // this.selectedSampling['reserve_qty'] =  this.reserve_composite;
      this.calculation();


    });
  }

  lafOtion="";



    sampling_containers1=0;


}
