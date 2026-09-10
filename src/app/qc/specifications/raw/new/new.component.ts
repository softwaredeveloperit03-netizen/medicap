import {Component,OnInit} from '@angular/core';
import { Router } from '@angular/router';
 
import { DataAccessService } from 'src/app/data-access.service';
import { SpecificationFormCustomisationService } from 'src/app/qa/soft-restriction/specification-form-customisation/specification-form-customisation.service';
import { SpecificationScope } from 'src/app/qa/soft-restriction/specification-form-customisation/specification-form-customisation.constants';
declare let alertify;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {



  public  testType=[
    { value: '', label: '' },
    { value: 'Chemical', label: 'Chemical' },
    { value: 'Microbiology', label: 'Microbiology' },
    { value: 'Physcial', label: 'Physical Observation' },
    { value: 'Packing', label: 'Packing' },
    { value: 'Water', label: 'Water' },
    { value: 'Indentification', label: 'Indentification' },
    { value: 'LOD', label: 'LOD (Loss on Drying)' },
  ];

 
  plant_id = localStorage.getItem('plant_id');

 
  constructor(
    private service: DataAccessService,
    private router: Router,
    private specCustomisationService: SpecificationFormCustomisationService
  ){
    this.plant_id = localStorage.getItem('plant_id');
  }


  ngOnInit(): void {
    this.plant_id = localStorage.getItem('plant_id');
    this.getUnits();
    this.getSubMaterials();

    if(localStorage.getItem('plant_id') == '181'){
      this.sampling_plan = 'Test Specific';
    }else{
      this.sampling_plan = 'Fixed';
    }
    this.loadSpecCustomisation();
  }

  units: any[] = [];
  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe((response: any) => {
      this.units = Array.isArray(response) ? response : [];
    });
  }


  material_type = 'Raw Material';
  activeFieldMap: { [key: string]: any } = {};
  dynamicAdditionalFields: any[] = [];
  dynamicFieldValues: { [key: string]: any } = {};

  private getScopeByMaterialType(): SpecificationScope {
    const mt = String(this.material_type || '').trim();
    if (mt === 'Packing Material') {
      return 'Packing Material';
    }
    if (mt === 'Finish Product') {
      return 'Finish Product';
    }
    return 'Raw Material';
  }

  private loadSpecCustomisation(): void {
    const scope = this.getScopeByMaterialType();
    this.specCustomisationService.getActiveLayout(scope).subscribe({
      next: (res: any) => {
        const rows = Array.isArray(res?.fields) ? res.fields : [];
        const map: { [key: string]: any } = {};
        rows.forEach((r: any) => {
          if (r?.field_key) {
            map[String(r.field_key)] = r;
          }
        });
        this.activeFieldMap = map;
        this.dynamicAdditionalFields = rows.filter((r: any) => this.isAdditionalCustomField(String(r?.field_key || '')));
      },
      error: () => {
        this.activeFieldMap = {};
        this.dynamicAdditionalFields = [];
      },
    });
  }

  private isAdditionalCustomField(key: string): boolean {
    const defaults = [
      'specIs', 'spec_type', 'material_subtype', 'material_name', 'material_code', 'material_grade',
      'specification_no', 'supersede_no', 'version_no', 'issuedDate', 'review_date', 'effective_date',
      'retest_period', 'sampling_plan', 'test_method_no', 'storage_condition', 'samplingDetails',
      'hazardAndPrecautions', 'stockTransfer', 'note', 'sample_qty', 'control_sample', 'additional_sample',
      'totalsample_qty', 'unit', 'reference_type', 'outside_testing', 'retest', 'release_stability',
      'bulk_release', 'sto', 'change_mode', 'reason'
    ];
    return defaults.indexOf(key) === -1;
  }

  getFieldLabel(fieldKey: string, fallback: string): string {
    const row = this.activeFieldMap[fieldKey];
    return row?.field_label || fallback;
  }

  isFieldApplicable(fieldKey: string): boolean {
    const row = this.activeFieldMap[fieldKey];
    if (!row) {
      return true;
    }
    return String(row.applicable || 'Applicable') !== 'Not Applicable';
  }

  getDynamicFieldOptions(cf: any): string[] {
    const raw = String(cf?.field_options || '').trim();
    if (raw) {
      const options = raw
        .split(/[,|\n;\/]+/)
        .map((x) => String(x || '').trim())
        .filter((x) => x.length > 0);
      if (options.length > 0) {
        return options;
      }
    }
    if (String(cf?.field_type || '') === 'YESNO') {
      return ['YES', 'No'];
    }
    return ['Applicable', 'Not Applicable'];
  }

  sub_types: any[] = [];
  getSubMaterials() {
      // Reset materials and subtype when material type changes
      this.materials = [];
      this.material_subtype = '';
      this.selectedItem = {};
      this.selMaterialGrade = [];
      this.selectedMaterialCode = '';
      
      if (!this.material_type) {
        this.sub_types = [];
        return;
      }
      
      const url = 'master/materialtype.php?type=getSubMaterials&material_type=' + encodeURIComponent(this.material_type);
      
      this.service.getJsonArray(url).subscribe((response: any) => {
            this.sub_types = Array.isArray(response) ? response : [];
            this.loadSpecCustomisation();
            this.loadNextSpecificationNo();
      }, () => {
          this.sub_types = [];
      });
   }
 
   category = '';
  getSubMaterials1() {
      // Reset materials and subtype when category changes
      this.materials = [];
      this.material_subtype = '';
      this.selectedItem = {};
      this.selMaterialGrade = [];
      this.selectedMaterialCode = '';
      
      this.service.get('master/materialtype.php?type=getSubMaterials&material_type=' + this.category).subscribe((response: any) => {
          this.sub_types = Array.isArray(response) ? response : [];
          this.loadSpecCustomisation();
      });
   }

  material_subtype = '';
  materials: any[] = [];
  loadingMaterials = false;
  selectedMaterialCode = '';

  getMaterialsBySubType() {
    this.materials = [];
    this.selectedItem = {};
    this.selMaterialGrade = [];
    this.selectedMaterialCode = '';

    const materialType = String(this.material_type || '').trim();
    const materialSubtype = String(this.material_subtype || '').trim();
    if (!materialType || !materialSubtype || materialSubtype === '[object Object]') {
      return;
    }

    this.loadingMaterials = true;
    // Same source as Master → Material (master/material.php getMaterials).
    this.service
      .getJsonArray(
        'master/material.php?type=getMaterials&material_type=' + encodeURIComponent(materialType)
      )
      .subscribe({
        next: (rows) => {
          const all = Array.isArray(rows) ? rows : [];
          const sub = materialSubtype.toLowerCase();
          const matched = all.filter((m) =>
            String(m?.material_subtype || '')
              .trim()
              .toLowerCase() === sub
          );
          this.materials = matched.length ? matched : all;
          this.loadingMaterials = false;
        },
        error: () => {
          this.materials = [];
          this.loadingMaterials = false;
        },
      });
  }
 
  getMaterialsBySubType1() {
    // Reset materials when subtype changes
    this.materials = [];
    this.selectedItem = {};
    this.selMaterialGrade = [];
    
    if (!this.category || !this.material_subtype) {
      console.log('Missing category or material_subtype:', { category: this.category, material_subtype: this.material_subtype });
      return;
    }
    
    const url = 'common.php?type=getMaterialsByTypeByCategory&category=' + encodeURIComponent(this.category) + '&dosage_form=' + encodeURIComponent(this.material_subtype);
    console.log('Fetching materials (by category) with URL:', url);
    
    this.service.get(url).subscribe((response: any) => {
        console.log('Materials response (by category):', response);
        if (Array.isArray(response)) {
          this.materials = response;
        } else if (response && Array.isArray(response.data)) {
          this.materials = response.data;
        } else if (response && typeof response === 'object') {
          this.materials = response.materials || response.results || response.items || [];
        } else {
          this.materials = [];
        }
        console.log('Materials after processing:', this.materials);
    }, (error) => {
        console.error('Error fetching materials:', error);
        this.materials = [];
    });
  }

  recuuringInspection = 'Not Applicable';

  selectedItem: any = {};
  selMaterialGrade: string[] = [];
 

  getDetails(indexOrCode: number | string) {
    let match: any = null;
    if (typeof indexOrCode === 'string') {
      match = (this.materials || []).find((m) => String(m?.material_code) === indexOrCode) || null;
    } else {
      const index = Number(indexOrCode) - 1;
      if (index >= 0 && this.materials && this.materials[index]) {
        match = this.materials[index];
      }
    }
    if (match) {
      this.selectedItem = match;
      this.selectedMaterialCode = String(match.material_code || '');
      if (this.material_type == 'Raw Material' || this.material_type == 'Packing Material') {
        const grade = this.selectedItem['grade'];
        this.selMaterialGrade = grade ? String(grade).split(',').map((x) => x.trim()).filter((x) => !!x) : [];
      }
    } else {
      this.selectedItem = {};
      this.selMaterialGrade = [];
    }
  }

  specification_no = '';
  version_no = '';
  specIs = 'Existing';
  loadingSpecNo = false;

  loadNextSpecificationNo(): void {
    if (this.specIs !== 'New') {
      return;
    }
    this.loadingSpecNo = true;
    this.specification_no = '';
    const specType = encodeURIComponent(this.material_type || 'Raw Material');
    this.service.get('qc/specification/raw.php?type=getNextSpecificationNo&spec_type=' + specType).subscribe({
      next: (res: any) => {
        this.loadingSpecNo = false;
        const no = res?.specification_no != null ? String(res.specification_no).trim() : '';
        this.specification_no = no && !/auto\s*generated/i.test(no) ? no : '';
        if (!this.specification_no) {
          alertify.error('Could not fetch Specification No. Try again.');
        }
      },
      error: () => {
        this.loadingSpecNo = false;
        this.specification_no = '';
        alertify.error('Could not fetch Specification No. Try again.');
      },
    });
  }

  checkSpec(){
    if(this.specIs == 'Existing'){
        this.specification_no = '';
        this.version_no = '';
    }else if(this.specIs == 'New'){
        this.revisionList = [];
        this.specification_no = '';
        this.version_no = '00';
        this.revisionList.push({"version_no":"00","change_mode":"NA","reason":"NA","effective_date":""});
        this.loadNextSpecificationNo();
    }
  }

  tests: any[] = [];
  getTestByTestType(value) {
    this.service.get('common.php?type=getTests&test_type=' + value).subscribe((response: any) => {
        this.tests = Array.isArray(response) ? response : [];
    });
  }

  specSubTests: any[] = [];
  testMasterId = '';

  getSubTest(index) {
    this.specSubTests = [];
    this.testMasterId = '';
      index = index - 1;
      if (index !== -1 && this.tests && this.tests[index]) {
        this.specSubTests = Array.isArray(this.tests[index].subtests) ? this.tests[index].subtests : [];
        this.testMasterId = this.tests[index].id;
      }
  }




  limit_type = '';
  retest = 'Not Applicable';

  tms = '';
  addMethodToDownBox(){
    this.tms = this.test_method_no;
  }


  spectTests: any[] = [];
  sampling_plan = 'Fixed';
  test_method_no = '';
  highlightTestMethod = false;
  testFor = 'Medicap';
  client_name = '';
  addTests(data) {

    if(!data.valid){
      alertify.error("All Field Required....");
      return;
    }
    let temp = data.value;

    if (temp['testFor'] === 'CLIENT') {
      const name = (this.client_name || '').trim();
      if (!name) {
        alertify.error('Client name is required');
        return;
      }
      temp['client_name'] = name;
      temp['testFor'] = 'CLIENT - ' + name;
    } else if (temp['testFor'] === 'Medicap') {
      temp['testFor'] = 'Medicap';
    }
  
    if (temp['limit_type'] == 'Range') {
      temp['limits'] = temp['lower_limit'] +  temp['unit'] + ' to ' + temp['upper_limit'] + temp['unit'];
    } else if (temp['limit_type'] == 'Not MoreThan') {
      temp['limits'] = 'NMT ' + temp['upper_limit'] + temp['unit'];
    } else if (temp['limit_type'] == 'Not LessThan') {
      temp['limits'] = 'NLT ' + temp['lower_limit'] + temp['unit'];
    } else if (temp['limit_type'] == 'Compliances') {
      temp['limits'] = temp['compliances'];
    } else if (temp['limit_type'] == 'Description') {
      temp['limits'] = temp['description'];
    }
    
    temp['test_master_id'] = this.testMasterId;
  
    this.spectTests.push(temp);
    data.reset();
    this.testFor = 'Medicap';
    this.client_name = '';

    if(this.sampling_plan=='Test Specific'){
      this.calculateSamplingQty();
    }
 
   }


  delTest(i){
    this.spectTests.splice(i,1);

    if(this.sampling_plan=='Test Specific'){
      this.calculateSamplingQty();
    }
  }



  revisionList: any[] = [];
  addRevision(data) {

    if(!data.valid){
      alertify.error("All Field Required....");
      return;
    }

    let temp = data.value;
    this.revisionList.push(temp);
    data.reset();
   }


  delRevision(i){
    this.revisionList.splice(i,1);
  }




  chemical_qty = 0;
  physical_qty = 0;
  micro_qty = 0;
  indentification_qty = 0;
  sample_qty = 0;
  control_sample = 0;
  additional_sample = 0;
  totalsample_qty = 0;

 
   calculateSamplingQty(){

      this.chemical_qty = 0;
      this.physical_qty = 0;
      this.micro_qty = 0;
      this.indentification_qty = 0;
      this.control_sample = 0;
      this.sample_qty = 0;
      this.totalsample_qty = 0;
      let otherQty = 0;
  
      this.spectTests.forEach((item) => {
        if (item.test_type === 'Chemical') {
          this.chemical_qty += Number(item.sample_qty) || 0;
        } 
        else if (item.test_type === 'Microbiology') {
          this.micro_qty += Number(item.sample_qty) || 0;
        } 
        else if (item.test_type === 'Physcial') {
          this.physical_qty += Number(item.sample_qty) || 0;
        } 
        else if (item.test_type === 'Indentification') {
          this.indentification_qty += Number(item.sample_qty) || 0;
        }else{
          otherQty += Number(item.sample_qty) || 0;
        }

      });

      this.sample_qty = Number(this.chemical_qty) + Number(this.physical_qty) + Number(this.micro_qty) + Number(this.indentification_qty) + Number(otherQty);
     
      this.control_sample = Number(this.sample_qty) * 2;
      
      this.totalsample_qty = Number(this.sample_qty) + Number(this.control_sample) + Number(this.additional_sample) ;
        
   }



  caclculateTotalQty(){
    this.totalsample_qty = 0;
    this.totalsample_qty = Number(this.sample_qty) + Number(this.control_sample) + Number(this.additional_sample) ;
  }

 

  saveSpecification(data) {

    if(!data.valid){
      alertify.error("All Field Required....");
      return;
    }

    if (this.specIs === 'New' && (!this.specification_no || /auto\s*generated/i.test(String(this.specification_no)))) {
      alertify.error('Specification No is not ready. Wait for auto number or try again.');
      this.loadNextSpecificationNo();
      return;
    }

    let temp = data.value;
    temp['specification_no'] = this.specification_no;
    temp['specIs'] = this.specIs;
    temp['chemical_qty'] = this.chemical_qty;
    temp['physical_qty'] = this.physical_qty;
    temp['micro_qty'] = this.micro_qty;
    temp['indentification_qty'] = this.indentification_qty;
    temp['sample_qty'] = this.sample_qty;
    temp['control_sample'] = this.control_sample;
    temp['additional_sample'] = this.additional_sample;
    temp['totalsample_qty'] = this.totalsample_qty;
    temp['spectTests'] = this.spectTests;
    temp['revisionList'] = this.revisionList;
    temp['dynamic_fields_json'] = this.dynamicFieldValues;
    this.service.post('qc/specification/raw.php?type=saveSpecification',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Specification saved Successfully');
          this.router.navigate(['/master/specification/raw']);
          this.spectTests = [];
          this.revisionList = [];
          data.reset();
        } else {
          alertify.error(response['status']);
        }
    });
  }



 

  isShow1 = true;
  toggleShow1(){
    this.isShow1 = !this.isShow1;
  }



}

