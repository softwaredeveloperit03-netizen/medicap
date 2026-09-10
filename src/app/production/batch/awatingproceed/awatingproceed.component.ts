import { Component, OnInit,AfterViewInit  } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service';
import { BmrFormStorageService } from '../bmr-form-storage.service';

declare let alertify;
@Component({
  selector: 'app-awatingproceed',
  templateUrl: './awatingproceed.component.html',
  styleUrls: ['./awatingproceed.component.css'],
})
export class AwatingproceedComponent implements OnInit {
  today: Date = new Date();
  NULL = '';
  null = '';
  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private dataService: DataService,
    private bmrFormStorage: BmrFormStorageService
  ) {
    this.emp_id = localStorage.getItem('emp_id');
  }

  previousProduct;
  batchNoPrevious;
  lineClearanceDateTime;
  Remarks;
  emp_id;
  dry_shift_sample_dtl;
  dry_shift_Milling_dtl;
  dry_shift_sample_dtl_Equipment;
  dry_shift_Milling_dtl_Equipment;
  selectedResult: any = [];
  batch_size = 100000;

  /** eBMR payloads loaded from `ebmr_proceed_step_api.php` and merged into `Stages` for readonly views */
  private ebmrStepDataById: Record<string, unknown> = {};
  private ebmrSubstepDataByKey: Record<string, unknown> = {};

  product_code;
  work_order_no;
  rejectionQty = '';
  goodQty = '';
  LineClearanceChkPoints = [
    {
      id: 1,
      point: 'The area is cleaned, disinfected and fumigated as per schedule.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 2,
      point: 'Cleanliness of equipment.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 3,
      point:
        'Environmental conditions (Temperature/RH/DP) of area are within limit.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 4,
      point:
        'Equipment usage log, cleaning and sanitization logbook are maintained and updated.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 5,
      point: 'Batch record is completed till the activity.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 6,
      point:
        'Batch record, labels and materials related to previous batch are removed from area.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 7,
      point:
        'Calibration and validation status of all equipment’s are within validity periods.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 8,
      point: 'Calibration of all gauges are within the validity period.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 9,
      point:
        'The preventive maintenance of machine is completed as per predefined schedule.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 10,
      point:
        'The area and machine is cleared from previous batch of aluminum seals, rubber stoppers, broken Vials etc.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 11,
      point:
        'The O-RAB of Filling machine is ON and the pressure is within limit.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 12,
      point: 'The DPB is clean and working.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 13,
      point: 'Waste bin in the area is cleaned and “CLEANED” label is updated.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 14,
      point: 'Status labels in all the area are maintained.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 15,
      point:
        'The O-RAB of Sealing machine is ON and the pressure is within limit.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 16,
      point: 'Cleanliness of area.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 17,
      point: 'SS trays are cleaned properly.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
    {
      id: 18,
      point: 'Waste bin in the area is cleaned and “CLEANED” label affixed.',
      remark: '',
      checked_by: '',
      verified_by: '',
    },
  ];

  ABBRIVATIONS = [
    { key: 'DO', value: 'Dissolved Oxygen', selected: false },
    { key: 'API', value: 'Active Pharmaceutical Ingredients', selected: false },
    { key: 'PPM', value: 'Primary Packaging Material', selected: false },
    { key: 'RH', value: 'Relative Humidity', selected: false },
    { key: 'QA', value: 'Quality Assurance', selected: false },
    { key: 'QC', value: 'Quality Control', selected: false },
    { key: 'IPQA', value: 'In Process Quality Assurance', selected: false },
    { key: 'Medicap Lot No', value: 'Analytical Report Number', selected: false },
    { key: 'NLT', value: 'Not Less Than', selected: false },
    { key: 'NMT', value: 'Not More Than', selected: false },
    { key: 'COA', value: 'Certificate of Analysis', selected: false },
    { key: 'SOP', value: 'Standard Operating Procedure', selected: false },
    { key: 'CIP', value: 'Cleaning in Place', selected: false },
    { key: 'SIP', value: 'Sterilization in Place', selected: false },
    { key: 'NA', value: 'Not Applicable', selected: false },
    { key: 'DP', value: 'Differential Pressure', selected: false },
    { key: 'AR. No.', value: 'Analytical Report Number', selected: false },
    {
      key: 'BOPP',
      value: 'Bi-Axially Oriented Poly-Propylene',
      selected: false,
    },
    { key: 'BPR', value: 'Batch Packaging Record', selected: false },
    { key: 'Exp. Date', value: 'Expiry Date', selected: false },
    { key: 'UOM', value: 'Unit of Measurement', selected: false },
    { key: 'FGTN', value: 'Finished Good Transfer Note', selected: false },
    { key: 'Mfg.', value: 'Manufacturing', selected: false },
    { key: 'BP', value: 'British Pharmacopeia', selected: false },
    { key: 'IP', value: 'Indian Pharmacopeia', selected: false },
    { key: 'PR', value: 'Production', selected: false },
  ];

  ngOnInit(): void {
    // this.route.paramMap.subscribe(params => {
    //   this.getProcessStage(params.get('id'));
    // });
    this.getEmployeePacking();
    this.getEmployeeQualityAssurance();
    this.getEmployeeProduction();
    this.getAllMaterial();
    this.GetProductBatchNo();

    this.selectedResult = this.dataService.getData();
    this.route.paramMap.subscribe((params) => {
      this.getProcessStage(
        params.get('product_code'),
        this.selectedResult['work_order_no']
      );
      this.product_code = params.get('product_code');
      this.work_order_no = this.selectedResult['work_order_no'];
    });
    console.log('Service data found:', this.selectedResult);
    console.log('product_code', this.product_code);
    console.log('work_order_no', this.work_order_no);

    if (this.selectedResult == undefined) {
      this.router.navigate(['prod-f-ebmr/batch/awaiting']);
    }

    // this.getProcessStage(this.selectedResult['manufacturing_process_id']);
  }
  PackingEmployee;
  getEmployeePacking() {
    this.service
      .get('deptEmployee.php?type=getEmployeePacking')
      .subscribe((response) => {
        this.PackingEmployee = response;
      });
  }
  QualityAssuranceEmployee;
  getEmployeeQualityAssurance() {
    this.service
      .get('deptEmployee.php?type=getEmployeeQualityAssurance')
      .subscribe((response) => {
        this.QualityAssuranceEmployee = response;
      });
  }
  ProductionEmployee;
  getEmployeeProduction() {
    this.service
      .get('deptEmployee.php?type=getEmployeeProduction')
      .subscribe((response) => {
        this.ProductionEmployee = response;
      });
  }

  duration22: string = '';
  calculateDuration22(): void {
    if (this.cleaningFrom && this.cleaningTo) {
      const start = new Date(this.cleaningFrom);
      const end = new Date(this.cleaningTo);

      if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end > start) {
        const diffMs = end.getTime() - start.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const hours = Math.floor(diffMins / 60);
        const minutes = diffMins % 60;

        this.duration22 = `${hours}h ${minutes}m`;
      } else {
        this.duration22 = '';
      }
    } else {
      this.duration22 = '';
    }
  }
  stagemaster;
  Stages;
  materials;
  Products;
  isLoading = true;
  getAllMaterial() {
    this.service.get('common.php?type=getAllMaterial').subscribe((response) => {
      this.materials = response;
    });
  }
  GetProductBatchNo() {
    this.service
      .get('common.php?type=GetProductBatchNo')
      .subscribe((response) => {
        this.Products = response;
      });
  }

  selectedProduct = [];
  BatchNos = [];
  getProductBatchNos(i) {
    this.selectedProduct = this.Products[i - 1];
    this.BatchNos = this.selectedProduct['batch_no'];
    console.log('selectedProduct :>> ', this.selectedProduct);
    console.log('BatchNos :>> ', this.BatchNos);
  }

  /**
   * Helper method to filter null/undefined values from Steps array
   */
  private filterNullSteps(stages: any[]): any[] {
    if (!stages || !Array.isArray(stages)) {
      return [];
    }
    return stages.map((stage: any) => {
      if (stage && stage.Steps && Array.isArray(stage.Steps)) {
        stage.Steps = stage.Steps.filter((step: any) => step !== null && step !== undefined);
      } else if (stage) {
        stage.Steps = [];
      }
      return stage;
    });
  }

  /**
   * Print selected stage and step data on dropdown selection
   */
  printSelectedData(): void {
    console.log('========== SELECTED STAGE DATA ==========');
    console.log('Stage Index:', this.StagessIndex);
    console.log('Stage Number:', this.StagessIndexNumber);
    
    if (this.selectedStage) {
      console.log('Stage Name:', this.selectedStage['stages']);
      console.log('Stage ID:', this.selectedStage['id']);
      console.log('Stage Manufacturing Process ID:', this.selectedStage['manufacturing_process_id']);
      console.log('Stage Plant ID:', this.selectedStage['plant_id']);
      console.log('Stage Entry By:', this.selectedStage['entry_by']);
      console.log('Stage Entry Date:', this.selectedStage['entry_date']);
      console.log('Stage Check By:', this.selectedStage['check_by']);
      console.log('Stage Check Date:', this.selectedStage['check_date']);
      console.log('Stage Production Office:', this.selectedStage['Production_Office']);
      console.log('Stage Alternate Officer:', this.selectedStage['Alternate_Officer']);
      console.log('Stage Supervisor:', this.selectedStage['Supervisor']);
      console.log('Stage Remark:', this.selectedStage['remark']);
      console.log('Stage Department:', this.selectedStage['for_department']);
      console.log('Stage Forms List:', this.selectedStage['forms_list']);
      console.log('Total Steps in Stage:', this.selectedStageStepsLength);
      
      // Print all Steps array to see what's inside
      console.log('========== ALL STEPS IN STAGE ==========');
      if (this.selectedStage['Steps'] && Array.isArray(this.selectedStage['Steps'])) {
        console.log('Steps Array Length:', this.selectedStage['Steps'].length);
        this.selectedStage['Steps'].forEach((step: any, index: number) => {
          console.log(`Step[${index}]:`, step);
          if (step === null || step === undefined) {
            console.warn(`⚠️ Step[${index}] is NULL/UNDEFINED`);
          } else {
            console.log(`  - Step Name: ${step['step'] || 'N/A'}`);
            console.log(`  - Step ID: ${step['id'] || 'N/A'}`);
            console.log(`  - Step Data: ${step['data'] || 'N/A'}`);
          }
        });
      } else {
        console.warn('⚠️ Steps array is not an array or does not exist');
        console.log('Steps value:', this.selectedStage['Steps']);
      }
      console.log('========================================');
    }
    
    console.log('========== SELECTED STEP DATA ==========');
    console.log('Step Index:', this.stepsss_index);
    console.log('Step Name:', this.selectedSteps);
    console.log('Step ID:', this.selectedStepsID);
    console.log('Step Data:', this.selectedStepsdatas);
    
    if (this.selectedStepsData) {
      console.log('Full Step Data Object:', JSON.stringify(this.selectedStepsData, null, 2));
      
      // Print specific step data fields
      if (this.selectedStepsData['procedures']) {
        console.log('Step Procedures:', this.selectedStepsData['procedures']);
      }
      if (this.selectedStepsData['Dispensing']) {
        console.log('Step Dispensing Data:', this.selectedStepsData['Dispensing']);
      }
      if (this.selectedStepsData['equipment']) {
        console.log('Step Equipment:', this.selectedStepsData['equipment']);
      }
      if (this.selectedStepsData['CleaningChecks']) {
        console.log('Step Cleaning Checks:', this.selectedStepsData['CleaningChecks']);
      }
      if (this.selectedStepsData['room']) {
        console.log('Step Room Data:', this.selectedStepsData['room']);
      }
      if (this.selectedStepsData['weighing']) {
        console.log('Step Weighing Data:', this.selectedStepsData['weighing']);
      }
      if (this.selectedStepsData['instructions']) {
        console.log('Step Instructions:', this.selectedStepsData['instructions']);
      }
      if (this.selectedStepsData['step_status']) {
        console.log('Step Status:', this.selectedStepsData['step_status']);
      }
      if (this.selectedStepsData['bmr_procedures']) {
        console.log('BMR Procedures:', this.selectedStepsData['bmr_procedures']);
      }
      if (this.selectedStepsData['bmr_equipment']) {
        console.log('BMR Equipment:', this.selectedStepsData['bmr_equipment']);
      }
      if (this.selectedStepsData['Dispensning_lineclearance']) {
        console.log('Dispensing Line Clearance:', this.selectedStepsData['Dispensning_lineclearance']);
      }
      if (this.selectedStepsData['Dispensing']) {
        console.log('Dispensing Details:', this.selectedStepsData['Dispensing']);
      }
    }
    
    if (this.selectedStepsSubstep && this.selectedStepsSubstep.length > 0) {
      console.log('Step Substeps:', this.selectedStepsSubstep);
    }
    
    console.log('==========================================');
    
    // Also print to alertify for user visibility (optional)
    if (this.selectedStage && this.selectedSteps) {
      alertify.success(`Stage: ${this.selectedStage['stages']} | Step: ${this.selectedSteps}`);
    }
  }

  getProcessStage(product_code: string, work_order_no: string): void {
    let temp = {};
    temp['work_order_id'] = this.selectedResult['id'];
    temp['bfr_no'] = this.selectedResult['bfr_no'];
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['batch_number'] = this.selectedResult['batch_number'];
    this.isLoading = true;
    this.service
      .get(
        'bmr/process.php?type=getProcesses_BMRviewProceed&product_code=' +
          product_code +
          '&batch_number=' +
          this.selectedResult['batch_number'] +
          '&work_order_no=' +
          this.selectedResult['work_order_no']
      )
      .subscribe((response) => {
        this.stagemaster = response;

        if (this.stagemaster && this.stagemaster[0] && this.stagemaster[0]['Stages']) {
          this.Stages = this.filterNullSteps(this.stagemaster[0]['Stages']);
        } else {
          this.Stages = [];
        }

        this.loadEbmrSnapshotIntoCacheAfterStages();
      });
  }

  private normalizeEbmrPayload(d: unknown): any {
    if (d === null || d === undefined) {
      return '';
    }
    if (typeof d === 'string') {
      const t = d.trim();
      if (t === '') {
        return '';
      }
      try {
        return JSON.parse(t);
      } catch {
        return d;
      }
    }
    return d;
  }

  private patchAllStagesWithEbmrPayloads(): void {
    if (!this.Stages) {
      return;
    }
    for (const st of this.Stages) {
      if (!st || !st['Steps']) {
        continue;
      }
      for (const step of st['Steps']) {
        if (!step) {
          continue;
        }
        const sid = String(step['id']);
        if (Object.prototype.hasOwnProperty.call(this.ebmrStepDataById, sid)) {
          step['data'] = this.normalizeEbmrPayload(this.ebmrStepDataById[sid]);
        }
        const subs = step['Substeps'];
        if (subs && Array.isArray(subs)) {
          for (const sub of subs) {
            if (!sub) {
              continue;
            }
            const key = `${sid}__${String(sub['id'])}`;
            if (
              Object.prototype.hasOwnProperty.call(this.ebmrSubstepDataByKey, key)
            ) {
              sub['data'] = this.normalizeEbmrPayload(
                this.ebmrSubstepDataByKey[key]
              );
            }
          }
        }
      }
    }
  }

  private refreshVisibleStepBindingsAfterEbmrPatch(): void {
    if (!this.selectedStage || !this.selectedStage['Steps']) {
      return;
    }
    const cur = this.selectedStage['Steps'][this.stepsss_index];
    if (!cur) {
      return;
    }
    this.selectedStepsdatas =
      cur['data'] !== undefined && cur['data'] !== null ? cur['data'] : '';
  }

  /** Merge server-stored step JSON into `Stages` so `selectedStepsdatas != ''` shows readonly blocks */
  private loadEbmrSnapshotIntoCacheAfterStages(): void {
    this.ebmrStepDataById = {};
    this.ebmrSubstepDataByKey = {};
    if (!this.selectedResult) {
      this.isLoading = false;
      return;
    }
    this.bmrFormStorage
      .fetchServerSnapshot(
        this.selectedResult['product_code'],
        this.selectedResult['work_order_no'],
        this.selectedResult['batch_number'],
        this.selectedResult['product_name']
      )
      .subscribe((snap) => {
        if (snap) {
          for (const id of Object.keys(snap.steps)) {
            this.ebmrStepDataById[id] = snap.steps[id].data;
          }
          for (const k of Object.keys(snap.substeps)) {
            this.ebmrSubstepDataByKey[k] = snap.substeps[k].data;
          }
        }
        this.patchAllStagesWithEbmrPayloads();

        this.isLoading = false;

        if (this.StagessIndex !== -1 && this.Stages && this.Stages.length > 0) {
          this.StagessIndex = this.StagessIndex0;
          this.stepsss_index = this.stepsss_index0;
          if (
            this.Stages[this.StagessIndex] &&
            this.Stages[this.StagessIndex]['Steps'] &&
            this.Stages[this.StagessIndex]['Steps'].length > this.stepsss_index &&
            this.Stages[this.StagessIndex]['Steps'][this.stepsss_index]
          ) {
            this.selectedStage = this.Stages[this.StagessIndex];
            this.step_status =
              this.Stages[this.StagessIndex]['Steps'][this.stepsss_index][
                'step_status'
              ] || '';
            this.selectedStageStepsLength =
              this.Stages[this.StagessIndex]['Steps'].length;
            console.log('this.step_status :>> ', this.step_status);
          }
        }

        this.refreshVisibleStepBindingsAfterEbmrPatch();
      });
  }
  instructions;
  isEncapsule = false;
  selectedPage = 0;
  stepsss = false;
  selectPage(index, index1) {
    this.selectedPage = index;
    if (index == 13) {
      this.isEncapsule = true;
      console.log('hellllo');
    }

    this.stepsss = false;
  }
  selectedStage = [];

  bmr_rooms;

  bmrRoomList = [];

  bmractionRoomList = [];

  CleaStatusList = [];

  eqdatas2 = [];

  selectEquipments = [];
  stage_selected;

  newStageMaster = false;
  stageMaster() {
    this.newStageMaster = true;
  }

  ProcessTitle;
  DocumentNo;
  Forms;

  sections;

  selectedstep = [];
  selectedsteps = [];
  selectedStages = [];

  selectStage1 = [];
  selected_stage = [];
  selected_stages = [];

  completeMaster(status) {
    let temp = {};
    temp['product_code'] = this.selectedResult['product_code'];
    temp['status'] = status;
    temp['stages'] = this.Stages;
    this.service
      .post('bmr_new/bmr.php?type=complete_bmr_master', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.router.navigate(['/master']);

          alertify.success('SAVE');
        } else {
          alertify.error(response['msg']);
        }
      });
  }

  lineclearance: any = [];
  Dispensning_lineclearance: any = [];
  Dispensing: any = [];
  StagessIndexNumber: number;
  StagessIndex = -1;
  StagessIndex0 = -1;
  selectedStageStepsLength: number = 0;
  dispLine = 0;
  step_status = '';
  selectedSteps: any = [];
  selectedStepsSubstep: any = [];
  selectedStepsData: any = [];
  selectedStepsdatas: any = [];
  selectedStepsID: any;

  getStagessIndex(index: number): void {
    this.stepsss_index = 0;
    this.stepsss_index0 = 0;
    this.StagessIndexNumber = index;
    
    // Validate index and Stages array
    if (!this.Stages || index < 1 || index > this.Stages.length) {
      console.error('Invalid stage index:', index);
      return;
    }
    
    this.selectedStage = this.Stages[index - 1];
    
    if (!this.selectedStage) {
      console.error('Selected stage is null or undefined');
      return;
    }
    
    this.StagessIndex = this.StagessIndexNumber - 1;
    this.StagessIndex0 = this.StagessIndex;
    
    // Filter null values from Steps array
    if (this.selectedStage['Steps'] && Array.isArray(this.selectedStage['Steps'])) {
      this.selectedStage['Steps'] = this.selectedStage['Steps'].filter((step: any) => step !== null && step !== undefined);
    } else {
      this.selectedStage['Steps'] = [];
    }
    
    // Ensure stepsss_index is within bounds
    if (this.selectedStage['Steps'].length > 0) {
      this.stepsss_index = Math.min(this.stepsss_index, this.selectedStage['Steps'].length - 1);
      this.stepsss_index = Math.max(0, this.stepsss_index);
    } else {
      this.stepsss_index = 0;
    }
    
    this.selectedStageStepsLength = this.selectedStage['Steps'].length;
    console.log(
      'this.selectedStageStepsLength :>> ',
      this.selectedStageStepsLength
    );
    
    // Check if there are any steps before accessing
    if (this.selectedStageStepsLength > 0 && this.selectedStage['Steps'][this.stepsss_index]) {
      const currentStep = this.selectedStage['Steps'][this.stepsss_index];
      this.selectedSteps = currentStep['step'] || '';
      this.selectedStepsdatas = currentStep['data'] || '';
      this.selectedStepsID = currentStep['id'] || '';
      this.selectedStepsData = currentStep;
      this.selectedStepsSubstep = currentStep['Substeps'] || [];
    } else {
      // Initialize with empty values if no steps
      this.selectedSteps = '';
      this.selectedStepsdatas = '';
      this.selectedStepsID = '';
      this.selectedStepsData = null;
      this.selectedStepsSubstep = [];
    }
    
    console.log('this.selectedStepsData :>> ', this.selectedStepsData);
    console.log('this.selectedStepsSubstep :>> ', this.selectedStepsSubstep);
    
    // Print selected stage and step data
    this.printSelectedData();
    
    if (this.selectedSteps == 'Raw Material') {
      this.GET_RM_MFR(this.product_code);
    }
    // if(this.selectedSteps =='Persons involved' || this.selectedSteps =='PERSONS MAKING ENTRIES IN THE BATCH PACKING RECORD (BPR)'){
    this.GET_InvolvedPersons();
    // }
    if (this.selectedSteps == 'List of equipments used') {
      this.getEquipmentsUsed();
    }
    if (this.selectedSteps == 'List of equipments used') {
      this.getEquipmentsUsed();
    }
    if (this.selectedSteps == 'Dispensing of Raw Material') {
      this.getDispensingDetails('Raw Material');
    }
    if (this.selectedSteps == 'Dispensing of Raw Material') {
      this.getDispensingDetails('Raw Material');
    }
    if (this.selectedSteps == 'Dispensing of Primary Packing Material') {
      this.getDispensingDetails('Packing Material');
    }
    if (this.selectedSteps == 'Issuance of Consumables Material') {
      this.getConsumableBom(this.selectedResult['product_code']);
    }
    if (this.selectedSteps == 'Machine Part Cleaning Record') {
      this.getSparesParts();
    }
  }

  stepsss_index: number = 0;
  stepsss_index0: number = 0;
  PreviousStepss(): void {
    if (!this.selectedStage || !this.selectedStage['Steps'] || this.selectedStage['Steps'].length === 0) {
      console.error('No steps available');
      return;
    }
    
    this.stepsss_index = Math.max(0, this.stepsss_index - 1);
    this.stepsss_index0 = this.stepsss_index;
    
    const currentStep = this.selectedStage['Steps'][this.stepsss_index];
    if (currentStep) {
      this.selectedSteps = currentStep['step'] || '';
      this.selectedStepsSubstep = currentStep['Substeps'] || [];
      this.selectedStepsID = currentStep['id'] || '';
      this.selectedStepsdatas = currentStep['data'] || '';
      this.selectedStepsData = currentStep;
    } else {
      console.error('Step at index', this.stepsss_index, 'is null or undefined');
    }
    console.log('lineclearance :>> ', this.lineclearance);
    console.log('lineclearance :>> ', this.lineclearance);
    if (this.selectedSteps == 'Raw Material') {
      this.GET_RM_MFR(this.product_code);
    }
    // if(this.selectedSteps =='Persons involved' || this.selectedSteps =='PERSONS MAKING ENTRIES IN THE BATCH PACKING RECORD (BPR)'){
    this.GET_InvolvedPersons();
    // }
    if (this.selectedSteps == 'List of equipments used') {
      this.getEquipmentsUsed();
    }
    if (this.selectedSteps == 'Dispensing of Raw Material') {
      this.getDispensingDetails('Raw Material');
    }
    if (this.selectedSteps == 'Dispensing of Primary Packing Material') {
      this.getDispensingDetails('Packing Material');
    }
    if (this.selectedSteps == 'Issuance of Consumables Material') {
      this.getConsumableBom(this.selectedResult['product_code']);
    }
    if (this.selectedSteps == 'Machine Part Cleaning Record') {
      this.getSparesParts();
    }
  }
  NextStepss(): void {
    if (!this.selectedStage || !this.selectedStage['Steps'] || this.selectedStage['Steps'].length === 0) {
      console.error('No steps available');
      return;
    }
    
    console.log('stepsss_index :>> ', this.stepsss_index);
    const maxIndex = this.selectedStage['Steps'].length - 1;
    this.stepsss_index = Math.min(maxIndex, this.stepsss_index + Number(1));
    this.stepsss_index0 = this.stepsss_index;
    console.log('this.stepsss_index :>> ', this.stepsss_index);
    
    const currentStep = this.selectedStage['Steps'][this.stepsss_index];
    if (currentStep) {
      this.selectedSteps = currentStep['step'] || '';
      this.selectedStepsID = currentStep['id'] || '';
      this.selectedStepsdatas = currentStep['data'] || '';
      this.selectedStepsData = currentStep;
      this.selectedStepsSubstep = currentStep['Substeps'] || [];
    } else {
      console.error('Step at index', this.stepsss_index, 'is null or undefined');
    }
    console.log('this.selectedSteps :>> ', this.selectedSteps);
    console.log('this.selectedStepsData :>> ', this.selectedStepsData);
    console.log('this.selectedStepsSubstep :>> ', this.selectedStepsSubstep);
    console.log('lineclearance :>> ', this.lineclearance);
    if (this.selectedSteps == 'Raw Material') {
      this.GET_RM_MFR(this.product_code);
    }
    // if(this.selectedSteps =='Persons involved' || this.selectedSteps =='PERSONS MAKING ENTRIES IN THE BATCH PACKING RECORD (BPR)'){
    this.GET_InvolvedPersons();
    // }
    if (this.selectedSteps == 'List of equipments used') {
      this.getEquipmentsUsed();
    }
    if (this.selectedSteps == 'Dispensing of Raw Material') {
      this.getDispensingDetails('Raw Material');
    }
    if (this.selectedSteps == 'Dispensing of Primary Packing Material') {
      this.getDispensingDetails('Packing Material');
    }
    if (this.selectedSteps == 'Issuance of Consumables Material') {
      this.getConsumableBom(this.selectedResult['product_code']);
    }
    if (this.selectedSteps == 'Machine Part Cleaning Record') {
      this.getSparesParts();
    }
    console.log('selectedStepsSubstep :>> ', this.selectedStepsSubstep);
    console.log(
      'selectedStepsSubstep :>> ',
      this.selectedStage['Steps'][this.stepsss_index]
    );
  }

  save(step: string, stage: number, step_id: number): void {
    if (!this.selectedStage || !this.selectedStage['Steps'] || 
        !this.selectedStage['Steps'][this.stepsss_index]) {
      console.error('Cannot save: Step data is invalid');
      alertify.error('Cannot save: Step data is invalid');
      return;
    }
    
    this.step_status =
      this.selectedStage['Steps'][this.stepsss_index]['step_status'] || '';
    let temp = {};
    temp['step'] = step;
    temp['stage_id'] = stage;
    temp['step_id'] = step_id;

    if ((this.dispLine = 1)) {
      temp['lineclearance'] = this.Dispensning_lineclearance;
    } else {
      temp['lineclearance'] = this.lineclearance;
    }
    console.log('temp :>> ', temp);

    this.bmrFormStorage
      .saveFillStep(
        this.selectedResult['product_code'],
        this.selectedResult['work_order_no'],
        this.selectedResult['batch_number'],
        step_id,
        temp,
        step,
        this.selectedResult['product_name']
      )
      .subscribe({
        next: (ok) => {
          if (ok) {
            alertify.success('SAVE');
            this.getProcessStage(this.product_code, this.work_order_no);
          } else {
            alertify.error('Save failed (ebmr_filled_step_api.php)');
          }
        },
        error: (err) => {
          console.error('saveFillStep', err);
          alertify.error('Save failed (network or server error)');
        },
      });
  }

  E1 = 0;
  E2 = 0;
  E3 = 0;
  E4 = 0;
  F1 = 0;
  F2 = 0;
  E_Total = 0;
  F_Total = 0;

  calculateTotals() {
    const e1 = Number(this.E1) || 0;
    const e2 = Number(this.E2) || 0;
    const e3 = Number(this.E3) || 0;
    const e4 = Number(this.E4) || 0;

    const f1 = Number(this.F1) || 0;
    const f2 = Number(this.F2) || 0;

    this.E_Total = e1 + e2 + e3 + e4;
    this.F_Total = f1 + f2;
  }
  safetyInstructions = {
    inhaled: '',
    skinContact: '',
    eyeContact: '',
    swallowed: '',
  };
  FinalData: any = [];
  // If you want it inside an array:
  safetyArray: any[] = [this.safetyInstructions];

  //  submitSafetyMSDS() {
  //     // Always store only one object in the array
  //     this.safetyArray = [{ ...this.safetyInstructions }];
  //     console.log('Submitted Safety Instructions:', this.safetyArray);
  //     this.FinalData=this.safetyArray;
  //     console.log('Submitted FinalData:', this.FinalData);
  //   }
  rmlist;
  primary_pm_list: any = [];
  raw_materials: any = [];
  GET_RM_MFR(product_code) {
    this.service
      .get('bmr/process.php?type=GET_RM_MFR&product_code=' + product_code)
      .subscribe((response) => {
        this.rmlist = response[0];
        this.primary_pm_list = response[0]['primary_pm_list'];
        this.raw_materials = response[0]['raw_materials'];
      });
  }

  InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
  selectedInvolvedPersons: any = [];
  getInvolvedPersonsData(index) {
    this.selectedInvolvedPersons = this.InvolvedPersons[index - 1];
  }
  InvolvedPersonsList: any = [];
  Add_selectedInvolvedPersons() {
    let temp = this.selectedInvolvedPersons;
    this.InvolvedPersonsList[this.InvolvedPersonsList.length] = temp;
    console.log('this.InvolvedPersonsList :>> ', this.InvolvedPersonsList);
  }

  general_instruction = '';
  general_instructionData = [];
  addGeneralInstructions() {
    let temp = {};
    temp['general_instruction'] = this.general_instruction;
    this.general_instructionData[this.general_instructionData.length] = temp;
    this.general_instruction = '';
  }

  PrecautionDispensing = '';

  PrecautionDispensingData = [];
  addPrecautionDispensing() {
    let temp = {};
    temp['PrecautionDispensing'] = this.PrecautionDispensing;
    this.PrecautionDispensingData[this.PrecautionDispensingData.length] = temp;
    this.PrecautionDispensing = '';
  }

  equipmentsToBeUsed: any = [];
  getEquipmentsUsed() {
    this.service.get('common.php?type=getEquipments').subscribe((response) => {
      this.equipmentsToBeUsed = response;
    });
  }
  selectedEquipmentsUsed: any = [];
  getEquipmentsUsedData(index) {
    this.selectedEquipmentsUsed = this.equipmentsToBeUsed[index - 1];
  }
  EquipmentsUsedList: any = [];
  Add_selectedEquipmentsUsed() {
    // let temp =this.selectedEquipmentsUsed;
    // this.EquipmentsUsedList[this.EquipmentsUsedList.length]=temp;
    let temp = {};
    temp['make'] = this.selectedEquipmentsUsed['make'];
    temp['equipment_code'] = this.selectedEquipmentsUsed['equipment_code'];
    temp['equipment_name'] = this.selectedEquipmentsUsed['equipment_name'];
    this.EquipmentsUsedList[this.EquipmentsUsedList.length] = temp;
    console.log('this.EquipmentsUsedList :>> ', this.EquipmentsUsedList);
  }
  LineClearanceChkPointsList: any = [];
  AddLineClearanceChkPoints(data) {
    let temp = data.value;
    this.LineClearanceChkPointsList[this.LineClearanceChkPointsList.length] =
      temp;
    data.resetForm();
  }

  DispensingResultBOM;
  getDispensingDetails(material_type) {
    this.service
      .get(
        'store/dispensing.php?type=getDispensingDetails&material_type=' +
          material_type +
          '&id=' +
          this.selectedResult['id']
      )
      .subscribe((response) => {
        this.DispensingResultBOM = response;
      });
  }
  ConsumableResultBOM;
  getConsumableBom(product_code) {
    this.service
      .get(
        'production/unitformula.php?type=getConsumableBom&product_code=' +
          product_code
      )
      .subscribe((response) => {
        this.ConsumableResultBOM = response[0];
      });
  }
  unitPreparationPrecaution;
  unitPreparationPrecautionData = [];
  addunitPreparationPrecaution() {
    let temp = {};
    temp['general_instruction'] = this.unitPreparationPrecaution;
    this.unitPreparationPrecautionData[
      this.unitPreparationPrecautionData.length
    ] = temp;
    this.unitPreparationPrecaution = '';
  }

  spareParts;
  getSparesParts() {
    this.service.get('common.php?type=getSparesParts').subscribe((response) => {
      this.spareParts = response;
    });
  }
  MachinePartCleaningRecordData = [];
  addMachinePartCleaningRecord(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.MachinePartCleaningRecordData[
      this.MachinePartCleaningRecordData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.MachinePartCleaningRecordData :>> ',
      this.MachinePartCleaningRecordData
    );
  }
  thisUnitPreparationFormDataData: any = [];
  addUnitPreparationForm(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.thisUnitPreparationFormDataData[
      this.thisUnitPreparationFormDataData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.thisUnitPreparationFormDataData :>> ',
      this.thisUnitPreparationFormDataData
    );
  }
  cleaningFrom: string = '';
  cleaningTo: string = '';
  duration: string = '';

  calculateDuration() {
    if (this.cleaningFrom && this.cleaningTo) {
      const [fromHour, fromMin] = this.cleaningFrom.split(':').map(Number);
      const [toHour, toMin] = this.cleaningTo.split(':').map(Number);

      const fromDate = new Date(0, 0, 0, fromHour, fromMin);
      const toDate = new Date(0, 0, 0, toHour, toMin);

      let diffMs = toDate.getTime() - fromDate.getTime();

      if (diffMs < 0) {
        // Handle overnight duration (e.g., from 23:00 to 01:00)
        diffMs += 24 * 60 * 60 * 1000;
      }

      const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
      const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

      this.duration = `${this.padZero(diffHrs)}:${this.padZero(diffMins)}`;
    } else {
      this.duration = '';
    }
  }

  padZero(num: number): string {
    return num < 10 ? '0' + num : num.toString();
  }

  addFilterDetailsData = [];
  addFilterDetails(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.addFilterDetailsData[this.addFilterDetailsData.length] = temp;
    data.resetForm();
    console.log('this.addFilterDetailsData :>> ', this.addFilterDetailsData);
  }

  loadSterilizedMachinePartsData = [];
  addLoadSterilizedMachineParts(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.loadSterilizedMachinePartsData[
      this.loadSterilizedMachinePartsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.loadSterilizedMachinePartsData :>> ',
      this.loadSterilizedMachinePartsData
    );
  }
  rubberStopperSealSterilizationData = [];
  addRubberStopperSealSterilizationData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.rubberStopperSealSterilizationData[
      this.rubberStopperSealSterilizationData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.rubberStopperSealSterilizationData :>> ',
      this.rubberStopperSealSterilizationData
    );
  }

  addPostIntegrityFilterData = [];
  addPostIntegrityFilter(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.addPostIntegrityFilterData[this.addPostIntegrityFilterData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.addPostIntegrityFilterData :>> ',
      this.addPostIntegrityFilterData
    );
  }
  addDeCartonningVialSopData = [];
  addDeCartonningVialSop(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.addDeCartonningVialSopData[this.addDeCartonningVialSopData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.addDeCartonningVialSopData :>> ',
      this.addDeCartonningVialSopData
    );
  }
  AddapicalculationArDataFormData = [];
  AddapicalculationArDataForm(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.AddapicalculationArDataFormData[
      this.AddapicalculationArDataFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.AddapicalculationArDataFormData :>> ',
      this.AddapicalculationArDataFormData
    );
  }
  totalDeCartonningVialSopDatarejectionQty = 0;
  TotalsubmitTrayFormRejects = 0;

  getTotalRejects() {
    let totalRejects = 0;
    this.addDeCartonningVialSopData.forEach((item) => {
      if (item.rejectionQty) {
        totalRejects += Number(item.rejectionQty);
      }
    });
    this.totalDeCartonningVialSopDatarejectionQty = totalRejects;
    return totalRejects;
  }
  qtyTransferProdNos;

  loadingWashingInspectionData = [];
  addLoadingWashingInspectionData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.loadingWashingInspectionData[
      this.loadingWashingInspectionData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.loadingWashingInspectionData :>> ',
      this.loadingWashingInspectionData
    );
  }
  VialWashingData = [];
  addVialWashingData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.VialWashingData[this.VialWashingData.length] = temp;
    data.resetForm();
    console.log('this.VialWashingData :>> ', this.VialWashingData);
  }

  operatorSettingVialWashingData = [];
  addOperatorSettingVialWashingData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.operatorSettingVialWashingData[
      this.operatorSettingVialWashingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.operatorSettingVialWashingData :>> ',
      this.operatorSettingVialWashingData
    );
  }

  inprocessCheckAtEndsWashingData = [];
  addInprocessCheckAtEndsWashingData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.inprocessCheckAtEndsWashingData[
      this.inprocessCheckAtEndsWashingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.inprocessCheckAtEndsWashingData :>> ',
      this.inprocessCheckAtEndsWashingData
    );
  }
  washedVialInspectionData = [];
  addWashedVialInspectionData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.washedVialInspectionData[this.washedVialInspectionData.length] = temp;
    data.resetForm();
    console.log(
      'this.washedVialInspectionData :>> ',
      this.washedVialInspectionData
    );
  }
  inprocessAtEndWashingData = [];
  addInprocessAtEndWashingData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.inprocessAtEndWashingData[this.inprocessAtEndWashingData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.inprocessAtEndWashingData :>> ',
      this.inprocessAtEndWashingData
    );
  }
  reconcilationWashedVialsData = [];
  addReconcilationWashedVialsData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.reconcilationWashedVialsData[
      this.reconcilationWashedVialsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.reconcilationWashedVialsData :>> ',
      this.reconcilationWashedVialsData
    );
  }
  asepticPreparationHoldTimeData = [];
  addAsepticPreparationHoldTime(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.asepticPreparationHoldTimeData[
      this.asepticPreparationHoldTimeData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.asepticPreparationHoldTimeData :>> ',
      this.asepticPreparationHoldTimeData
    );
  }
  sterilizationAndDepyrogenationData = [];
  addSterilizationAndDepyrogenationData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.sterilizationAndDepyrogenationData[
      this.sterilizationAndDepyrogenationData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.sterilizationAndDepyrogenationData :>> ',
      this.sterilizationAndDepyrogenationData
    );
  }
  parameterData = [];
  addParameterData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.parameterData[this.parameterData.length] = temp;
    data.resetForm();
    console.log('this.parameterData :>> ', this.parameterData);
  }
  inprocessChecksDuringTunnelSterilizationVialData = [];
  addInprocessChecksDuringTunnelSterilizationData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.inprocessChecksDuringTunnelSterilizationVialData[
      this.inprocessChecksDuringTunnelSterilizationVialData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.inprocessChecksDuringTunnelSterilizationVialData :>> ',
      this.inprocessChecksDuringTunnelSterilizationVialData
    );
  }
  assemblingAndSealingProcessData = [];
  addAssemblingAndSealingProcessData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.assemblingAndSealingProcessData[
      this.assemblingAndSealingProcessData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.assemblingAndSealingProcessData :>> ',
      this.assemblingAndSealingProcessData
    );
  }
  ReconciliationAfterFillingData = [];
  addReconciliationAfterFilling(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.ReconciliationAfterFillingData[
      this.ReconciliationAfterFillingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ReconciliationAfterFillingData :>> ',
      this.ReconciliationAfterFillingData
    );
  }
  inProcessRecordVialSealingCheckData = [];
  addInProcessRecordVialSealingCheckData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.inProcessRecordVialSealingCheckData[
      this.inProcessRecordVialSealingCheckData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.inProcessRecordVialSealingCheckData :>> ',
      this.inProcessRecordVialSealingCheckData
    );
  }
  finishedProductSamplesData = [];
  addFinishedProductSamplesData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.finishedProductSamplesData[this.finishedProductSamplesData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.finishedProductSamplesData :>> ',
      this.finishedProductSamplesData
    );
  }
  leakTestData = [];
  addLeakTestData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.leakTestData[this.leakTestData.length] = temp;
    data.resetForm();
    console.log('this.leakTestData :>> ', this.leakTestData);
  }
  leakTestFilledSealedVialThroughtMachineData = [];
  addLeakTestFilledSealedVialThroughtMachineData(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.leakTestFilledSealedVialThroughtMachineData[
      this.leakTestFilledSealedVialThroughtMachineData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.leakTestFilledSealedVialThroughtMachineData :>> ',
      this.leakTestFilledSealedVialThroughtMachineData
    );
  }
  assemblingAndFillingProcessDetailsData = [];
  addAssemblingAndFillingProcessDetails(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.assemblingAndFillingProcessDetailsData[
      this.assemblingAndFillingProcessDetailsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.assemblingAndFillingProcessDetailsData :>> ',
      this.assemblingAndFillingProcessDetailsData
    );
  }
  fillWeightData = [];
  addFillWeight(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.fillWeightData[this.fillWeightData.length] = temp;
    data.resetForm();
    console.log('this.fillWeightData :>> ', this.fillWeightData);
  }
  initialChecksBeforeFillingData = [];
  addInitialChecksBeforeFilling(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.initialChecksBeforeFillingData[
      this.initialChecksBeforeFillingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.initialChecksBeforeFillingData :>> ',
      this.initialChecksBeforeFillingData
    );
  }
  FillWeightEntryData = [];
  InitialFillWeightRecordformData = [];
  addFillWeightEntry(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.FillWeightEntryData[this.FillWeightEntryData.length] = temp;
    data.resetForm();
    console.log('this.FillWeightEntryData :>> ', this.FillWeightEntryData);
  }
  AddInitialFillWeightRecordform(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.InitialFillWeightRecordformData[
      this.InitialFillWeightRecordformData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.InitialFillWeightRecordformData :>> ',
      this.InitialFillWeightRecordformData
    );
  }
  Vial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials =
    [];
  addVial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials(
    data
  ) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.Vial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials[
      this.Vial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.Vial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials :>> ',
      this
        .Vial_Washing_and_Depyrogenation_area_Inprocess_Checks_During_Vial_Washing_of_Vials
    );
  }
  InprocesschecksduringfillingandHalfstopperingasperSOPFormData = [];
  addInprocesschecksduringfillingandHalfstopperingasperSOPForm(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.InprocesschecksduringfillingandHalfstopperingasperSOPFormData[
      this.InprocesschecksduringfillingandHalfstopperingasperSOPFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.InprocesschecksduringfillingandHalfstopperingasperSOPFormData :>> ',
      this.InprocesschecksduringfillingandHalfstopperingasperSOPFormData
    );
  }
  fillingStopStartSheetData = [];
  addFillingStopStartSheetRow(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.fillingStopStartSheetData[this.fillingStopStartSheetData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.fillingStopStartSheetData :>> ',
      this.fillingStopStartSheetData
    );
  }
  ReconciliationaftersealingofvialsData = [];
  addReconciliationaftersealingofvialsForm(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.ReconciliationaftersealingofvialsData[
      this.ReconciliationaftersealingofvialsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ReconciliationaftersealingofvialsData :>> ',
      this.ReconciliationaftersealingofvialsData
    );
  }

  isDisabled = false;

  ReconciliationofDrypowderFormData = {
    rejection_a1_KG: '',
    rejection_a1_NOS: '',
    rejection_a2_KG: '',
    rejection_a2_NOS: '',
    rejection_a3_KG: '',
    rejection_a3_NOS: '',
    stage_b1_KG: '',
    stage_b1_NOS: '',
    stage_b2_KG: '',
    stage_b2_NOS: '',
    stage_b3_KG: '',
    stage_b3_NOS: '',
    stage_b4_KG: '',
    stage_b4_NOS: '',
    stage_b5_KG: '',
    stage_b5_NOS: '',
    stage_b6_KG: '',
    stage_b6_NOS: '',
    stage_b7_KG: '',
    stage_b7_NOS: '',
    stage_c_KG: '',
    stage_c_NOS: '',
    stage_d_KG: '',
    stage_d_NOS: '',
    stage_e_KG: '',
    stage_e_NOS: '',
  };

  onSave() {
    this.isDisabled = true;
    console.log(
      'this.ReconciliationofDrypowderFormData :>> ',
      this.ReconciliationofDrypowderFormData
    );
  }
  onEdit() {
    this.isDisabled = false;
  }
  DestructionRejectionDeviationHistoryFormData = [];
  addDestructionRejectionDeviationHistory(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.DestructionRejectionDeviationHistoryFormData[
      this.DestructionRejectionDeviationHistoryFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.DestructionRejectionDeviationHistoryFormData :>> ',
      this.DestructionRejectionDeviationHistoryFormData
    );
  }
  AttachmentsFormData = [];
  addAttachments(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.AttachmentsFormData[this.AttachmentsFormData.length] = temp;
    data.resetForm();
    console.log('this.AttachmentsFormData :>> ', this.AttachmentsFormData);
  }
  addSterilizationEntry(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.sterilizationEntries[this.sterilizationEntries.length] = temp;
    data.resetForm();
    console.log('this.sterilizationEntries :>> ', this.sterilizationEntries);
  }
  BPRpersonsList = [];
  addPerson(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.BPRpersonsList[this.BPRpersonsList.length] = temp;
    data.resetForm();
    console.log('this.BPRpersonsList :>> ', this.BPRpersonsList);
  }
  jobAllocationDataSheetFormData = [];
  addjobAllocationDataSheetForm(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.jobAllocationDataSheetFormData[
      this.jobAllocationDataSheetFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.jobAllocationDataSheetFormData :>> ',
      this.jobAllocationDataSheetFormData
    );
  }
  DISPENSING_INSTRUCTIONS_FormData = [];
  addDISPENSING_INSTRUCTIONS_Form(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.DISPENSING_INSTRUCTIONS_FormData[
      this.DISPENSING_INSTRUCTIONS_FormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.DISPENSING_INSTRUCTIONS_FormData :>> ',
      this.DISPENSING_INSTRUCTIONS_FormData
    );
  }
  secondaryPackagingMaterialDetails = [];
  addSecondaryPackagingMaterialDetail(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.secondaryPackagingMaterialDetails[
      this.secondaryPackagingMaterialDetails.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.secondaryPackagingMaterialDetails :>> ',
      this.secondaryPackagingMaterialDetails
    );
  }
  JustificationforadditionalmaterialIssuanceData = [];
  addJustificationforadditionalmaterialIssuance_Form(data) {
    console.log('data :>> ', data);
    let temp = data.value;
    this.JustificationforadditionalmaterialIssuanceData[
      this.JustificationforadditionalmaterialIssuanceData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.JustificationforadditionalmaterialIssuanceData :>> ',
      this.JustificationforadditionalmaterialIssuanceData
    );
  }
  excessMaterialRequisitionData = [];
  addExcessMaterialRequisition(data) {
    let temp = data.value;
    this.excessMaterialRequisitionData[
      this.excessMaterialRequisitionData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.excessMaterialRequisitionData :>> ',
      this.excessMaterialRequisitionData
    );
  }
  tempratureRecordLabellingFormData = [];
  addtempratureRecordLabellingForm(data) {
    let temp = data.value;
    this.tempratureRecordLabellingFormData[
      this.tempratureRecordLabellingFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.tempratureRecordLabellingFormData :>> ',
      this.tempratureRecordLabellingFormData
    );
  }
  addLABELLING_INSTRUCTIONS_FormData = [];
  addLABELLING_INSTRUCTIONS_Form(data) {
    let temp = data.value;
    this.addLABELLING_INSTRUCTIONS_FormData[
      this.addLABELLING_INSTRUCTIONS_FormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addLABELLING_INSTRUCTIONS_FormData :>> ',
      this.addLABELLING_INSTRUCTIONS_FormData
    );
  }
  PotentialtrappointscheckingData = [];
  addPotentialtrappointschecking_Form(data) {
    let temp = data.value;
    this.PotentialtrappointscheckingData[
      this.PotentialtrappointscheckingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.PotentialtrappointscheckingData :>> ',
      this.PotentialtrappointscheckingData
    );
  }
  ArtWorkCodeVerificationFormData = [];
  addArtWorkCodeVerification(data) {
    let temp = data.value;
    this.ArtWorkCodeVerificationFormData[
      this.ArtWorkCodeVerificationFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ArtWorkCodeVerificationFormData :>> ',
      this.ArtWorkCodeVerificationFormData
    );
  }
  machineSetupListData = [];
  addMachineSetup(data) {
    let temp = data.value;
    this.machineSetupListData[this.machineSetupListData.length] = temp;
    data.resetForm();
    console.log('this.machineSetupListData :>> ', this.machineSetupListData);
  }
  // below is for one step
  RecordSpecimenOverprintingDetailsData = [];
  addRecordSpecimenOverprintingDetails(data) {
    let temp = data.value;
    this.RecordSpecimenOverprintingDetailsData[
      this.RecordSpecimenOverprintingDetailsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.RecordSpecimenOverprintingDetailsData :>> ',
      this.RecordSpecimenOverprintingDetailsData
    );
  }
  RecordSpecimenOverprintingDetailsData2 = [];
  addRecordSpecimenOverprintingDetails2(data) {
    let temp = data.value;
    this.RecordSpecimenOverprintingDetailsData2[
      this.RecordSpecimenOverprintingDetailsData2.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.RecordSpecimenOverprintingDetailsData2 :>> ',
      this.RecordSpecimenOverprintingDetailsData2
    );
  }
  SubmitRecordSpecimenOverprintingDetailsData = [];

  // complete
  inprocessCheckDuringPFSlabellingFormData = [];
  addinprocessCheckDuringPFSlabellingForm(data) {
    let temp = data.value;
    this.inprocessCheckDuringPFSlabellingFormData[
      this.inprocessCheckDuringPFSlabellingFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.inprocessCheckDuringPFSlabellingFormData :>> ',
      this.inprocessCheckDuringPFSlabellingFormData
    );
  }
  addlabelRollSpecimenSYRINGESRecFormData = [];
  addlabelRollSpecimenSYRINGESRecForm(data) {
    let temp = data.value;
    this.addlabelRollSpecimenSYRINGESRecFormData[
      this.addlabelRollSpecimenSYRINGESRecFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addlabelRollSpecimenSYRINGESRecFormData :>> ',
      this.addlabelRollSpecimenSYRINGESRecFormData
    );
  }

  addPFSLabelReconciliationRowData = [];
  addPFSLabelReconciliationRow(data) {
    let temp = data.value;
    this.addPFSLabelReconciliationRowData[
      this.addPFSLabelReconciliationRowData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addPFSLabelReconciliationRowData :>> ',
      this.addPFSLabelReconciliationRowData
    );
  }
  addArtWorkPharmaCodeVerificationRowData = [];
  addArtWorkPharmaCodeVerificationRow(data) {
    let temp = data.value;
    this.addArtWorkPharmaCodeVerificationRowData[
      this.addArtWorkPharmaCodeVerificationRowData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addArtWorkPharmaCodeVerificationRowData :>> ',
      this.addArtWorkPharmaCodeVerificationRowData
    );
  }
  recordSpecimenProofChecking3PlyCartonData = [];
  addRecordSpecimenProofChecking3PlyCarton(data) {
    let temp = data.value;
    this.recordSpecimenProofChecking3PlyCartonData[
      this.recordSpecimenProofChecking3PlyCartonData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.recordSpecimenProofChecking3PlyCartonData :>> ',
      this.recordSpecimenProofChecking3PlyCartonData
    );
  }
  inProcessChecksDuring3PlyFluteCartonOverPrintingData = [];
  addInProcessChecksDuring3PlyFluteCartonOverPrinting(data) {
    let temp = data.value;
    this.inProcessChecksDuring3PlyFluteCartonOverPrintingData[
      this.inProcessChecksDuring3PlyFluteCartonOverPrintingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.inProcessChecksDuring3PlyFluteCartonOverPrintingData :>> ',
      this.inProcessChecksDuring3PlyFluteCartonOverPrintingData
    );
  }
  threePlyFluteCartonReconciliationData = [];
  add3PlyFluteCartonReconciliationRow(data) {
    let temp = data.value;
    this.threePlyFluteCartonReconciliationData[
      this.threePlyFluteCartonReconciliationData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.threePlyFluteCartonReconciliationData :>> ',
      this.threePlyFluteCartonReconciliationData
    );
  }
  temperatureRecords = [];
  addTemperatureRecord(data) {
    let temp = data.value;
    this.temperatureRecords[this.temperatureRecords.length] = temp;
    data.resetForm();
    console.log('this.temperatureRecords :>> ', this.temperatureRecords);
  }
  potentialTrapPointCheckingList = [];
  addPotentialTrapPointChecking(data) {
    let temp = data.value;
    this.potentialTrapPointCheckingList[
      this.potentialTrapPointCheckingList.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.potentialTrapPointCheckingList :>> ',
      this.potentialTrapPointCheckingList
    );
  }
  artWorkPharmaCodeVerificationList = [];
  addArtWorkPharmaCodeVerificationthreeply(data) {
    let temp = data.value;
    this.artWorkPharmaCodeVerificationList[
      this.artWorkPharmaCodeVerificationList.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.artWorkPharmaCodeVerificationList :>> ',
      this.artWorkPharmaCodeVerificationList
    );
  }
  SpecimenProofCheckingthreeplyFluteCartonData = [];
  addSpecimenProofCheckingthreeplyFluteCarton(data) {
    let temp = data.value;
    this.SpecimenProofCheckingthreeplyFluteCartonData[
      this.SpecimenProofCheckingthreeplyFluteCartonData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.SpecimenProofCheckingthreeplyFluteCartonData :>> ',
      this.SpecimenProofCheckingthreeplyFluteCartonData
    );
  }
  bulkHoldingDataArray = [];
  addBulkHoldingEntry(data) {
    let temp = data.value;
    this.bulkHoldingDataArray[this.bulkHoldingDataArray.length] = temp;
    data.resetForm();
    console.log('this.bulkHoldingDataArray :>> ', this.bulkHoldingDataArray);
  }

  // belw is for `1`

  LimitFor3PlyCartonWeighingBalanceList = [];
  addLimitFor3PlyCartonWeighingBalance(data) {
    let temp = data.value;
    this.LimitFor3PlyCartonWeighingBalanceList[
      this.LimitFor3PlyCartonWeighingBalanceList.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.LimitFor3PlyCartonWeighingBalanceList :>> ',
      this.LimitFor3PlyCartonWeighingBalanceList
    );
  }
  limitFor3PlyCartonWeighingList = [];
  addLimitFor3PlyCartonWeighing(data) {
    let temp = data.value;
    this.limitFor3PlyCartonWeighingList[
      this.limitFor3PlyCartonWeighingList.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.limitFor3PlyCartonWeighingList :>> ',
      this.limitFor3PlyCartonWeighingList
    );
  }

  // complete
  inProcessPackingCheckList = [];
  addInProcessPackingCheckForm(data) {
    let temp = data.value;
    this.inProcessPackingCheckList[this.inProcessPackingCheckList.length] =
      temp;
    data.resetForm();
    console.log(
      'this.inProcessPackingCheckList :>> ',
      this.inProcessPackingCheckList
    );
  }
  COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList = [];
  AddCOLLECTIONOFCONTROLLEDSAMPLESANDOTHERSForm(data) {
    let temp = data.value;
    this.COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList[
      this.COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList :>> ',
      this.COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList
    );
  }

  // for 1 step
  WeighingRecordForShipper1FormData = [];
  addWeighingRecordForShipper1Form(data) {
    let temp = data.value;
    this.WeighingRecordForShipper1FormData[
      this.WeighingRecordForShipper1FormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.WeighingRecordForShipper1FormData :>> ',
      this.WeighingRecordForShipper1FormData
    );
  }
  addWeighingRecordForShipper2FormData = [];
  addWeighingRecordForShipper2Form(data) {
    let temp = data.value;
    this.addWeighingRecordForShipper2FormData[
      this.addWeighingRecordForShipper2FormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addWeighingRecordForShipper2FormData :>> ',
      this.addWeighingRecordForShipper2FormData
    );
  }
  AddWeighingRecordForShipper3FormData = [];
  AddWeighingRecordForShipper3Form(data) {
    let temp = data.value;
    this.AddWeighingRecordForShipper3FormData[
      this.AddWeighingRecordForShipper3FormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.AddWeighingRecordForShipper3FormData :>> ',
      this.AddWeighingRecordForShipper3FormData
    );
  }
  // complete
  Reprocessing2DCartonRejectFormData = [];
  addReprocessing2DCartonRejectForm(data) {
    let temp = data.value;
    this.Reprocessing2DCartonRejectFormData[
      this.Reprocessing2DCartonRejectFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.Reprocessing2DCartonRejectFormData :>> ',
      this.Reprocessing2DCartonRejectFormData
    );
  }

  // ranjit

  InProcessChecksDuringCartonOverPrintingFormData = [];

  addInProcessChecksDuringCartonOverPrintingForm(data) {
    let temp = data.value;
    this.InProcessChecksDuringCartonOverPrintingFormData[
      this.InProcessChecksDuringCartonOverPrintingFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.InProcessChecksDuringCartonOverPrintingFormData :>> ',
      this.InProcessChecksDuringCartonOverPrintingFormData
    );
  }

  //2
  cartonReconciliationData: any[] = [];
  addCartonReconciliation(data) {
    let temp = data.value;
    this.cartonReconciliationData[this.cartonReconciliationData.length] = temp;
    data.resetForm();
    console.log(
      'this.cartonReconciliationData :>> ',
      this.cartonReconciliationData
    );
  }

  //3
  reconciliationAfterPackingData: any[] = [];

  addReconciliationAfterPacking(form: any) {
    const temp = form.value;
    this.reconciliationAfterPackingData.push(temp);
    form.resetForm();
    console.log('Reconciliation Data:', this.reconciliationAfterPackingData);
  }

  deleteReconciliationAfterPacking(index: number) {
    this.reconciliationAfterPackingData.splice(index, 1);
  }

  //4
  ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData =
    [];

  addReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationForm(
    form
  ) {
    const temp = form.value;
    this.ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData[
      this.ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData.length
    ] = temp;
    form.resetForm();
    console.log(
      'this.ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData :>> ',
      this
        .ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData
    );
  }

  //5
  decartoningAsPerSOPFormData = [];

  addDecartoningAsPerSOPForm(data) {
    const temp = data.value;
    this.decartoningAsPerSOPFormData[this.decartoningAsPerSOPFormData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.decartoningAsPerSOPFormData :>> ',
      this.decartoningAsPerSOPFormData
    );
  }

  //6
  processForLoadingForFillingFormData = [];
  checkpointsFormData = [];

  addProductChangeoverRow(data) {
    const temp = data.value;
    this.checkpointsFormData[this.checkpointsFormData.length] = temp;
    data.resetForm();
    console.log('this.checkpointsFormData :>> ', this.checkpointsFormData);
  }
  addProcessForLoadingForFillingForm(data) {
    const temp = data.value;
    this.processForLoadingForFillingFormData[
      this.processForLoadingForFillingFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.processForLoadingForFillingFormData :>> ',
      this.processForLoadingForFillingFormData
    );
  }

  //7
  detailOfFillingMachineAlignmentFormData = [];

  addDetailOfFillingMachineAlignmentForm(data) {
    const temp = data.value;
    this.detailOfFillingMachineAlignmentFormData[
      this.detailOfFillingMachineAlignmentFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.detailOfFillingMachineAlignmentFormData :>> ',
      this.detailOfFillingMachineAlignmentFormData
    );
  }

  //8
  inProcessChecksData = [];

  addInProcessCheck(form) {
    const temp = form.value;
    this.inProcessChecksData.push(temp);
    form.resetForm();
    console.log('inProcessChecksData:', this.inProcessChecksData);
  }
  addPrimaryPackingRowData=[];
  addPrimaryPackingRow(form) {
    const temp = form.value;
    this.addPrimaryPackingRowData.push(temp);
    form.resetForm();
    console.log('addPrimaryPackingRowData:', this.addPrimaryPackingRowData);
  }

  //9
  fillingAreaMonitoringData = [];

  addFillingAreaMonitoring(form) {
    const temp = form.value;
    this.fillingAreaMonitoringData.push(temp);
    form.resetForm();
    console.log('fillingAreaMonitoringData:', this.fillingAreaMonitoringData);
  }

  //10
  inspectionData = [];

  addInspectionData(form) {
    const temp = form.value;
    this.inspectionData.push(temp);
    form.resetForm();
    console.log('inspectionData:', this.inspectionData);
  }

  //11
  leakTestRecords = [];

  addLeakTest(form) {
    const temp = form.value;
    this.leakTestRecords[this.leakTestRecords.length] = temp;
    form.resetForm();
    console.log('leakTestRecords :>> ', this.leakTestRecords);
  }

  //12
  reconciliationData = [];

  addReconciliation(form) {
    const temp = form.value;
    this.reconciliationData[this.reconciliationData.length] = temp;
    form.resetForm();
    console.log('reconciliationData :>> ', this.reconciliationData);
  }

  //13
  primaryPackingFormDataArray = [];

  addPrimaryPackingFormData(form) {
    const temp = form.value;
    this.primaryPackingFormDataArray[this.primaryPackingFormDataArray.length] =
      temp;
    form.resetForm();
    console.log(
      'this.primaryPackingFormDataArray :>> ',
      this.primaryPackingFormDataArray
    );
  }

  //15
  FilledSyringeDataArray = [];

  addFilledSyringeData(form) {
    const temp = form.value;
    this.FilledSyringeDataArray[this.FilledSyringeDataArray.length] = temp;
    form.resetForm();
    console.log(
      'this.FilledSyringeDataArray :>> ',
      this.FilledSyringeDataArray
    );
  }

  //16
  precautionFiltrationDataArray = [];

  addPrecautionFiltration(form) {
    const temp = form.value;
    this.precautionFiltrationDataArray.push(temp);
    form.resetForm();
    console.log(
      'precautionFiltrationDataArray:',
      this.precautionFiltrationDataArray
    );
  }
  BulkSolutionDataArray = [];

  addBulkSolutionData(form) {
    const temp = form.value;
    this.BulkSolutionDataArray[this.BulkSolutionDataArray.length] = temp;
    form.resetForm();
    console.log('this.BulkSolutionDataArray :>> ', this.BulkSolutionDataArray);
  }
  //17
  LeftOverMaterialDataArray = [];

  addLeftOverMaterial(form) {
    const temp = form.value;
    this.LeftOverMaterialDataArray[this.LeftOverMaterialDataArray.length] =
      temp;
    form.resetForm();
    console.log(
      'this.LeftOverMaterialDataArray :>> ',
      this.LeftOverMaterialDataArray
    );
  }

  //18
  DeviationDataArray = [];

  addDeviation(form) {
    const temp = form.value;
    this.DeviationDataArray[this.DeviationDataArray.length] = temp;
    form.resetForm();
    console.log('this.DeviationDataArray :>> ', this.DeviationDataArray);
  }

  //19
  rejectionDestructionDataArray = [];

  //20
  disposalDataArray = [];

  addDisposal(form: any) {
    const temp = form.value;
    this.disposalDataArray.push(temp);
    form.resetForm();
    console.log('disposalDataArray:', this.disposalDataArray);
  }

  //21
  packingMaterialDataArray = [];

  addPackingMaterialData(form: any) {
    const temp = form.value;
    this.packingMaterialDataArray.push(temp);
    form.resetForm();
    console.log('packingMaterialDataArray :>> ', this.packingMaterialDataArray);
  }
  manufacturinProcessFormDataArray = [];
  addmanufacturinProcessForm(form: any) {
    const temp = form.value;
    this.manufacturinProcessFormDataArray.push(temp);
    form.resetForm();
    console.log(
      'manufacturinProcessFormDataArray :>> ',
      this.manufacturinProcessFormDataArray
    );
  }
  //22
  finishedGoodsDataArray = [];

  addFinishedGoodsData(form: any) {
    const temp = form.value;
    this.finishedGoodsDataArray.push(temp);
    form.resetForm();
    console.log('finishedGoodsDataArray :>> ', this.finishedGoodsDataArray);
  }
  //23
  reconciliationAfterPackingDataArray: any[] = [];

  // Pre-filled table structure
  reconciliationData1 = [
    {
      code: 'A',
      description: 'No. of units transferred for labelling',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'B',
      description: 'No. of units breakage during labelling',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'C',
      description: 'No. of labelled units transferred to packing',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'D',
      description: 'Labelling yield (C ÷ A × 100)',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'E',
      description: 'No. of units broken during packing',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'F',
      description: 'Controlled samples collected',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'G',
      description: 'Samples collected for analysis',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'H',
      description: 'Total units packaged for shipment',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'I',
      description: 'Qty. Packed for stability testing',
      stepNo: '',
      syringes: null,
    },
    { code: 'J', description: 'Others if any', stepNo: '', syringes: null },
    {
      code: 'K',
      description: 'Total Packed Qty (F+G+H+I+J)',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'L',
      description: 'Packing yield - Limit (NLT 97%)',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'M',
      description: 'Theoretical Batch Size of BMR',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'N',
      description: 'Split Batch Details of each BPR + collected Samples',
      stepNo: '',
      syringes: null,
    },
    {
      code: 'O',
      description: 'Batch Yield = (N ÷ M) × 100 - Std: NLT 84%',
      stepNo: '',
      syringes: null,
    },
  ];

  checkedBy = '';
  verifiedBy = '';

  addReconciliationData(form: any) {
    const temp = {
      reconciliationData1: JSON.parse(JSON.stringify(this.reconciliationData1)), // deep copy
      checkedBy: this.checkedBy,
      verifiedBy: this.verifiedBy,
    };

    this.reconciliationAfterPackingDataArray.push(temp);
    form.resetForm();

    // Reset the internal data structure as well
    this.reconciliationData.forEach((row) => {
      row.stepNo = '';
      row.syringes = null;
    });
    this.checkedBy = '';
    this.verifiedBy = '';

    console.log(
      'reconciliationAfterPackingDataArray :>> ',
      this.reconciliationAfterPackingDataArray
    );
  }

  //24
  approvalDataArray = [];

  //25
  cipCompoundingDataArray = [];

  AddCIPApproval(data: any) {
    const temp = data.value;
    this.cipCompoundingDataArray.push(temp);

    console.log('cipCompoundingDataArray:', this.cipCompoundingDataArray);
  }

  //26
  sipDetailsDataArray = [];

  AddSIPDetails(data: any) {
    const temp = data.value;
    this.sipDetailsDataArray.push(temp);
  }

  //27
  selectedStepsProcessingDataArray = [];

  submitSelectedStepsProcessing(form: any) {
    const temp = form.value;
    this.selectedStepsProcessingDataArray.push(temp);
    form.resetForm();
    console.log(
      'selectedStepsProcessingDataArray:',
      this.selectedStepsProcessingDataArray
    );
  }
  filterDataArray = [];
  addFilterEntry(form: any) {
    const temp = form.value;
    this.filterDataArray.push(temp);
    form.resetForm();
    console.log('filterDataArray:', this.filterDataArray);
  }

  //28
  selectedStepsReconciliationDataArray = [];

  submitSelectedStepsReconciliation(form: any) {
    const temp = form.value;
    this.selectedStepsReconciliationDataArray.push(temp);
    form.resetForm();
    console.log(
      'selectedStepsReconciliationDataArray:',
      this.selectedStepsReconciliationDataArray
    );
  }
  //29
  cleaningDataArray = [];

  AddCleaning(data: any) {
    const temp = data.value;
    this.cleaningDataArray.push(temp);

    console.log('cleaningDataArray:', this.cleaningDataArray);
  }
  //30
  sipFormDataArray = [];

  AddSipForm(data: any) {
    const temp = data.value;
    this.sipFormDataArray.push(temp);

    console.log('sipFormDataArray:', this.sipFormDataArray);
  }
  PrecautionsToBeFollowedDuringManufacturingData = [];
  addPrecautionsToBeFollowedDuringManufacturing(data: any) {
    const temp = data.value;
    this.PrecautionsToBeFollowedDuringManufacturingData.push(temp);

    console.log(
      'PrecautionsToBeFollowedDuringManufacturingData:',
      this.PrecautionsToBeFollowedDuringManufacturingData
    );
  }
  PackingLineClearanceData = [];
  addLineClearanceRow(data: any) {
    const temp = data.value;
    this.PackingLineClearanceData.push(temp);

    console.log('PackingLineClearanceData:', this.PackingLineClearanceData);
    data.resetForm();
  }
  //31
  bulkHoldingFormDataArray = [];

  submitBulkHoldingForm(form: any) {
    const temp = form.value;
    this.bulkHoldingFormDataArray.push(temp);
    form.resetForm();
    console.log('bulkHoldingFormDataArray:', this.bulkHoldingFormDataArray);
  }
  //32
  trayFormDataArray = [];

  submitTrayForm(form: any) {
    const temp = form.value;
    this.trayFormDataArray.push(temp);
    form.resetForm();

    for (let i = 0; i < this.trayFormDataArray.length; i++) {
      this.TotalsubmitTrayFormRejects =
        this.TotalsubmitTrayFormRejects +
        Number(this.trayFormDataArray[i].rejectionQty);
    }
  }

  //33
  filterFormDataArray = [];

  submitFilterForm(form: any) {
    const temp = form.value;
    this.filterFormDataArray.push(temp);
    form.resetForm();
    console.log('filterFormDataArray:', this.filterFormDataArray);
  }
  //34
  equipmentFormDataArray = [];

  submitEquipmentForm(form: any) {
    const temp = form.value;
    this.equipmentFormDataArray.push(temp);
    form.resetForm();
    console.log('equipmentFormDataArray:', this.equipmentFormDataArray);
  }

  //35
  vialDataArray: any[] = [];

  addVialData(form: any) {
    const temp = form.value;
    this.vialDataArray.push(temp);
    form.resetForm();
    console.log('vialDataArray:', this.vialDataArray);
  }

  removeVialData(index: number) {
    this.vialDataArray.splice(index, 1);
  }

  //36
  formDataArray = [];

  submitForm(form: any) {
    const temp = form.value;
    this.formDataArray.push(temp);
    form.resetForm();
    console.log('formDataArray:', this.formDataArray);
  }

  //37
  qtyFormDataArray: any[] = [];

  submitQtyForm(form: any) {
    let formValue = { ...form.value };
    // Calculate Good Quantity Transfer = A - (B + C)
    const A = Number(formValue.qtyReceived) || 0;
    const B = Number(formValue.inProcessRejects) || 0;
    const C = Number(formValue.sampleQty) || 0;
    formValue.goodQtyTransfer = A - (B + C);

    this.qtyFormDataArray.push(formValue);
    form.resetForm();
    console.log('qtyFormDataArray:', this.qtyFormDataArray);
  }

  removeQtyData(index: number) {
    this.qtyFormDataArray.splice(index, 1);
  }

  //38
  vacuumFormDataArray: any[] = [];

  submitVacuumForm(form: any) {
    const temp = form.value;
    this.vacuumFormDataArray.push(temp);
    form.resetForm();
    console.log('vacuumFormDataArray:', this.vacuumFormDataArray);
  }
  lyophilizerFormData: any = [];
  AddLyophilizerForm(form: any) {
    const temp = form.value;
    this.lyophilizerFormData.push(temp);
    form.resetForm();
    console.log('lyophilizerFormData:', this.lyophilizerFormData);
  }

  removeVacuumData(index: number) {
    this.vacuumFormDataArray.splice(index, 1);
  }

  //39
  primaryDryingFormDataArray: any[] = [];

  submitPrimaryDryingForm(form: any) {
    const temp = form.value;
    this.primaryDryingFormDataArray.push(temp);
    form.resetForm();
    console.log('primaryDryingFormDataArray:', this.primaryDryingFormDataArray);
  }

  removePrimaryDryingData(index: number) {
    this.primaryDryingFormDataArray.splice(index, 1);
  }

  //40
  secondaryDryingFormDataArray: any[] = [];
  RECONCILIATION_OF_BULK_MANUFACTURING_Data = [];
  addSelectedStepsData(form: any) {
    const temp = form.value;
    this.RECONCILIATION_OF_BULK_MANUFACTURING_Data.push(temp);
    form.resetForm();
    console.log(
      'RECONCILIATION_OF_BULK_MANUFACTURING_Data:',
      this.RECONCILIATION_OF_BULK_MANUFACTURING_Data
    );
  }
  submitSecondaryDryingForm(form: any) {
    const temp = form.value;
    this.secondaryDryingFormDataArray.push(temp);
    form.resetForm();
    console.log(
      'secondaryDryingFormDataArray:',
      this.secondaryDryingFormDataArray
    );
  }

  removeSecondaryDryingData(index: number) {
    this.secondaryDryingFormDataArray.splice(index, 1);
  }

  //41
  lyophilizationFormDataArray: any[] = [];

  submitLyophilizationForm(form: any) {
    this.lyophilizationFormDataArray.push(form.value);
    form.resetForm();
    console.log(
      'lyophilizationFormDataArray:',
      this.lyophilizationFormDataArray
    );
  }

  removeLyophilizationData(index: number) {
    this.lyophilizationFormDataArray.splice(index, 1);
  }
  //42
  reconData = [
    {
      srNo: 'A',
      description: 'Total Qty of vials transferred to Lyophilizer',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'B',
      description: 'Rejection during initial setting of sealing machine',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'C',
      description: 'Rejection during sealing',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'D',
      description: 'In process check During Sealing (Leak test)',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'E',
      description: 'Sample of Sealed vials (If any)',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'F',
      description: 'Seals rejected during sealing',
      quantity: null,
      uom: '',
    },
    { srNo: 'G', description: 'Finished sample', quantity: null, uom: '' },
    { srNo: 'H', description: 'Left over seals', quantity: null, uom: '' },
    {
      srNo: 'I',
      description:
        'Total Qty of vials transferred to vial collection = A-(B+C+D+E+F+G)',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'J',
      description: 'Destructed Qty. seal = C+D+E+F',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'K',
      description: 'Total Qty of vials transfer for visual inspection = I',
      quantity: null,
      uom: '',
    },
    {
      srNo: 'L',
      description: '%Yield = (K+G x100) / A',
      quantity: null,
      uom: '',
    },
  ];

  doneBy = '';

  submitReconForm(form: any) {
    console.log('Form Data:', this.reconData);
    console.log('Done By:', this.doneBy);
    console.log('Checked By:', this.checkedBy);
    alert('Form submitted! Check console for data.');
  }
  //43
  terminalParams = [];

  checkedByProduction = '';
  verifiedByIPQA = '';

  addTerminalParam(form: any) {
    const temp = form.value;
    this.terminalParams.push(temp);
    form.resetForm();
    console.log('Terminal Parameters:', this.terminalParams);
  }

  //44
  equipmentId: string = '';

  sterilizationEntries = [];

  submitSterilizationForm(form: any) {
    console.log('Equipment ID:', this.equipmentId);
    console.log('Sterilization Entries:', this.sterilizationEntries);
    alert('Sterilization process details submitted! Check console for output.');
  }
  //45
  lineClearance = {
    equipmentName: 'Vial labelling machine',
    equipmentId: 'PK/LBM-01',
    clearanceProduct: '',
    batchNo: '',
    previousProduct: '',
    previousBatchNo: '',
    dateTime: '',
    checkPoints: [
      {
        description: 'All product labels removed from previous batch',
        complies: false,
        notComplies: false,
      },
      {
        description: 'Equipment cleaned and sanitized',
        complies: false,
        notComplies: false,
      },
      // Add more check points here as needed
    ],
    remarks: '',
    checkedBy: '',
    verifiedBy: '',
  };

  submitLineClearance(form: any) {
    console.log('Line Clearance Data:', this.lineClearance);
    alert('Line clearance form submitted! Check console for data.');
  }
  //46
  inProcessChecks = [];

  //47
  LabelRollSpecimenForVialRecordFormData = [];

  // Temporary object for form binding
  LabelRollSpecimen = {
    rollNo: '',
    startDoneBy: '',
    startCheckedBy: '',
    startVerifiedBy: '',
    endDoneBy: '',
    endCheckedBy: '',
    endVerifiedBy: '',
  };

  // Method to handle form submission
  addLabelRollSpecimenForVialRecordForm(form) {
    let temp = { ...this.LabelRollSpecimen }; // Create a shallow copy
    this.LabelRollSpecimenForVialRecordFormData.push(temp); // Push data to array
    form.resetForm(); // Reset form
    console.log(
      'LabelRollSpecimenForVialRecordFormData:',
      this.LabelRollSpecimenForVialRecordFormData
    );
  }
  //48

  //49
  ProductChangeoverFormData = [];

  ProductChangeover = {
    previousProduct: '',
    batchNo: '',
    date: '',
    time: '',
    checkpoint1: '',
    comply1: false,
    notComply1: false,
    remarks: '',
    checkedBy: '',
    verifiedBy: '',
  };

  addProductChangeoverForm(form) {
    const temp = { ...this.ProductChangeover };
    this.ProductChangeoverFormData.push(temp);
    form.resetForm();
    console.log('ProductChangeoverFormData:', this.ProductChangeoverFormData);
  }

  // subtest

  AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData: any =
    [];
  AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineForm(data) {
    let temp = data.value;
    this.AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData[
      this.AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData :>> ',
      this
        .AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData
    );
  }

  CalculationsofRawMaterialData = [];
  addCalculationsofRawMaterial(data) {
    let temp = data.value;
    this.CalculationsofRawMaterialData[
      this.CalculationsofRawMaterialData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.CalculationsofRawMaterialData :>> ',
      this.CalculationsofRawMaterialData
    );
  }
  addReconcilationofDepyrogenationVialsData = [];
  addReconcilationofDepyrogenationVials(data) {
    let temp = data.value;
    this.addReconcilationofDepyrogenationVialsData[
      this.addReconcilationofDepyrogenationVialsData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addReconcilationofDepyrogenationVialsData :>> ',
      this.addReconcilationofDepyrogenationVialsData
    );
  }

  addSecLineClearanceRow = [];
  AddSecLineClearance(data) {
    let temp = data.value;
    this.addSecLineClearanceRow[this.addSecLineClearanceRow.length] = temp;
    data.resetForm();
    console.log(
      'this. addSecLineClearanceRow :>> ',
      this.addSecLineClearanceRow
    );
  }
  addInProcessStartFillingRow = [];
  addInProcessStartFilling(data) {
    let temp = data.value;
    this.addInProcessStartFillingRow[this.addInProcessStartFillingRow.length] =
      temp;
    data.resetForm();
    console.log(
      'this. addInProcessStartFillingRow :>> ',
      this.addInProcessStartFillingRow
    );
  }

  addInprocessduringfillingRowData = [];
  addInprocessduringfilling(data) {
    let temp = data.value;
    this.addInprocessduringfillingRowData[
      this.addInprocessduringfillingRowData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addInprocessduringfillingRowData :>> ',
      this.addInprocessduringfillingRowData
    );
  }
  addInProcessCheckForm(data) {
    let temp = data.value;
    this.inProcessChecks[this.inProcessChecks.length] = temp;
    data.resetForm();
    console.log('this.inProcessChecks :>> ', this.inProcessChecks);
  }

  addInprocessattheendoffillingRowData = [];
  addInprocessattheendoffilling(data) {
    let temp = data.value;
    this.addInprocessattheendoffillingRowData[
      this.addInprocessattheendoffillingRowData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.addInprocessattheendoffillingRowData:>> ',
      this.addInprocessattheendoffillingRowData
    );
  }

  InspectionoffilledVialduringfillingRows = [];
  addInspectionoffilledVialduringfilling(data) {
    let temp = data.value;
    this.InspectionoffilledVialduringfillingRows[
      this.InspectionoffilledVialduringfillingRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.InspectionoffilledVialduringfillingRows:>> ',
      this.InspectionoffilledVialduringfillingRows
    );
  }

  TransferandVerificationofSemifinishedGoodsRows = [];
  addTransferandVerificationofSemifinishedGoods(data) {
    let temp = data.value;
    this.TransferandVerificationofSemifinishedGoodsRows[
      this.TransferandVerificationofSemifinishedGoodsRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.TransferandVerificationofSemifinishedGoodsRows:>> ',
      this.TransferandVerificationofSemifinishedGoodsRows
    );
  }
  PrecautionsDuringBulkSolutionManufacturingData = [];
  addPrecautionsDuringBulkSolutionManufacturing(data) {
    let temp = data.value;
    this.PrecautionsDuringBulkSolutionManufacturingData[
      this.PrecautionsDuringBulkSolutionManufacturingData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.PrecautionsDuringBulkSolutionManufacturingData:>> ',
      this.PrecautionsDuringBulkSolutionManufacturingData
    );
  }

  OpticalInspectionRecordRows = [];
  addOpticalInspectionRecord(data) {
    let temp = data.value;
    this.OpticalInspectionRecordRows[this.OpticalInspectionRecordRows.length] =
      temp;
    data.resetForm();
    console.log(
      'this.OpticalInspectionRecordRows:>> ',
      this.OpticalInspectionRecordRows
    );
  }

  SPOTCheckRecordRows = [];
  addSPOTCheckRecord(data) {
    let temp = data.value;
    this.SPOTCheckRecordRows[this.SPOTCheckRecordRows.length] = temp;
    data.resetForm();
    console.log('this.SPOTCheckRecordRows:>> ', this.SPOTCheckRecordRows);
  }

  addReInspectionRecordData = [];
  addReInspectionRecord(data) {
    let temp = data.value;
    this.addReInspectionRecordData[this.addReInspectionRecordData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.addReInspectionRecordData:>> ',
      this.addReInspectionRecordData
    );
  }
  commentArray: any = [];
  addcommentArray(data) {
    let temp = data.value;
    this.commentArray[this.commentArray.length] = temp;
    data.resetForm();
    console.log(
      'this.addReInspectionRecordData:>> ',
      this.addReInspectionRecordData
    );
  }

  addRejectionAnalysisRowData = [];
  addRejectionAnalysisRow(data) {
    let temp = data.value;
    this.addRejectionAnalysisRowData[this.addRejectionAnalysisRowData.length] =
      temp;
    data.resetForm();
    console.log(
      'this.addRejectionAnalysisRowData:>> ',
      this.addRejectionAnalysisRowData
    );
  }

  opticalReconciliationRows = [];
  addOpticalReconciliationRow(data) {
    let temp = data.value;
    this.opticalReconciliationRows[this.opticalReconciliationRows.length] =
      temp;
    data.resetForm();
    console.log(
      'this.opticalReconciliationRows:>> ',
      this.opticalReconciliationRows
    );
  }

  ReconciliationofPrimaryPackingMaterialRows = [];
  addReconciliationofPrimaryPackingMaterial(data) {
    let temp = data.value;
    this.ReconciliationofPrimaryPackingMaterialRows[
      this.ReconciliationofPrimaryPackingMaterialRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ReconciliationofPrimaryPackingMaterialRows :>> ',
      this.ReconciliationofPrimaryPackingMaterialRows
    );
  }

  disposalOfOpticalRejectsRows = [];
  addDisposalOfOpticalRejects(data) {
    let temp = data.value;
    this.disposalOfOpticalRejectsRows[
      this.disposalOfOpticalRejectsRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.disposalOfOpticalRejectsRows :>> ',
      this.disposalOfOpticalRejectsRows
    );
  }
  loadSterilizedMachineAccessoriesData = [];
  addloadSterilizedMachineAccessoriesForm(data) {
    let temp = data.value;
    this.loadSterilizedMachineAccessoriesData[
      this.loadSterilizedMachineAccessoriesData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.loadSterilizedMachineAccessoriesData :>> ',
      this.loadSterilizedMachineAccessoriesData
    );
  }

  DEVIATIONHISTORYRows = [];
  addDEVIATIONHISTORY(data) {
    let temp = data.value;
    this.DEVIATIONHISTORYRows[this.DEVIATIONHISTORYRows.length] = temp;
    data.resetForm();
    console.log('this.DEVIATIONHISTORYRows :>> ', this.DEVIATIONHISTORYRows);
  }

  lineClearanceLabellingactivityRows = [];
  addLineClearanceLabellingactivity(data) {
    let temp = data.value;
    this.lineClearanceLabellingactivityRows[
      this.lineClearanceLabellingactivityRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.lineClearanceLabellingactivityRows :>> ',
      this.lineClearanceLabellingactivityRows
    );
  }

  PrimaryFiltrationDetailsRows = [];
  addPrimaryFiltrationDetails(data) {
    let temp = data.value;
    this.PrimaryFiltrationDetailsRows[
      this.PrimaryFiltrationDetailsRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.PrimaryFiltrationDetailsRows :>> ',
      this.PrimaryFiltrationDetailsRows
    );
  }
  filledSyringeFormData = [];
  addfilledSyringeForm(data) {
    let temp = data.value;
    this.filledSyringeFormData[this.filledSyringeFormData.length] = temp;
    data.resetForm();
    console.log('this.filledSyringeFormData :>> ', this.filledSyringeFormData);
  }

  ChangeHistoryDetailsForm = [];
  AddChangeHistoryDetails(data) {
    let temp = data.value;
    this.ChangeHistoryDetailsForm[this.ChangeHistoryDetailsForm.length] = temp;

    console.log(
      'this.ChangeHistoryDetails :>> ',
      this.ChangeHistoryDetailsForm
    );
  }
  ReconciliationofBulkSolutionRows = [];
  addReconciliationofBulkSolution(data) {
    let temp = data.value;
    this.ReconciliationofBulkSolutionRows[
      this.ReconciliationofBulkSolutionRows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ReconciliationofBulkSolutionRows :>> ',
      this.ReconciliationofBulkSolutionRows
    );
  }
  ReconciliationofBulkSolutionFormData=[];
  addReconciliationRow(data) {
    let temp = data.value;
    this.ReconciliationofBulkSolutionFormData[
      this.ReconciliationofBulkSolutionFormData.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.ReconciliationofBulkSolutionFormData :>> ',
      this.ReconciliationofBulkSolutionFormData
    );
  }

  //   for 1 step
  LimitforCartonWeighingBalance1Rows = [];
  AddLimitforCartonWeighingBalance1(data) {
    let temp = data.value;
    this.LimitforCartonWeighingBalance1Rows[
      this.LimitforCartonWeighingBalance1Rows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.LimitforCartonWeighingBalance1Rows :>> ',
      this.LimitforCartonWeighingBalance1Rows
    );
  }

  LimitforCartonWeighingBalance2Rows = [];
  AddLimitforCartonWeighingBalance2(data) {
    let temp = data.value;
    this.LimitforCartonWeighingBalance2Rows[
      this.LimitforCartonWeighingBalance2Rows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.LimitforCartonWeighingBalance2Rows :>> ',
      this.LimitforCartonWeighingBalance2Rows
    );
  }

  LimitforCartonWeighingBalance3Rows = [];
  addLimitforCartonWeighingBalance3(data) {
    let temp = data.value;
    this.LimitforCartonWeighingBalance3Rows[
      this.LimitforCartonWeighingBalance3Rows.length
    ] = temp;
    data.resetForm();
    console.log(
      'this.LimitforCartonWeighingBalance3Rows :>> ',
      this.LimitforCartonWeighingBalance3Rows
    );
  }

  // /////////////////////////////////////////////////////////////////////////////////////////////////
  // ///////////////////////////////////Final Save////////////////////////////////////////////////////
  // ////////////////////////////////////////////////////////////////////////////////////////////////
  // ///////////////////////////////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////////////////////////////
  submitSafetyMSDS(data, data1) {
    // Always store only one object in the array
    this.safetyArray = [{ ...this.safetyInstructions }];
    console.log('Submitted Safety Instructions:', this.safetyArray);

    this.FinalData = this.safetyArray;
    const StepID1 = data1;
    console.log('Submitted FinalData:', this.FinalData);
    // console.log('Submitted FinalData:', data1);
    this.saveStepFilledData(StepID1);
  }
  submitProcessFlowDryPowder(data1) {
    this.FinalData = 'ProcessFlowDryPowder.png';
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitProcessFlowLYOPHILIZED(data1) {
    this.FinalData = 'ProcessFlowLYOPHILIZED.png';
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitProcessFlowInjection(data1) {
    this.FinalData = 'ProcessFlowInjection.png';
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitCalculations(data1) {
    this.FinalData = 'Calculations.png';
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitMFR_RawMaterial(data1) {
    this.FinalData = this.raw_materials;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  Submitprimary_pm_list(data1) {
    this.FinalData = this.primary_pm_list;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitGeneralInstructions(data1) {
    this.FinalData = this.general_instructionData;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPersonInvolved(data1) {
    this.FinalData = this.InvolvedPersonsList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitEquipmentsUsedList(data1) {
    this.FinalData = this.EquipmentsUsedList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitsipDetailsDataArray(data1) {
    this.FinalData = this.sipDetailsDataArray;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submit_IssuanceofConsumablesMaterial(data1) {
    this.FinalData = this.ConsumableResultBOM;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitunitPreparationPrecaution(data1) {
    this.FinalData = this.unitPreparationPrecautionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitMachinePartCleaningRecordData(data1) {
    this.FinalData = this.MachinePartCleaningRecordData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitthisUnitPreparationFormDataData(data1) {
    this.FinalData = this.thisUnitPreparationFormDataData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitStepLineClearanceCheckpoints(data, data1) {
    let temp = data.value;
    temp['lineclearance'] = this.LineClearanceChkPointsList;
    console.log('temp :>> ', temp);
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitRubberStopperSealSterilization(data, data1) {
    let temp = data.value;
    temp['rubberStopperSealSterilizationData'] =
      this.rubberStopperSealSterilizationData;
    console.log('temp :>> ', temp);
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitVialWashingData(data, data1) {
    console.log('data :>> ');
    console.log(data.value);
    let temp = data.value;
    temp['VialWashingData'] = this.VialWashingData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDOCUMENT_COMPLETION_VERIFIED_BY(data, data1) {
    console.log('data :>> ');
    console.log(data.value);
    let temp = data.value;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDisconnectUtilitiesData(data, data1) {
    console.log('data :>> ');
    console.log(data.value);
    let temp = data.value;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitBulkSolutionsForm(data, data1) {
    let temp = data.value;
    temp['F_Total'] = this.F_Total;
    temp['E_Total'] = this.E_Total;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDeCartonningVialSop(data, data1) {
    let temp = data.value;
    temp['deCartonningVialSopData'] = this.addDeCartonningVialSopData;
    temp['qtyTransferProdNos'] = this.qtyTransferProdNos;
    temp['totalDeCartonningVialSopDatarejectionQty'] =
      this.totalDeCartonningVialSopDatarejectionQty;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  productionEmployeeSign = '';
  productionEmployeeSignDate = '';
  qaSign = '';
  qaSignDate = '';
  submitRECONCILIATION_OF_BULK_MANUFACTURING_Data(data1) {
    let temp = {};
    temp['productionEmployeeSign'] = this.productionEmployeeSign;
    temp['productionEmployeeSignDate'] = this.productionEmployeeSignDate;
    temp['qaSign'] = this.qaSign;
    temp['qaSignDate'] = this.qaSignDate;
    temp['RECONCILIATION_OF_BULK_MANUFACTURING_Data'] =
      this.RECONCILIATION_OF_BULK_MANUFACTURING_Data;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submittrayFormData(data1) {
    let temp = {};
    temp['previousProduct'] = this.previousProduct;
    temp['batchNo'] = this.batchNo;
    temp['date'] = this.date;
    temp['time'] = this.time;
    temp['PackingLineClearanceData'] = this.PackingLineClearanceData;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitsterilizationFormData(data1) {
    let temp = {};
    temp['equipmentId'] = this.equipmentId;
    temp['sterilizationEntries'] = this.sterilizationEntries;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPackingLineClearanceData(data1) {
    let temp = {};
    temp['TotalsubmitTrayFormRejects'] = this.TotalsubmitTrayFormRejects;
    temp['qtyTransferProdNos'] = this.qtyTransferProdNos;
    temp['trayFormDataArray'] = this.trayFormDataArray;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitAssemblingAndFillingProcessDetails(data1) {
    let temp = {};
    temp['asepticPreparationHoldTimeData'] =
      this.asepticPreparationHoldTimeData;
    temp['assemblingAndFillingProcessDetailsData'] =
      this.assemblingAndFillingProcessDetailsData;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconciliationofDrypowderForm(data, data1) {
    let temp = data.value;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitApproval(data, data1) {
    let temp = data.value;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitaddCartonReconciliation(data, data1) {
    let temp = data.value;
    temp['cartonReconciliationData'] = this.cartonReconciliationData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  equipmentName = '';

  clearanceProduct = '';
  batchNo = '';

  previousBatchNo = '';
  dateTime = '';
  lineClearance_remarks = '';
  lineClearance_checkedBy = '';
  lineClearance_checkedByDate = '';
  lineClearance_verifiedBy = '';
  lineClearance_verifiedByDate = '';
  submitLineClearanceData(data1) {
    let temp = {};
    temp['equipmentName'] = this.equipmentName;
    temp['equipmentId'] = this.equipmentId;
    temp['clearanceProduct'] = this.clearanceProduct;
    temp['batchNo'] = this.batchNo;
    temp['previousProduct'] = this.previousProduct;
    temp['previousBatchNo'] = this.previousBatchNo;
    temp['dateTime'] = this.dateTime;
    temp['lineClearance_remarks'] = this.lineClearance_remarks;
    temp['lineClearance_checkedBy'] = this.lineClearance_checkedBy;
    temp['lineClearance_checkedByDate'] = this.lineClearance_checkedByDate;
    temp['lineClearance_verifiedBy'] = this.lineClearance_verifiedBy;
    temp['lineClearance_verifiedByDate'] = this.lineClearance_verifiedByDate;
    temp['checkpointsFormData'] = this.checkpointsFormData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitinprocessChecksDuringTunnelSterilizationVIALForm(data1) {
    this.FinalData = this.inprocessChecksDuringTunnelSterilizationVialData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitFilterDetailsForm(data1) {
    this.FinalData = this.addFilterDetailsData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitparameterData(data1) {
    this.FinalData = this.parameterData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitsipFormDataArray(data1) {
    this.FinalData = this.sipFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitcleaningDataArray(data1) {
    this.FinalData = this.cleaningDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitsterilizationAndDepyrogenationForm(data1) {
    this.FinalData = this.sterilizationAndDepyrogenationData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitinprocessCheckAtEndsWashingForm(data1) {
    this.FinalData = this.inprocessCheckAtEndsWashingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInprocessAtEndWashingForm(data1) {
    this.FinalData = this.inprocessAtEndWashingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationForm(
    data1
  ) {
    this.FinalData =
      this.ReconciliationOfBulkSolutionAfterPreFiltrationBeforeFinalFiltrationFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconcilationWashedVialsData(data1) {
    this.FinalData = this.reconcilationWashedVialsData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitWashedVialInspectionData(data1) {
    this.FinalData = this.washedVialInspectionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitexcessMaterialRequisitionData(data1) {
    this.FinalData = this.excessMaterialRequisitionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submittempratureRecordLabellingFormData(data1) {
    this.FinalData = this.tempratureRecordLabellingFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submittemperatureRecords(data1) {
    this.FinalData = this.temperatureRecords;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  SubmitcommentArray(data1) {
    this.FinalData = this.commentArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitvialForm(data1) {
    this.FinalData = this.vialDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitoperatorSettingVialWashingForm(data1) {
    this.FinalData = this.operatorSettingVialWashingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitloadSterilizedMachinePartsData(data1) {
    this.FinalData = this.loadSterilizedMachinePartsData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitloadingWashingInspectionForm(data1) {
    this.FinalData = this.loadingWashingInspectionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPostIntegrityFilterData(data1) {
    this.FinalData = this.addPostIntegrityFilterData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitInProcessCheckForm(data1) {
    this.FinalData = this.inProcessChecks;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  SubmitAssemblingAndSealingProcessData(data1) {
    this.FinalData = this.assemblingAndSealingProcessData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitartWorkPharmaCodeVerificationList(data1) {
    this.FinalData = this.artWorkPharmaCodeVerificationList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitSpecimenProofCheckingthreeplyFluteCartonData(data1) {
    this.FinalData = this.SpecimenProofCheckingthreeplyFluteCartonData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitinProcessPackingCheckList(data1) {
    this.FinalData = this.inProcessPackingCheckList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitLABELROLLSPECIMENFORVIALRECORDData(data1) {
    this.FinalData = this.LabelRollSpecimenForVialRecordFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitCOLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList(data1) {
    this.FinalData = this.COLLECTIONOFCONTROLLEDSAMPLESANDOTHERSFormList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  SubmitInProcessRecordVialSealingCheckData(data1) {
    this.FinalData = this.inProcessRecordVialSealingCheckData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  SubmitFinishedProductSamplesData(data1) {
    this.FinalData = this.finishedProductSamplesData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitBulkHoldingData(data1) {
    this.FinalData = this.bulkHoldingDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  SubmitLeakTestFilledSealedVialData(data1) {
    this.FinalData = this.leakTestData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  SubmitLeakTestFilledSealedVialThroughtMachineData(data1) {
    this.FinalData = this.leakTestFilledSealedVialThroughtMachineData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitcipCompoundingDataArray(data1) {
    this.FinalData = this.cipCompoundingDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInitialFillWeightRecordformData(data1) {
    this.FinalData = this.InitialFillWeightRecordformData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInitialChecksBeforeFilling(data1) {
    this.FinalData = this.initialChecksBeforeFillingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitreconciliationAfterPackingData(data1) {
    this.FinalData = this.reconciliationAfterPackingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitFinishedGoodsData(data1) {
    this.FinalData = this.finishedGoodsDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitreconFormData(data1) {
    this.FinalData = this.reconData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitreconciliationData(data1) {
    this.FinalData = this.reconciliationAfterPackingDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitQtyFormData(data1) {
    this.FinalData = this.qtyFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  PackingMaterialData(data1) {
    this.FinalData = this.packingMaterialDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitselectedStepsProcessingDataArray(data1) {
    this.FinalData = this.selectedStepsProcessingDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitmanufacturinProcessFormDataArray(data1) {
    this.FinalData = this.manufacturinProcessFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitfilledSyringeFormData(data1) {
    this.FinalData = this.filledSyringeFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  SubmitChangeHistoryDetails(data1) {
    this.FinalData = this.ChangeHistoryDetailsForm;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInProcessChecksDuring3PlyFluteCartonOverPrinting(data1) {
    this.FinalData = this.inProcessChecksDuring3PlyFluteCartonOverPrintingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPrecautionFiltration(data1) {
    this.FinalData = this.precautionFiltrationDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitthreePlyFluteCartonReconciliationForm(data1) {
    this.FinalData = this.threePlyFluteCartonReconciliationData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitWEIGHING_RECORD_FOR_SHIPPER(data1) {
    let temp = {};
    temp['WeighingRecordForShipper1FormData'] =
      this.WeighingRecordForShipper1FormData;
    temp['addWeighingRecordForShipper2FormData'] =
      this.addWeighingRecordForShipper2FormData;
    temp['AddWeighingRecordForShipper3FormData'] =
      this.AddWeighingRecordForShipper3FormData;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconciliationofBulkSolutionRows(data1) {
    this.FinalData = this.ReconciliationofBulkSolutionFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitjobAllocationDataSheetForm(data1) {
    this.FinalData = this.jobAllocationDataSheetFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  date = '';
  time = '';
  checkedByProductionDate = '';
  verifiedByIPQADate = '';
  submitterminalParams(data1) {
    let temp = {};
    temp['checkedByProduction'] = this.checkedByProduction;
    temp['checkedByProductionDate'] = this.checkedByProductionDate;
    temp['verifiedByIPQA'] = this.verifiedByIPQA;
    temp['verifiedByIPQADate'] = this.verifiedByIPQADate;
    temp['terminalParams'] = this.terminalParams;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitcheckpointsFormData(data1) {
    let temp = {};
    temp['previousProduct'] = this.previousProduct;
    temp['batchNo'] = this.batchNo;
    temp['date'] = this.date;
    temp['time'] = this.time;
    temp['checkpointsFormData'] = this.checkpointsFormData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitAddapicalculationArDataFormData(data1) {
    let temp = {};
    temp['calcDoneBy'] = this.calcDoneBy;
    temp['calcCheckedBy'] = this.calcCheckedBy;
    temp['AddapicalculationArDataFormData'] =
      this.AddapicalculationArDataFormData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitFillWeightForm(data1) {
    this.FinalData = this.fillWeightData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPotentialTrapPointCheck(data1) {
    let temp = {};
    temp['potentialTrapPointCheckingList'] =
      this.potentialTrapPointCheckingList;
    temp['PotentialtrappointscheckingData'] =
      this.PotentialtrappointscheckingData;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
    console.log(
      'this.SubmitRecordSpecimenOverprintingDetailsData :>> ',
      this.SubmitRecordSpecimenOverprintingDetailsData
    );
  }
  SubmitRecordSpecimenOverprintingDetails(data1) {
    let temp = {};
    temp['RecordSpecimenOverprintingDetailsData'] =
      this.RecordSpecimenOverprintingDetailsData;
    temp['RecordSpecimenOverprintingDetailsData2'] =
      this.RecordSpecimenOverprintingDetailsData2;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
    console.log(
      'this.SubmitRecordSpecimenOverprintingDetailsData :>> ',
      this.SubmitRecordSpecimenOverprintingDetailsData
    );
  }
  submitprimaryPackingData(data1) {
    this.FinalData =
      this.addPrimaryPackingRowData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInprocesschecksduringfillingandHalfstopperingasperSOPForm(data1) {
    this.FinalData =
      this.InprocesschecksduringfillingandHalfstopperingasperSOPFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitArtWorkCodeVerificationForm(data1) {
    this.FinalData = this.ArtWorkCodeVerificationFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitFillingStopStartSheet(data1) {
    this.FinalData = this.fillingStopStartSheetData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconciliationAfterFillingData(data1) {
    this.FinalData = this.ReconciliationAfterFillingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitFilterFormData(data1) {
    this.FinalData = this.filterDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitWashingDepyrogenationForm(data1) {
    this.FinalData = this.filterDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitinprocessCheckDuringPFSlabellingForm(data1) {
    this.FinalData = this.inprocessCheckDuringPFSlabellingFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitReconciliationaftersealingofvialsForm(data1) {
    this.FinalData = this.ReconciliationaftersealingofvialsData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitlabelRollSpecimenSYRINGESRecForm(data1) {
    this.FinalData = this.addlabelRollSpecimenSYRINGESRecFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPFSLabelReconciliationForm(data1) {
    this.FinalData = this.addPFSLabelReconciliationRowData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitLyophilizerFormData(data1) {
    this.FinalData = this.lyophilizerFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitsecondaryDryingFormDataArray(data1) {
    this.FinalData = this.secondaryDryingFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitlyophilizationFormDataArray(data1) {
    this.FinalData = this.lyophilizationFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitleakTestForm(data1) {
    this.FinalData = this.leakTestRecords;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitArtWorkPharmaCodeVerificationForm(data1) {
    this.FinalData = this.addArtWorkPharmaCodeVerificationRowData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitmachineSetUpForm(data1) {
    this.FinalData = this.machineSetupListData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitBPRpersonsList(data1) {
    this.FinalData = this.BPRpersonsList;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPrecautionsToBeFollowedDuringManufacturingData(data1) {
    this.FinalData = this.PrecautionsToBeFollowedDuringManufacturingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitLABELLING_INSTRUCTIONS_Form(data1) {
    this.FinalData = this.addLABELLING_INSTRUCTIONS_FormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDEVIATIONForm(data1) {
    this.FinalData = this.DeviationDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitPrecautionsDuringBulkSolutionManufacturingData(data1) {
    this.FinalData = this.PrecautionsDuringBulkSolutionManufacturingData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDestructionRejectionDeviationHistoryFormData(data1) {
    this.FinalData = this.DestructionRejectionDeviationHistoryFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitRecordSpecimenProofChecking3PlyCarton(data1) {
    this.FinalData = this.recordSpecimenProofChecking3PlyCartonData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitFillvolumeFillweightForm(data1) {
    this.FinalData = this.FillWeightEntryData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitsecondaryPackagingMaterialDetails(data1) {
    this.FinalData = this.secondaryPackagingMaterialDetails;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitJustificationforadditionalmaterialIssuanceData(data1) {
    this.FinalData = this.secondaryPackagingMaterialDetails;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitLimitFor3PlyCartonWeighingBalanceList(data1) {
    let temp = {};
    temp['LimitFor3PlyCartonWeighingBalanceList'] =
      this.LimitFor3PlyCartonWeighingBalanceList;
    temp['limitFor3PlyCartonWeighingList'] =
      this.limitFor3PlyCartonWeighingList;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitInProcessChecksDuringCartonOverPrintingFormData(data1) {
    this.FinalData = this.InProcessChecksDuringCartonOverPrintingFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitdetailOfFillingMachineAlignmentFormData(data1) {
    this.FinalData = this.detailOfFillingMachineAlignmentFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDisposalForm(data1) {
    this.FinalData = this.disposalDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitinProcessChecksData(data1) {
    this.FinalData = this.inProcessChecksData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitfillingAreaMonitoringData(data1) {
    this.FinalData = this.fillingAreaMonitoringData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitRejectionDestruction(form, data1) {
    const temp = form.value;
    this.rejectionDestructionDataArray[
      this.rejectionDestructionDataArray.length
    ] = temp;
    this.FinalData = this.inspectionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitinspectionData(data1) {
    this.FinalData = this.inspectionData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitsterilizationForm(data1) {
    this.FinalData = this.sterilizationEntries;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  submitLeftOverMaterialDataArray(data1) {
    this.FinalData = this.LeftOverMaterialDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  vialLabelReconciliation = [];
  SubmitReconciliation(data, data1) {
    let temp = data.value;
    this.vialLabelReconciliation[this.vialLabelReconciliation.length] = temp;
    this.FinalData = this.vialLabelReconciliation;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitAttachmentsFormData(data1) {
    this.FinalData = this.AttachmentsFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDISPENSING_INSTRUCTIONS_Form(data1) {
    this.FinalData = this.DISPENSING_INSTRUCTIONS_FormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitprimaryDryingFormDataArray(data1) {
    this.FinalData = this.primaryDryingFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitvacuumFormDataArray(data1) {
    this.FinalData = this.vacuumFormDataArray;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  calcDoneBy = '';
  calcCheckedBy = '';
  submitCalculationsofRawMaterialData(data1) {
    let temp = {};
    temp['CalculationsofRawMaterialData'] = this.CalculationsofRawMaterialData;
    temp['calcDoneBy'] = this.calcDoneBy;
    temp['calcCheckedBy'] = this.calcCheckedBy;
    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitdecartoningAsPerSOPFormData(data1) {
    this.FinalData = this.decartoningAsPerSOPFormData;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitProcessForLoadingForFillingForm(data1) {
    let temp = {};
    temp['rejectionQty'] = this.rejectionQty;
    temp['goodQty'] = this.goodQty;
    temp['processForLoadingForFillingFormData'] =
      this.processForLoadingForFillingFormData;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }
  submitDISPENSINGOFSECONDARYPACKINGMATERIALLINECLEARANCE(data1) {
    let temp = {};
    temp['previousProduct'] = this.previousProduct;
    temp['batchNoPrevious'] = this.batchNoPrevious;
    temp['lineClearanceDateTime'] = this.lineClearanceDateTime;
    temp['Remarks'] = this.Remarks;
    temp['checkedBy'] = this.checkedBy;
    temp['verifiedBy'] = this.verifiedBy;
    temp['addSecLineClearanceRow'] = this.addSecLineClearanceRow;

    this.FinalData = temp;
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
    this.previousProduct = '';
    this.batchNoPrevious = '';
    this.lineClearanceDateTime = '';
    this.Remarks = '';
    this.checkedBy = '';
    this.verifiedBy = '';
  }
  submitloadSterilizedMachineAccessoriesData() {
    this.FinalData = this.loadSterilizedMachineAccessoriesData;
  }
  //
  //
  //
  //
  //
  //
  //
  //
  //subtsep
  submitDEVIATIONHISTORYRows(StepId1, SubId1) {
    this.FinalData = this.DEVIATIONHISTORYRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitPrecautionDuringDispensing(StepId1, SubId1) {
    this.FinalData = this.PrecautionDispensingData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitBillofRawMaterialBOM(StepId1, SubId1) {
    this.FinalData = this.DispensingResultBOM;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitBillofPrimaryPackingMaterialBOM(StepId1, SubId1) {
    this.FinalData = this.DispensingResultBOM;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitInitialInprocessChecksofmpyDepyrogenationVialsFromFillingLine(
    StepId1,
    SubId1
  ) {
    this.FinalData =
      this.AddinitialInprocessChecksofmpyDepyrogenationVialsFromFillingLineFormData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitReconcilationofDepyrogenationVials(StepId1, SubId1) {
    this.FinalData = this.addReconcilationofDepyrogenationVialsData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitInprocessatStartoffilling(StepId1, SubId1) {
    this.FinalData = this.addInProcessStartFillingRow;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitInprocessduringfilling(StepId1, SubId1) {
    this.FinalData = this.addInprocessduringfillingRowData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitInprocessattheendoffilling(StepId1, SubId1) {
    this.FinalData = this.addInprocessattheendoffillingRowData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitLineclearanceforLabellingactivity(StepId1, SubId1) {
    this.FinalData = this.lineClearanceLabellingactivityRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitInspectionoffilledVialduringfilling(StepId1, SubId1) {
    this.FinalData = this.InspectionoffilledVialduringfillingRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitSPOTCheckRecord(StepId1, SubId1) {
    this.FinalData = this.SPOTCheckRecordRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitOpticalInspectionRecord(StepId1, SubId1) {
    this.FinalData = this.OpticalInspectionRecordRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitReInspectionRecord(StepId1, SubId1) {
    this.FinalData = this.addReInspectionRecordData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitREJECTIONANALYSIS(StepId1, SubId1) {
    this.FinalData = this.addRejectionAnalysisRowData;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitReconciliationofPrimaryPackingMaterial(StepId1, SubId1) {
    this.FinalData = this.ReconciliationofPrimaryPackingMaterialRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitReconciliationDetailsofOpticalInspection(StepId1, SubId1) {
    this.FinalData = this.opticalReconciliationRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitDisposalofOpticalrejects(StepId1, SubId1) {
    this.FinalData = this.disposalOfOpticalRejectsRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitPrimaryFiltrationDetails(StepId1, SubId1) {
    this.FinalData = this.PrimaryFiltrationDetailsRows;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }

  submitABBRIVATIONSSubstep(StepId1, SubId1) {
    this.ABBRIVATIONSList = []; // clear previous selections if any

    for (let i = 0; i < this.ABBRIVATIONS.length; i++) {
      if (this.ABBRIVATIONS[i].selected === true) {
        this.ABBRIVATIONSList.push({
          key: this.ABBRIVATIONS[i].key,
          value: this.ABBRIVATIONS[i].value,
        });
      }
    }
    this.FinalData = this.ABBRIVATIONSList;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }

  submitLineClearanceCheckpoints(data, StepId1, SubId1) {
    let temp = data.value;
    temp['lineclearance'] = this.LineClearanceChkPointsList;
    console.log('temp :>> ', temp);
    this.FinalData = temp;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }
  submitTransferandVerificationofSemifinishedGoods(data, StepId1, SubId1) {
    let temp = data.value;
    temp['TransferandVerificationofSemifinishedGoodsRows'] =
      this.TransferandVerificationofSemifinishedGoodsRows;
    console.log('temp :>> ', temp);
    this.FinalData = temp;
    const StepId = StepId1;
    const SubId = SubId1;
    this.saveSubStepFilledData(StepId, SubId);
  }

  ABBRIVATIONSList: any[] = [];
  submitABBRIVATIONS(data1) {
    this.ABBRIVATIONSList = []; // clear previous selections if any

    for (let i = 0; i < this.ABBRIVATIONS.length; i++) {
      if (this.ABBRIVATIONS[i].selected === true) {
        this.ABBRIVATIONSList.push({
          key: this.ABBRIVATIONS[i].key,
          value: this.ABBRIVATIONS[i].value,
        });
      }
    }
    this.FinalData = this.ABBRIVATIONSList;
    console.log('Submitted FinalData:', this.FinalData);
    const StepID1 = data1;
    this.saveStepFilledData(StepID1);
  }

  saveStepFilledData(StepID1) {
    this.bmrFormStorage
      .saveFillStep(
        this.selectedResult['product_code'],
        this.selectedResult['work_order_no'],
        this.selectedResult['batch_number'],
        StepID1,
        this.FinalData,
        typeof this.selectedSteps === 'string'
          ? this.selectedSteps
          : this.selectedSteps?.step,
        this.selectedResult['product_name']
      )
      .subscribe({
        next: (ok) => {
          if (ok) {
            alertify.success('Record Save Successfully');
            this.getProcessStage(this.selectedResult['product_code'], null);
          } else {
            alertify.error('Save failed (ebmr_filled_step_api.php)');
          }
        },
        error: (err) => {
          console.error('saveFillStep', err);
          alertify.error('Save failed (network or server error)');
        },
      });
  }
  saveSubStepFilledData(StepId, SubId) {
    this.bmrFormStorage
      .saveFillSubstep(
        this.selectedResult['product_code'],
        this.selectedResult['work_order_no'],
        this.selectedResult['batch_number'],
        StepId,
        SubId,
        this.FinalData,
        this.selectedResult['product_name']
      )
      .subscribe({
        next: (ok) => {
          if (ok) {
            alertify.success('Record Save Successfully');
            this.getProcessStage(this.selectedResult['product_code'], null);
          } else {
            alertify.error('Save failed (ebmr_filled_step_api.php)');
          }
        },
        error: (err) => {
          console.error('saveFillSubstep', err);
          alertify.error('Save failed (network or server error)');
        },
      });
  }

  selectedFile2: File;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  saveUploadDocs(data1) {
    const uploadData = new FormData();
    if (this.selectedFile2 !== undefined) {
      uploadData.append(
        'FinalData',
        this.selectedFile2,
        this.selectedFile2.name
      );
    }

    const StepID1 = data1;
    this.saveStepFilledDataDOCUMENT(StepID1, uploadData);
  }
  saveUploadSubstepDocs(data1, data2) {
    const uploadData = new FormData();
    if (this.selectedFile2 !== undefined) {
      uploadData.append(
        'FinalData',
        this.selectedFile2,
        this.selectedFile2.name
      );
    }

    const StepID1 = data1;
    const SubStepID1 = data2;
    this.saveStepSubstepFilledDataDOCUMENT(StepID1, SubStepID1, uploadData);
  }

  saveStepFilledDataDOCUMENT(StepID1, uploadData) {
    let temp = {};

    this.service
      .post(
        'bmr/process.php?type=update_bmr_satgesStepdOCUMENT&id=' + StepID1,
        uploadData
      )
      .subscribe((response) => {
        if (this.bmrFormStorage.isProcessPhpSaveSuccess(response)) {
          alertify.success('Record Save Successfully');
          this.getProcessStage(this.selectedResult['product_code'], null);
        } else {
          alertify.error(
            response != null && response['status'] != null
              ? response['status']
              : String(response || 'Save failed')
          );
        }
      });
  }
  saveStepSubstepFilledDataDOCUMENT(StepID1, SubStepID1, uploadData) {
    let temp = {};

    this.service
      .post(
        'bmr/process.php?type=update_bmr_satgesStepSubstepdOCUMENT&Stepid=' +
          StepID1 +
          '&Subid=' +
          SubStepID1,
        uploadData
      )
      .subscribe((response) => {
        if (this.bmrFormStorage.isProcessPhpSaveSuccess(response)) {
          alertify.success('Record Save Successfully');
          this.getProcessStage(this.selectedResult['product_code'], null);
        } else {
          alertify.error(
            response != null && response['status'] != null
              ? response['status']
              : String(response || 'Save failed')
          );
        }
      });
  }
  viewUploadedDocument(url) {
    url = this.service.url + '../../upload/BMRdOCUMENTS/' + url;
    window.open(url, '_blank');
  }
}
