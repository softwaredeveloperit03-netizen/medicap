import { AfterViewInit, Component, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import { MethodWorkflowService } from '../shared/method-workflow.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements AfterViewInit, OnDestroy {

  private readonly destroy$ = new Subject<void>();

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private wf: MethodWorkflowService
  ) {}

  plant_id = localStorage.getItem('plant_id');
  details;
  testN: any = [];
  test_master_id;
  spectTestId;
  test_id;
  method_id = '';
  material_name = '';
  material_code = '';
  specification_no = '';
  selectedMethod: any = {};
  isView = false;
  /** test = Test Specific (GTP); specification = Zuma spec-line editor */
  moaMode: 'test' | 'specification' = 'specification';

  /** Section toggles — bound from template (were previously undefined). */
  gen_ins = false;
  purp = false;
  standSoln = false;
  Test_Soln = false;
  scop = false;
  ass_docs = false;
  ref_doc = false;
  defi = false;
  safe = false;
  test_instruction = false;
  proce = false;
  chrome = false;
  equip_inst = false;
  chem_reage = false;
  glasswa = false;
  weigh_bal = false;
  Volumetric_Solutions = false;
  HPLC = false;

  /** Default for preparation modal “markup with” select. */
  prepMarkupWith = 'Water';

  /** Reusable-method detection: existing methods for the same master test. */
  reusableMethods: any[] = [];
  showReuseModal = false;
  isCopyingMethod = false;

  ngAfterViewInit() {
    const mode = String(this.route.snapshot.queryParamMap.get('moaMode') || '').toLowerCase();
    this.moaMode = mode === 'test' ? 'test' : 'specification';
    this.route.paramMap.pipe(takeUntil(this.destroy$)).subscribe((params) => {
      this.getMaterialDetails(params.get('id'));
    });
    this.getMethodDocuments();
    this.getEquipments();
    this.getChemicals();
    this.getGlasswares();
    this.getBalance();
    this.GET_vOLUMENTRIC_sOLUNTIONS();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  glasswares;
  getGlasswares() {
    this.service.get('qc/method.php?type=getGlasswares').subscribe((response) => {
        this.glasswares = response;
      });
  }

  balances;
  getBalance() {
    this.service.get('qc/method.php?type=getweighingbalance').subscribe((response) => {
        this.balances = response;
      });
  }

  methodDocuments;
  getMethodDocuments() {
    this.service.get('master/checklist.php?type=getMethodDocuments&doc_type=AssociateDoc').subscribe((response) => {
        this.methodDocuments = response;
      });
  }

  equipements;
  getEquipments() {
    this.service.get('qc/method.php?type=getQCEquipments').subscribe((response) => {
        this.equipements = response;
      });
  }

  chemicals;
  getChemicals() {
    this.service.get('common.php?type=getChemicalsForMoa').subscribe((response) => {
        this.chemicals = response;
      });
  }

  vol_solution;
  GET_vOLUMENTRIC_sOLUNTIONS() {
    this.service.get('qc/method.php?type=GET_vOLUMENTRIC_sOLUNTIONS').subscribe((response) => {
        this.vol_solution = response;
      });
  }


  
   editorConfig = {
    toolbar: [
      ['bold', 'italic', 'underline', 'strike'],  // Basic formatting
      [{ 'header': [1, 2, 3, false] }],           // Header styles
      [{ 'list': 'ordered' }, { 'list': 'bullet' }], // Lists
      [{ 'script': 'sub' }, { 'script': 'super' }], // Subscript/Superscript
      [{ 'indent': '-1' }, { 'indent': '+1' }],   // Indent
      [{ 'direction': 'rtl' }],                   // Text direction
      [{ 'size': ['small', false, 'large', 'huge'] }], // Font sizes
      [{ 'color': [] }, { 'background': [] }],    // Colors
      [{ 'font': [] }],                           // Font family
      [{ 'align': [] }],                          // Text alignment
      ['blockquote', 'code-block'],               // Blockquote, Code block
      // ['link', 'image', 'video'],                 // Insert links, images, videos
      ['clean']                                   // Remove formatting
    ]
  };
 



  

   subtest;
   test_type;
   selectedMethodControl;
          defaultChromContent = `
  <p><strong>Chromatograph:</strong></p>
  <p><strong>Column:</strong></p>
  <p><strong>Flow rate:</strong></p>
  <p><strong>Wavelength:</strong></p>
  <p><strong>Injected volume:</strong></p>
  <p><strong>Mobile phase:</strong></p>
`;
  getMaterialDetails(id: string | null) {
    if (!id) {
      this.isView = false;
      return;
    }
    // Keep query-param mode (Test Specific) before API returns.
    const qMode = String(this.route.snapshot.queryParamMap.get('moaMode') || '').toLowerCase();
    if (qMode === 'test') {
      this.moaMode = 'test';
    }
    const modeQs = this.moaMode === 'test' ? '&moa_mode=test' : '';
    this.service.get('qc/method.php?type=get_test_data&id=' + id + modeQs).subscribe({
      next: (response) => {
        // Guard against PHP fatal/HTML bodies that Angular may surface as string/null.
        if (!response || typeof response !== 'object' || Array.isArray(response)) {
          this.isView = false;
          alertify.error('Unable to load method data (invalid server response). Please try again.');
          return;
        }
        this.details = response;

        // Spec mode needs spectTestId > 0; Test Specific needs test_master_id (spectTestId may be 0).
        const hasSpecLine = Number(this.details?.['spectTestId'] || 0) > 0;
        const hasTestMaster =
          Number(this.details?.['test_master_id'] || 0) > 0 ||
          Number(this.details?.['test_id'] || 0) > 0 ||
          String(this.details?.['moa_mode'] || '') === 'test' ||
          this.moaMode === 'test';
        if (!this.details || this.details['status'] === 'failed' || (!hasSpecLine && !hasTestMaster)) {
          this.isView = false;
          alertify.error(this.details?.['message'] || 'Unable to load this test. Please reopen it from the method list.');
          return;
        }
        if (String(this.details['moa_mode'] || '') === 'test' || (!hasSpecLine && hasTestMaster)) {
          this.moaMode = 'test';
        }

        this.testN = this.details['test'];
        this.subtest = this.details['subtest'];
        this.test_type = this.details['test_type'];
        this.test_master_id = this.details['test_master_id'] || (this.moaMode === 'test' ? id : this.details['test_master_id']);
        this.spectTestId = Number(this.details['spectTestId'] || 0);
        this.test_id = this.details['test_id'] ?? (this.spectTestId || this.test_master_id);
        this.method_id = this.details['method_id'] || '';
        this.material_name = this.details['material_name'] || '';
        this.material_code = this.details['material_code'] || '';
        this.specification_no = this.details['specification_no'] || '';

        const methodsObj = this.details['methods'];
        this.selectedMethod =
          methodsObj && typeof methodsObj === 'object' && !Array.isArray(methodsObj) ? { ...methodsObj } : {};
        this.selectedMethodControl =
          methodsObj && typeof methodsObj === 'object' && !Array.isArray(methodsObj) ? { ...methodsObj } : {};
        if (!Array.isArray(this.selectedMethod['revision_history'])) {
          this.selectedMethod['revision_history'] = [];
        }

        if (!Array.isArray(this.selectedMethod['phases'])) {
          this.selectedMethod['phases'] = [];
        }

        this.normalizeRichTextSectionControls();

        const hplc = this.selectedMethod['hplc'];
        if (hplc && typeof hplc === 'object') {
          this.instrumentParameterList = Array.isArray(hplc.instrumentParameterList)
            ? [...hplc.instrumentParameterList]
            : [];
          this.refractiveIndexList = Array.isArray(hplc.refractiveIndexList)
            ? [...hplc.refractiveIndexList]
            : [];
          this.methodParameterList = Array.isArray(hplc.methodParameterList)
            ? [...hplc.methodParameterList]
            : [];
          this.retentionTimeList = Array.isArray(hplc.retentionTimeList)
            ? [...hplc.retentionTimeList]
            : [];
        } else {
          this.instrumentParameterList = [];
          this.refractiveIndexList = [];
          this.methodParameterList = [];
          this.retentionTimeList = [];
        }

        if (!sessionStorage.getItem('moa-method-paste-hint')) {
          sessionStorage.setItem('moa-method-paste-hint', '1');
          alertify
            .dialog('alert')
            .set({
              transition: 'slide',
              message:
                'Tip: Avoid pasting directly from Word. Convert to PDF first, then copy from the PDF to reduce formatting issues.',
              title: 'Content paste',
            })
            .show();
        }

        this.isView = true;

        if (this.selectedMethod['id'] != null) {
          this.get_cromatograms(this.selectedMethod['id']);
        }

        this.findReusableMethods();
      },
      error: () => {
        alertify.error('Unable to load method data. Please try again.');
        this.isView = false;
      },
    });
  }

  // ---- Reusable method detection & copy -------------------------------------
  /** Detect methods already prepared for the SAME master test on other materials. */
  findReusableMethods(): void {
    this.reusableMethods = [];
    this.showReuseModal = false;
    // Reuse copy is for Specification Specific lines only (needs spectTestId > 0).
    if (!this.test_master_id || !(Number(this.spectTestId) > 0) || this.moaMode === 'test') {
      return;
    }
    // Auto-popup only when the current test has no method of its own yet.
    const ownProc = this.selectedMethodControl ? this.selectedMethodControl['procedure'] : null;
    const hasOwnMethod = ownProc != null && String(ownProc).trim() !== '' && String(ownProc).trim().toUpperCase() !== 'NA';
    this.service
      .get(
        'qc/method.php?type=findReusableMethods&test_master_id=' +
          encodeURIComponent(this.test_master_id) +
          '&spec_test_id=' +
          encodeURIComponent(this.spectTestId)
      )
      .subscribe({
        next: (res) => {
          this.reusableMethods = Array.isArray(res) ? res : [];
          if (this.reusableMethods.length && !hasOwnMethod) {
            this.showReuseModal = true;
          }
        },
        error: () => {
          this.reusableMethods = [];
        },
      });
  }

  openReuseModal(): void {
    if (this.reusableMethods.length) {
      this.showReuseModal = true;
    }
  }

  closeReuseModal(): void {
    this.showReuseModal = false;
  }

  /** Copy a detected method's full content into the current form for review + save. */
  copyMethod(row: any): void {
    const srcId = row?.spec_test_id;
    if (!srcId) {
      return;
    }
    this.isCopyingMethod = true;
    this.service.get('qc/method.php?type=get_test_data&id=' + srcId).subscribe({
      next: (res) => {
        this.isCopyingMethod = false;
        const src = res && res['methods'] ? res['methods'] : null;
        if (!src) {
          alertify.error('Could not read the selected method.');
          return;
        }
        this.applyCopiedMethod(src);
        this.showReuseModal = false;
        alertify.success('Method copied from ' + (row.specification_no || 'source') + '. Review each section and Save.');
      },
      error: () => {
        this.isCopyingMethod = false;
        alertify.error('Could not copy the selected method.');
      },
    });
  }

  private hasCopyContent(v: any): boolean {
    return v != null && String(v).trim() !== '' && String(v).trim().toUpperCase() !== 'NA';
  }

  /** Rich-text sections use selectedMethodControl === 'NA' to show the quill editor. */
  isRichTextEditable(field: string): boolean {
    const control = this.selectedMethodControl ? this.selectedMethodControl[field] : null;
    return control == null || control === 'NA' || String(control).trim() === '';
  }

  private isRichTextEmpty(value: any): boolean {
    return value == null || String(value).trim() === '' || String(value).trim().toUpperCase() === 'NA';
  }

  private normalizeRichTextSectionControls(): void {
    const fields = [
      'procedure',
      'Safety',
      'chromatographic_conditions',
      'Test_Solutions',
      'standard_Solutions',
      'purpose',
      'Scope',
    ];
    for (const field of fields) {
      const val = this.selectedMethod[field];
      if (this.isRichTextEmpty(val)) {
        if (field === 'chromatographic_conditions') {
          this.selectedMethod[field] = this.defaultChromContent;
        } else {
          this.selectedMethod[field] = '';
        }
        this.selectedMethodControl[field] = 'NA';
      } else if (this.moaMode === 'test') {
        this.selectedMethodControl[field] = 'NA';
      } else {
        this.selectedMethodControl[field] = val;
      }
    }
  }

  private applyCopiedMethod(src: any): void {
    const arr = (v: any) => (Array.isArray(v) ? JSON.parse(JSON.stringify(v)) : []);
    const txt = (v: any) => (v == null || v === 'NA' ? '' : v);

    this.selectedMethod['procedure'] = txt(src['procedure']);
    this.selectedMethod['Safety'] = txt(src['Safety']);
    this.selectedMethod['chromatographic_conditions'] = txt(src['chromatographic_conditions']) || this.defaultChromContent;
    this.selectedMethod['Test_Solutions'] = txt(src['Test_Solutions']);
    this.selectedMethod['standard_Solutions'] = txt(src['standard_Solutions']);
    this.selectedMethod['purpose'] = txt(src['purpose']);
    this.selectedMethod['Scope'] = txt(src['Scope']);

    this.selectedMethod['Genral_Instruction'] = arr(src['Genral_Instruction']);
    this.selectedMethod['Associative_Document'] = arr(src['Associative_Document']);
    this.selectedMethod['Refrenced_Document'] = arr(src['Refrenced_Document']);
    this.selectedMethod['defination'] = arr(src['defination']);
    this.selectedMethod['testinginstruction'] = arr(src['testinginstruction']);
    this.selectedMethod['equipment_instruments'] = arr(src['equipment_instruments']);
    this.selectedMethod['chemical_reagents'] = arr(src['chemical_reagents']);
    this.selectedMethod['glasswares'] = arr(src['glasswares']);
    this.selectedMethod['balance'] = arr(src['balance']);
    this.selectedMethod['dilutions'] = arr(src['dilutions']);
    this.selectedMethod['volumetric_solutions'] = arr(src['volumetric_solutions']);
    this.selectedMethod['phases'] = arr(src['phases']);
    this.selectedMethod['hplc'] = src['hplc'] && typeof src['hplc'] === 'object' ? JSON.parse(JSON.stringify(src['hplc'])) : {};

    // Rich-text editors render when their control value is 'NA'.
    this.selectedMethodControl['procedure'] = 'NA';
    this.selectedMethodControl['Safety'] = 'NA';
    this.selectedMethodControl['chromatographic_conditions'] = 'NA';

    const hplc = this.selectedMethod['hplc'];
    this.instrumentParameterList = hplc && Array.isArray(hplc.instrumentParameterList) ? [...hplc.instrumentParameterList] : [];
    this.refractiveIndexList = hplc && Array.isArray(hplc.refractiveIndexList) ? [...hplc.refractiveIndexList] : [];
    this.methodParameterList = hplc && Array.isArray(hplc.methodParameterList) ? [...hplc.methodParameterList] : [];
    this.retentionTimeList = hplc && Array.isArray(hplc.retentionTimeList) ? [...hplc.retentionTimeList] : [];

    // Reveal the sections that received content so the user can review them.
    this.chrome = this.hasCopyContent(this.selectedMethod['chromatographic_conditions']);
    this.safe = this.hasCopyContent(this.selectedMethod['Safety']);
    this.proce = this.hasCopyContent(this.selectedMethod['procedure']);
    this.test_instruction = (this.selectedMethod['testinginstruction'] || []).length > 0;
    this.equip_inst = (this.selectedMethod['equipment_instruments'] || []).length > 0;
    this.chem_reage = (this.selectedMethod['chemical_reagents'] || []).length > 0;
    this.glasswa = (this.selectedMethod['glasswares'] || []).length > 0;
    this.weigh_bal = (this.selectedMethod['balance'] || []).length > 0;
    this.Volumetric_Solutions = (this.selectedMethod['volumetric_solutions'] || []).length > 0;
    this.HPLC =
      this.instrumentParameterList.length > 0 ||
      this.methodParameterList.length > 0 ||
      this.retentionTimeList.length > 0 ||
      this.refractiveIndexList.length > 0 ||
      (this.selectedMethod['phases'] || []).length > 0;
  }

  
  addGen_ins(data) {
    if (!data.value) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.selectedMethod['Genral_Instruction'].push(temp);  
    data.reset();
  }
  
  del_addGen_ins(index) {
    this.selectedMethod['Genral_Instruction'].splice(index, 1);
  }

  selectedAssociateDoc: any = [];

  selectedAssocDoc(event: Event, docType: string) {
    const select = event.target as HTMLSelectElement;
    const value = select.value;
    if (value === '__ADD_NEW__') {
      select.value = '';
      this.selectedAssociateDoc = [];
      this.addNewDocument(docType);
      return;
    }
    if (!value || !this.methodDocuments?.length) {
      this.selectedAssociateDoc = [];
      return;
    }
    const doc = this.methodDocuments.find((d: any) => String(d.doc_no) === value);
    this.selectedAssociateDoc = doc || [];
  }


  addAssociateDoc() {
    if (!this.selectedAssociateDoc?.doc_no) {
      alertify.error('Please select a document first.');
      return;
    }
    const temp = {
      doc_no: this.selectedAssociateDoc['doc_no'],
      doc_name: this.selectedAssociateDoc['doc_name'],
    };
    this.selectedMethod['Associative_Document'].push(temp);
    this.selectedAssociateDoc = [];
  }

  deleteAssocDoc(index) {
    this.selectedMethod['Associative_Document'].splice(index, 1);
  }

  addRefDoc() {
    if (!this.selectedAssociateDoc?.doc_no) {
      alertify.error('Please select a document first.');
      return;
    }
    const temp = {
      doc_no: this.selectedAssociateDoc['doc_no'],
      doc_name: this.selectedAssociateDoc['doc_name'],
    };
    this.selectedMethod['Refrenced_Document'].push(temp);
    this.selectedAssociateDoc = [];
  }

  deleteRefDoc(index) {
    this.selectedMethod['Refrenced_Document'].splice(index, 1);
  }


  term = '';
  defination = '';

 
  addDefination() {
    const term = (this.term || '').trim();
    const def = (this.defination || '').trim();
    if (!term || !def) {
      alertify.error('Please enter both term and definition.');
      return;
    }
    this.selectedMethod['defination'].push({ term, defination: def });
    this.term = '';
    this.defination = '';
  }

  delDefination(index) {
    this.selectedMethod['defination'].splice(index, 1);
  }

  testing_instruction = '';
 
  testinginstruction = [];
  testinginstructiondemo = [];

  addinstruction() {
    const text = (this.testing_instruction || '').trim();
    if (!text) {
      alertify.error('Please enter a sub point before adding.');
      return;
    }
    this.testinginstructiondemo.push({ testing_instruction: text });
    this.testing_instruction = '';
  }
  deleteinstruction(index) {
    this.testinginstructiondemo.splice(index, 1);
  }


  testing_heading = '';

  addMaininstruction() {

    if (this.testing_heading == '') {
      alertify.error('All fields are required!');
      return;
    }

    let temp = {};
    temp['testing_heading'] = this.testing_heading;
    temp['testing_instruction'] = this.testinginstructiondemo;
    this.selectedMethod['testinginstruction'].push(temp);
    this.testing_heading = '';
    this.testinginstructiondemo = [];
  }
  deleteMaininstruction(index) {
    this.selectedMethod['testinginstruction'].splice(index, 1);
  }



  selectedEquipment: any = null;

  getEquipmentDetails(index: number) {
    if (index < 1 || !this.equipements?.length) {
      this.selectedEquipment = null;
      return;
    }
    this.selectedEquipment = this.equipements[index - 1];
  }




  equipment_name = '';

  addEquipment() {
    if (this.equipment_name == '' || !this.selectedEquipment?.id) {
      alertify.error('Please select equipment from the list.');
      return;
    }

    let temp = {};
    temp['equipment_type'] = this.selectedEquipment['equipment_type'];
    temp['equipment_name'] = this.selectedEquipment['equipment_name'];
    temp['equipment_code'] = this.selectedEquipment['equipment_code'];
    temp['id'] = this.selectedEquipment['id'];
    this.selectedMethod['equipment_instruments'].push(temp);
    this.selectedEquipment = null;
    this.equipment_name = '';
  }


  deleteeq(index) {
    this.selectedMethod['equipment_instruments'].splice(index, 1);
  }

  selectedChemical: any = null;
  getChemicalDetails(index: number) {
    if (index < 1 || !this.chemicals?.length) {
      this.selectedChemical = null;
      return;
    }
    this.selectedChemical = this.chemicals[index - 1];
  }

  chemName = '';


  addChemical() {
    if (this.chemName === '' || !this.selectedChemical?.material_name) {
      alertify.error('Please select a chemical from the list.');
      return;
    }

    let temp = {};
    temp['material_name'] = this.selectedChemical['material_name'];
    temp['material_code'] = this.selectedChemical['material_code'];
    temp['grade'] = this.selectedChemical['grade'];
    temp['make'] = this.selectedChemical['make'];
    this.selectedMethod['chemical_reagents'].push(temp);
    this.selectedChemical = null;
    this.chemName = '';
  }


  deleteChem(index) {
    this.selectedMethod['chemical_reagents'].splice(index, 1);
  }



  selectedGlassware: any = null;

  getGlasswareDetails(index: number) {
    if (index < 1 || !this.glasswares?.length) {
      this.selectedGlassware = null;
      return;
    }
    this.selectedGlassware = this.glasswares[index - 1];
  }
  

  glassware_name = '';
  addGlassware() {
    if (this.glassware_name === '' || !this.selectedGlassware?.material_name) {
      alertify.error('Please select glassware from the list.');
      return;
    }
    let temp = {};
    temp['material_name'] = this.selectedGlassware['material_name'];
    temp['capacity'] = this.selectedGlassware['capacity'];
    temp['class_type'] = this.selectedGlassware['class_type'];
    temp['material_code'] = this.selectedGlassware['material_code'];
    this.selectedMethod['glasswares'].push(temp);
    this.selectedGlassware = null;
    this.glassware_name = '';
  }

  deleteGlass(index){
    this.selectedMethod['glasswares'].splice(index,1);
  }


  selectedBalance: any = null;
  getBalanceDetails(index: number) {
    if (index < 1 || !this.balances?.length) {
      this.selectedBalance = null;
      return;
    }
    this.selectedBalance = this.balances[index - 1];
  }

  balance_name = '';

  addBalance() {
    if (this.balance_name === '' || !this.selectedBalance?.equipment_name) {
      alertify.error('Please select a balance from the list.');
      return;
    }
    let temp = {};
    temp['equipment_name'] = this.selectedBalance['equipment_name'];
    temp['make'] = this.selectedBalance['make'];
    temp['capacity'] = this.selectedBalance['capacity'];
    temp['equipment_code'] = this.selectedBalance['equipment_code'];
    this.selectedMethod['balance'].push(temp);
    this.selectedBalance = null;
    this.balance_name = '';
  }

  deleteBalData(index){
    this.selectedMethod['balance'].splice(index,1);
  }

  selectedSolution: any = null;
  getSolutionDetails(index: number) {
    if (index < 1 || !this.vol_solution?.length) {
      this.selectedSolution = null;
      return;
    }
    this.selectedSolution = this.vol_solution[index - 1];
  }
  solution_name = '';
  addVolumetric_Solutions() {
    if (this.solution_name === '' || !this.selectedSolution?.solution_name) {
      alertify.error('Please select a solution from the list.');
      return;
    }
    let temp = {};
    temp['percentage'] = this.selectedSolution['percentage'];
    temp['unit'] = this.selectedSolution['unit'];
    temp['strength'] = this.selectedSolution['strength'];
    temp['standard_type'] = this.selectedSolution['solution_type'];
    temp['solution_name'] = this.selectedSolution['solution_name'];
    temp['solution_no'] = this.selectedSolution['solution_no'];
    this.selectedMethod['volumetric_solutions'].push(temp);
    this.solution_name = '';
    this.selectedSolution = null;
  }

  delVolumetric_SolutionsList(index){
    this.selectedMethod['volumetric_solutions'].splice(index,1);
  }


instrumentParameterList: any[] = [];

  refractiveIndexList : any[] = [];
  retentionTimeList : any[] = [];
  methodParameterList : any[] = [];

  addInstrumentParameter(data) {
    if (!Array.isArray(this.instrumentParameterList)) {
  this.instrumentParameterList = [];
}
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.instrumentParameterList.push(temp);
    data.reset();
  }

  delInstrumentParameter(index){
    this.instrumentParameterList.splice(index,1);
  }



  addRefractiveIndex(data) {
        if (!Array.isArray(this.refractiveIndexList)) {
  this.refractiveIndexList = [];
}
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.refractiveIndexList.push(temp);
    data.reset();
  }

  delRefractiveIndex(index){
    this.refractiveIndexList.splice(index,1);
  }

 

  addMethodParameter(data) {
    if (!Array.isArray(this.methodParameterList)) {
      this.methodParameterList = [];
    }
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.methodParameterList.push(temp);
    data.reset();
  }

  delMethodParameter(index){
    this.methodParameterList.splice(index,1);
  }



  addRetentionTime(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.retentionTimeList.push(temp);
    data.reset();
  }

  delRetentionTime(index){
    this.retentionTimeList.splice(index,1);
  }








 

  saveMethodData(check) {
    const specId = Number(this.spectTestId || 0);
    const masterId = Number(this.test_master_id || 0);
    if (this.moaMode === 'test') {
      if (!masterId) {
        alertify.error('Cannot save: test master is missing. Please reopen from the method list.');
        return;
      }
    } else if (!specId) {
      alertify.error('Cannot save: this test is not loaded correctly. Please reopen it from the method list.');
      return;
    }

    let temp = {};
    temp['spectTestId'] = specId;
    temp['test_master_id'] = masterId;
    temp['moa_mode'] = this.moaMode;
    temp['check'] = check;

    if(check == 'General Instructions'){
      temp['Genral_Instruction'] = this.selectedMethod['Genral_Instruction'];
    }
    else if(check == 'Purpose'){
      temp['purpose'] = this.selectedMethod['purpose'];
    }
    else if(check == 'Test_Solutions'){
      temp['Test_Solutions'] = this.selectedMethod['Test_Solutions'];
    }
    else if(check == 'chromatographic_conditions'){
      temp['chromatographic_conditions'] = this.selectedMethod['chromatographic_conditions'];
    }
    else if(check == 'standard_Solutions'){
      temp['standard_Solutions'] = this.selectedMethod['standard_Solutions'];
    }
    else if(check == 'Scope'){
      temp['Scope'] = this.selectedMethod['Scope'];
    }
    else if(check == 'Associate Documents'){
      temp['Associative_Document'] = this.selectedMethod['Associative_Document'];
    }
    else if(check == 'Reference Documents'){
      temp['Refrenced_Document'] = this.selectedMethod['Refrenced_Document'];
    }
    else if(check == 'Definition'){
      temp['defination'] = this.selectedMethod['defination'];
    }
    else if(check == 'Safety'){
      temp['Safety'] = this.selectedMethod['Safety'];
    }
    else if(check == 'Testing Instruction'){
      temp['testinginstruction'] = this.selectedMethod['testinginstruction'];
    }
    else if(check == 'Procedure'){
      temp['procedure'] = this.selectedMethod['procedure'];
    }
    else if(check == 'Equipment Instruments'){
      temp['equipment_instruments'] = this.selectedMethod['equipment_instruments'];
    }
    else if(check == 'Chemical Reagents'){
      temp['chemical_reagents'] = this.selectedMethod['chemical_reagents'];
    }
    else if(check == 'Glasswares'){
      temp['glasswares'] = this.selectedMethod['glasswares'];
    }
    else if(check == 'Weighing Balance'){
      temp['balance'] = this.selectedMethod['balance'];
    }
    else if(check == 'Volumetric Solutions'){
      temp['volumetric_solutions'] = this.selectedMethod['volumetric_solutions'];
    }
    else if(check == 'HPLCData'){
 
      temp['phases'] =  this.selectedMethod['phases'];

      let jadu = {};

      jadu['instrumentParameterList'] = this.instrumentParameterList;
      jadu['refractiveIndexList'] = this.refractiveIndexList;
      jadu['methodParameterList'] = this.methodParameterList;
      jadu['retentionTimeList'] = this.retentionTimeList;
  
      temp['hplc'] = jadu;
    }

     
    this.service.post('qc/method.php?type=saveTestMethodMasterDraft', JSON.stringify(temp)).subscribe({
      next: (response) => {
        if (response['status'] === 'success') {
          alertify.success(check + ' saved successfully.');
          const reloadId = specId > 0 ? String(specId) : String(masterId);
          this.getMaterialDetails(reloadId);
        } else {
          alertify.error(response?.['message'] || 'Could not save this section. Please try again.');
        }
      },
      error: () => alertify.error('Network error while saving. Please try again.'),
    });
  }

  

    isNewDoc = 0;

    addNewDocument(docType: string) {
      if (this.isNewDoc !== 0) {
        return;
      }
      const type = docType === 'ReferenceDoc' ? 'Reference' : 'Associate';
      if (!confirm('Add a new ' + type + ' document?')) {
        return;
      }
      const name = (prompt(type + ' document name:') || '').trim();
      if (!name) {
        alertify.error('Document name is required.');
        return;
      }
      this.isNewDoc = 1;
      this.service
        .get(
          'master/checklist.php?type=saveMethodDocuments&doc_name=' +
            encodeURIComponent(name) +
            '&docType=' +
            encodeURIComponent(docType)
        )
        .subscribe({
          next: (response) => {
            this.isNewDoc = 0;
            if (response['status'] === 'success') {
              this.getMethodDocuments();
              this.selectedAssociateDoc = [];
              alertify.success('Document added.');
            } else {
              alertify.error('Could not add document. Please try again.');
            }
          },
          error: () => {
            this.isNewDoc = 0;
            alertify.error('Network error. Please try again.');
          },
        });
    }


    cromatogramFile: File | null = null;

    onFileChangedCromatograms(event: Event) {
      const input = event.target as HTMLInputElement;
      this.cromatogramFile = input.files?.[0] ?? null;
    }

    addCromatograms(data) {
      if (!this.cromatogramFile) {
        alertify.error('Please choose a file to upload.');
        return;
      }
      const uploadData = new FormData();
      uploadData.append('cromatograms', this.cromatogramFile, this.cromatogramFile.name);
      this.service
        .post(
          'qc/method.php?type=save_cromatograms&test_method_no=' + this.selectedMethod['id'],
          uploadData
        )
        .subscribe({
          next: (response) => {
            if (response['status'] === 'success') {
              alertify.success('Chromatogram saved.');
              data.reset();
              this.cromatogramFile = null;
              this.get_cromatograms(this.selectedMethod['id']);
            } else {
              alertify.error('Upload failed. Please try again.');
            }
          },
          error: () => alertify.error('Network error during upload.'),
        });
    }


    cromatograms_list;
    get_cromatograms(test_method_no) {
      this.service.get('qc/method.php?type=get_cromatograms&test_method_no=' + test_method_no).subscribe((response) => {
          this.cromatograms_list = response;
      });
    }


    viewCromatograms(url) {
      url = this.service.url + '../../upload/cromatograms/' + url;
      window.open(url, '_blank');
    }





    delCromatograms(id) {
      this.service.get('qc/method.php?type=delcromatograms&id=' + id).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Chromatogram deleted.');
            this.get_cromatograms(this.selectedMethod['id']);
          } else {
            alertify.error('Failed: An error occured, Please try again!');
          }
        });
    }


    isPreparation = false;
    preparation ="";
    phases = [];
    solvent_name = '';

    savePreparation(data) {
      if (!data.valid) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      this.preparation = "Accurately Weigh/measure "+temp['accurate_weigh']+" "+
      temp['weight_unit']+" Of "+this.solvent_name+" add to "+temp['add_to']+" ml volumetric flask, markup volume with "+
      temp['markup_with']+" Adjust pH "+temp['adjust_ph']+" with "+temp['adjust_ph_with'];

      data.reset();
      this.prepMarkupWith = 'Water';
      this.isPreparation = false;
    }

    addMobilePhase(data) {
      if (!data.value) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      temp['preparation'] = this.preparation;
      this.selectedMethod['phases'].push(temp);
      data.reset();
    }


    deletphases(index){
      this.selectedMethod['phases'].splice(index , 1);
    }

    addrevisionHistory(data) {
      if (!data.value) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      this.selectedMethod['revision_history'].push(temp);
      data.reset();
    }


    delRevisionHistory(index){
      this.selectedMethod['revision_history'].splice(index , 1);
    }

 

    saveTestMethodMaster() {
      if (this.moaMode !== 'test' && !this.spectTestId) {
        alertify.error('Cannot save the method: this test is not loaded correctly. Please reopen it from the method list.');
        return;
      }
      if (this.moaMode === 'test' && !this.test_master_id) {
        alertify.error('Cannot save the method: test master is missing.');
        return;
      }

      const hplcPayload = {
        instrumentParameterList: this.instrumentParameterList || [],
        refractiveIndexList: this.refractiveIndexList || [],
        methodParameterList: this.methodParameterList || [],
        retentionTimeList: this.retentionTimeList || [],
      };

      const temp: any = {
        spectTestId: this.spectTestId || 0,
        test_master_id: this.test_master_id,
        revision_history: this.selectedMethod['revision_history'] || [],
        moa_mode: this.moaMode,
        // Persist full body on final Save — section Save alone was easy to miss.
        procedure: this.selectedMethod['procedure'] || '',
        Safety: this.selectedMethod['Safety'] || '',
        chromatographic_conditions: this.selectedMethod['chromatographic_conditions'] || '',
        purpose: this.selectedMethod['purpose'] || '',
        Scope: this.selectedMethod['Scope'] || '',
        Test_Solutions: this.selectedMethod['Test_Solutions'] || '',
        standard_Solutions: this.selectedMethod['standard_Solutions'] || '',
        Genral_Instruction: this.selectedMethod['Genral_Instruction'] || [],
        Associative_Document: this.selectedMethod['Associative_Document'] || [],
        Refrenced_Document: this.selectedMethod['Refrenced_Document'] || [],
        defination: this.selectedMethod['defination'] || [],
        testinginstruction: this.selectedMethod['testinginstruction'] || [],
        equipment_instruments: this.selectedMethod['equipment_instruments'] || [],
        chemical_reagents: this.selectedMethod['chemical_reagents'] || [],
        glasswares: this.selectedMethod['glasswares'] || [],
        balance: this.selectedMethod['balance'] || [],
        volumetric_solutions: this.selectedMethod['volumetric_solutions'] || [],
        phases: this.selectedMethod['phases'] || [],
        hplc: hplcPayload,
      };

      const chrom = String(temp.chromatographic_conditions || '').replace(/\s+/g, ' ').trim();
      const defaultChrom = String(this.defaultChromContent || '').replace(/\s+/g, ' ').trim();
      const chromIsReal = this.hasCopyContent(temp.chromatographic_conditions) && chrom !== defaultChrom;

      const hasBody =
        this.hasCopyContent(temp.procedure) ||
        this.hasCopyContent(temp.Safety) ||
        chromIsReal ||
        this.hasCopyContent(temp.purpose) ||
        this.hasCopyContent(temp.Scope) ||
        (Array.isArray(temp.testinginstruction) && temp.testinginstruction.length > 0) ||
        (Array.isArray(temp.equipment_instruments) && temp.equipment_instruments.length > 0) ||
        (Array.isArray(temp.chemical_reagents) && temp.chemical_reagents.length > 0) ||
        (Array.isArray(temp.glasswares) && temp.glasswares.length > 0) ||
        (Array.isArray(temp.balance) && temp.balance.length > 0) ||
        (Array.isArray(temp.volumetric_solutions) && temp.volumetric_solutions.length > 0) ||
        (Array.isArray(temp.phases) && temp.phases.length > 0) ||
        (hplcPayload.instrumentParameterList || []).length > 0 ||
        (hplcPayload.methodParameterList || []).length > 0;

      if (!hasBody) {
        alertify.error(
          'No method content found. Open at least one section (Procedure, Safety, Equipment, HPLC…), enter data, then click Save method.'
        );
        return;
      }

      this.service.post('qc/method.php?type=saveTestMethodMaster111', JSON.stringify(temp)).subscribe({
        next: (response) => {
          if (response['status'] !== 'success') {
            alertify.error(response?.['message'] || 'Could not save the method. Please try again.');
            return;
          }
          if (this.moaMode === 'test') {
            alertify.success('Method saved and sent for checking.');
            this.router.navigate(['/master/moa-stp-copy/checking'], { queryParams: { scope: 'test' } });
            return;
          }
          this.wf.submitForReview(this.spectTestId).subscribe(() => {
            alertify.success('Method saved and sent for review.');
            this.router.navigate(['/master/moa-stp-copy/checking'], { queryParams: { scope: 'specification' } });
          });
        },
        error: () => alertify.error('Network error while saving the method.'),
      });
    }























  textContent: string = '';   // ✅ define the variable

  isShown22: boolean = false;  
  isShown1: boolean = false;  
  isShown33: boolean = false;  
  isShown44: boolean = false;  
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }
  isShown2: boolean = false;  
  toggleShow2() {
    this.isShown2 = !this.isShown2;
  }
  toggleShow22() {
    this.isShown22 = !this.isShown22;
  }
  toggleShow33() {
    this.isShown33 = !this.isShown33;
  }
  toggleShow44() {
    this.isShown44 = !this.isShown44;
  }
  isShown3: boolean = false;  
  toggleShow3() {
    this.isShown3 = !this.isShown3;
  }
  isShown4: boolean = false;  
  toggleShow4() {
    this.isShown4 = !this.isShown4;
  }

  isShown5: boolean = false;  
  toggleShow5() {
    this.isShown5 = !this.isShown5;
  }

  isShown6: boolean = false;  
  toggleShow6() {
    this.isShown6 = !this.isShown6;
  }
  isShown7: boolean = false;  
  toggleShow7() {
    this.isShown7 = !this.isShown7;
  }

  isShown8: boolean = false;  
  toggleShow8() {
    this.isShown8 = !this.isShown8;
  }

  isShown9: boolean = false;  
  toggleShow9() {
    this.isShown9 = !this.isShown9;
  }

  isShown10: boolean = false;  
  toggleShow10() {
    this.isShown10 = !this.isShown10;
  }

  isShown11: boolean = false;  
  toggleShow11() {
    this.isShown11 = !this.isShown11;
  }

  isShown12: boolean = false;  
  toggleShow12() {
    this.isShown12 = !this.isShown12;
  }

  isShown13: boolean = false;  
  toggleShow13() {
    this.isShown13 = !this.isShown13;
  }

  isShown14: boolean = false;  
  toggleShow14() {
    this.isShown14 = !this.isShown14;
  }

  isShown15: boolean = false;  
  toggleShow15() {
    this.isShown15 = !this.isShown15;
  }



 
 
}
