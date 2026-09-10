import {
  Component,
  OnInit,
  AfterViewInit,
  ViewChild,
  ɵConsole,
  ElementRef,
  Directive,
  TemplateRef,
  HostListener,
} from '@angular/core';
import { Router } from '@angular/router';
import {
  ReactiveFormsModule,
  FormBuilder,
  FormGroup,
  Validators,
  FormControl,
} from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

interface specType {
  value: string;
  label: string;
}

interface matType {
  value: string;
  label: string;
}

interface Unit {
  value: string;
  label: string;
}

interface testType {
  value: string;
  label: string;
}

interface limit {
  value: string;
  label: string;
}
interface limits {
  value: string;
  label: string;
}

interface retestApp {
  value: string;
  label: string;
}
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  grades;
  materials;
  material_info;
  dosage_forms;
  control_sample = 0;
  special_grades;
  totalsample_qty = 0;
  actual_composite = 0;
  grade = '';
  material_nature = '';
  additional_sample = 0;
  totalchemical_qty = 0;
  material_type;
  sample_qty;
  tests;
  ctests;
  
uom = 'gm';
  subtests = [];
  units;
  testTyper = '';
  unit = '';
  composite = 0;
  micro_qty = 0;
  isYes = false;
  isNo = false;
  selectedProd = [];
  sample_unit = '';
  material_subtype = '';
  material_code = '';
  chemical_name = '';
  selectedqty = 0;
  selectMicroQty = 0;
  companies = [];
  clientList = [];
  subtest = '';
  revisionList = [];
  refers;
  selectedRef = [];
  release_stability = '';
  test_type = '';
  submitted: boolean = false;
  revisionDataList = [];
  specification = 'Existing';
  isdesc = false;
  selectedFile: File;
  isupload = false;
  unit1 = '';
  isNewLocation = false;
  test1 = '';
  limit = '';
  isSample = false;
  version_no = '00';
  isStorage = false;
  storage_condition = '';
  sampling_plan = 'Fixed';
  sampling_drawn = '';
  samples;
  types;
  material_types;
  sample_drawns;
  sample_withs;
  precautions;
  hazards;
  storages;
  clients;
  selectedClient = [];
  special_grade = '';
  isGrade = false;
  isTest = false;
  sub_types;
  productdata;
  productCode;
  categories = [];
  totalmicro_qty = 0;
  total_physical_qty = 0;
  has_nature_of_material: 0;
  indentification_qty = 0;
  //control_reserve_criteria = 2;
  sample_applicable = 'Applicable';
  control_sample_criteria = 2;
  specification_type: any;
  control_sample_type: any;
  rowMaterialForm: FormGroup;
  versionControlForm: FormGroup;
  samplingForm: FormGroup;
  SpecificationForm: FormGroup;
  molecularForm: FormGroup;
  AddSpecificationTest: FormGroup;
  ClientTest: FormGroup;
  revisionHistory: FormGroup;

  public specList: specType[] = [
    { value: '', label: '' },
    { value: 'New', label: 'New' },
    { value: 'Existing', label: 'Existing' },
  ];

  public matTypeList: matType[] = [
    { value: '', label: '' },
    { value: 'Raw Material', label: 'Raw Material' },
    { value: 'Packing Material', label: 'Packing Material' },
    { value: 'Semi Finished Goods', label: 'Semi Finished Goods' },
    { value: 'Finish Product', label: 'Finish Product' },
  ];

                        

  public Units: Unit[] = [
    { value: '', label: '' },
    { value: 'mg', label: 'mg' },
    { value: 'gm', label: 'gm' },
    { value: 'nm', label: 'nm' },
    { value: 'Nos', label: 'Nos' },
    { value: 'Kg', label: 'Kg' },
  ];

  public testType: testType[] = [
     { value: '', label: '' },
    { value: 'Chemical', label: 'Chemical' },
    { value: 'Microbiology', label: 'Microbiology' },
    { value: 'Physcial', label: 'Physical Observation' },
    { value: 'Packing', label: 'Packing' },
    { value: 'Water', label: 'Water' },
    { value: 'Indentification', label: 'Indentification' },
    { value: 'LOD', label: 'LOD (Loss on Drying)' },
  ];

  public limits: limit[] = [
    { value: 'Description', label: 'Description' },
    { value: 'Range', label: 'Range' },
    { value: 'Not LessThan', label: 'Not LessThan' },
    { value: 'Not MoreThan', label: 'Not MoreThan' },
    { value: 'Compliances', label: 'Compliances' },
  ];

  public retestApp: retestApp[] = [
    { value: 'Applicable', label: 'Applicable' },
    { value: 'Not Applicable', label: 'Not Applicable' },
  ];
  grade_type: any;
  materialForm: any;
  subtestsClient: any;
  SelectedSubtest: any;
  ClientSelectedSubtest: any;
  gradeProd: any;
  product_code = '';
  total_Chem_Sampling_qty = 0;
  total_Micro_Sampling_qty = 0;
  // total_Sampling_qty: number;
  total_Sampling_qty;
  fixed_control_sample_criteria;
  software_type: any;
  department = '';
  type = '';
  plant_type: any;
  plant_id: any;
  emp_id: string;
  constructor(
    private service: DataAccessService,
    private router: Router,
    public formBuilder: FormBuilder
  ) {
    this.department = localStorage.getItem('department');
    this.type = localStorage.getItem('type');
    this.software_type = this.service.getPlantConfigFields('software_type');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    if (this.software_type == null) {
      this.service
        .getData(
          'https://gmpsoftwareindia.com/admin/api/clients/client_data_without_token.php?type=get_client_data_by_id&id=' +
            localStorage.getItem('plant_id')
        )
        .subscribe((response) => {
          localStorage.setItem('client_info', JSON.stringify(response));
          this.software_type =
            this.service.getPlantConfigFields('software_type');
        });
    }
  }

  ngOnInit() {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = localStorage.getItem('plant_id');
    console.log(this.plant_type);
    // console.log(this.service.getPlantConfigFields)
    this.getGrades();
    // this.getMaterialType();
    this.getUnits();
    this.getStorage();
    this.getSample();
    this.getSampleDrawn();
    this.getSampleWithD();
    this.getHazards();
    this.getClients();
    this.getSpecialGrdae();

    this.rowMaterialForm = this.formBuilder.group({
      rm_spec: ['', [Validators.required]],
      rm_matType: ['Raw Material', [Validators.required]],
      rm_matSubType: ['', [Validators.required]],
      rm_product_name: ['', [Validators.required]],
      rm_specType: ['', [Validators.required]],
      rm_matName: ['', [Validators.required]],
      rm_matCode: [''],
      rm_prodCode: [''],
      review_date_select: [''],
      review_date: [''],
      supersede_ver_no: [''],
      supersede_no: [''],
      version_no: [''],
      spec_no: [''],
    });

    this.molecularForm = this.formBuilder.group({
      molecular_formula: [''],
      molecular_weight: [''],
      structural_formula: [''],
      retest_peroid: [''],
      special_grade: [''],
      iupac_name: [''],
      therapetic_category: [''],
      storage_condition: [''],
      cas_name: [''],
    });
    // this.samplingForm=new FormGroup({
    //   total_Micro_Sampling_qty:new FormControl(0),
    //   total_Chem_Sampling_qty:new FormControl(0)
    // })
    this.samplingForm = this.formBuilder.group({
      totalchemical_qty: [0],
      total_physical_qty: ['0'],
      totalmicro_qty: ['0'],
      indentification_qty: ['0'],
      control_sample_type: ['0'],
      control_sample_criteria: [''],
      control_sample: [0],
      additional_sample: [0],
      unit: [''],
      totalsample_qty: [0, [Validators.required]],
      sampling_type: [''],
      Sampling_qty: [''],
      total_Chem_Sampling_qty: [0],
      total_Micro_Sampling_qty: [0],
      total_Sampling_qty: [0],
      fixed_control_sample_criteria: [0],
    });

    this.AddSpecificationTest = this.formBuilder.group({
      test_type: ['', [Validators.required]],
      selectedTest: [''],
      selectedSubTest: [''],
      grade: [''],
      ref: [''],
      reference: [''],
      limit: ['', [Validators.required]],
      retest_applicable: [''],
      sto: [''],
      req_sample_qty: [''],
      stp_no: [''],
      stability_indicating: [''],
      shelf_life_limit: [''],
      Shelf_descr:[''],
      Shelf_compCriteria:[''],
      Shelf_lower_limit:[''],
    
      Shelf_upper_limit:[''],

      Shelf_unit:[''],
      bulk_release: [''],
      sample_qty: [''],
      descr: [''],
      unit: [''],
      compCriteria: [''],
      upper_limit: ['', [Validators.required]],
      lower_limit: ['', [Validators.required]],
      outside_testing: ['', [Validators.required]],
      subtests: [''],
    });

    this.ClientTest = this.formBuilder.group({
      client_code: ['', [Validators.required]],
      test_type: ['', [Validators.required]],
      selectedTest: [''],
      selectedSubTest: [''],
      grade: [''],
      ref: [''],
      reference: [''],
      unit: [''],
      retest_applicable: [''],
      sto: [''],
      stability_indicating: [''],
      sample_qty: [''],
      descr: [''],
      limit: ['', [Validators.required]],
      upper_limit: [''],
      lower_limit: [''],
      outside_testing: [''],
      subtests: [''],
      reference_type: ['', [Validators.required]],
    });

    this.revisionHistory = this.formBuilder.group({
      spec_no: ['', [Validators.required]],
      ver_no: ['', [Validators.required]],
      change_mode: ['', [Validators.required]],
      change_reason: ['', [Validators.required]],
      effective_date: ['', [Validators.required]],
      training: ['', [Validators.required]],
    });

    this.AddSpecificationTest.get('limit')?.valueChanges.subscribe((obj) => {
      console.log(obj);
      if (obj == 'Range') {
        this.AddSpecificationTest.get('unit')?.setValidators([
          Validators.required,
        ]);
      } else {
        this.AddSpecificationTest.get('unit')?.setValidators(null);
      }
    });

    this.AddSpecificationTest.get('lower_limit')?.valueChanges.subscribe(
      (obj) => {
        console.log(obj);
        if (obj != '') {
          this.AddSpecificationTest.get('unit')?.setValidators([
            Validators.max(obj),
          ]);
        }
      }
    );
    // this.get_rights()
  }
  rights;
  righ;
  // get_rights() {
  //   this.service.get('hr/employee.php?type=getrights&module_name=Specifications&form_type=user&form_name=New Raw Material Specification&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
  //     this.rights = response;
  //     this.righ=this.rights[0].user_access
  //     console.log(this.righ)
  //   });
  // }




  calc_total_sample_qtyFiexed() {
   
 
    this.totalsample_qty = Number(this.additional_sample) + Number(this.totalchemical_qty) + Number(this.control_sample) ;
     this.samplingForm.patchValue({
      totalsample_qty: this.totalsample_qty
    }); 
  }




  getClients() {
    this.service.get('common.php?type=getClients').subscribe((response) => {
      this.clients = response;
    });
  }
  getClientName(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedClient = this.clients[index];
    }
  }

  checkSpec(event) {
    console.log(event);
    console.log(this.rowMaterialForm.value.rm_product_name);

    if (this.rowMaterialForm.value.rm_matType != '') {
      alertify.confirm(
        'Date lost',
        'Are you sure want to change specfication , your form data will reset ?',
        () => {
          this.revisionList = [];
          this.clientList = [];
          console.log(this.clientList);
          this.companies = [];
        },
        () => {
          if (event == 'New')
            this.rowMaterialForm.get('rm_spec').setValue('Existing');
          else this.rowMaterialForm.get('rm_spec').setValue('New');
        }
      );
    }

    if (this.rowMaterialForm.value.rm_spec == 'New') {
      let date = this.formatDate(new Date());
      // alert("new");
      this.rowMaterialForm.get('version_no').setValue(0);
      this.rowMaterialForm.get('supersede_no').setValue('NA');
      this.rowMaterialForm.get('supersede_no').setValue('NA');
      this.rowMaterialForm.get('spec_no').setValue(1);
      this.rowMaterialForm.get('supersede_ver_no').setValue('NA');
      this.revisionList[0] = {
        spec_no: 'AUTO GENERATE',
        change_mode: 'NA',
        change_reason: 'NA',
        effective_date: date,
      };
    }
  
  }
  getMaterialType(type) {
    this.types = [];
    this.material_types = [];
    this.service
      .get('master/materialtype.php?type=getSubMaterials&material_type=' + type)
      .subscribe((response) => {
        this.types = response['material_types'];
      });
    this.service.observableMaterialTypes.subscribe((response) => {
      this.material_types = response;
    });
  }

  getMaterialsBySubType() {

    let material_type = this.rowMaterialForm.value.rm_matType;
    let material_subtype = this.rowMaterialForm.value.rm_matSubType.material_subtype;

    this.service.get('common.php?type=getMaterialsByType&material_type=' + material_type +
      '&material_subtype=' + material_subtype ).subscribe((response) => {
        this.materials = response;
        console.log(this.materials);
    });
    
  }

 
  getMaterials(value) {
    this.service
      .get(
        'common.php?type=getMaterialsByType&material_subtype=' +
          this.material_subtype +
          '&material_type=' +
          this.material_type
      )
      .subscribe((response) => {
        this.materials = response;
        console.log(this.materials);
      });
 
  }
 

  getSubMaterials(type) {
     this.material_type = type;
      this.sub_types = [];
      this.service.get('master/materialtype.php?type=getSubMaterials&material_type=' + type).subscribe((response) => {
          this.sub_types = response;
          this.control_sample_type = this.sub_types[0].control_sample_type;
          console.log(this.control_sample_type);
      });

   }

   samplingUnit = '';
   chemical_qty = '';
  selMaterialGrade:any = [];
 
  getDetails(index) {
    this.selMaterialGrade = [];
    index = index - 1;
    if (index !== -1) {
      this.selectedProd = this.materials[index];
      this.rowMaterialForm.get('rm_matCode').setValue(this.materials[index].material_code);

      this.totalchemical_qty = this.selectedProd['sampleForTesting'] || 0;
      this.totalsample_qty = Number(this.control_sample) + Number(this.additional_sample) + Number(this.selectedProd['sampleForTesting']);
      this.samplingUnit = this.selectedProd['samplingUnit'] || 0;
  
    }
  }

    getGrades(){
    this.grades =[];
    this.service.get('master/product.php?type=getGrades').subscribe(response => {
      this.selMaterialGrade = response;

      this.service.observableGrade.subscribe(response => {
        this.selMaterialGrade = response;
      });
    
    })
  }

  getUnits() {
    this.service.observableUnit.subscribe((response) => {
      this.units = response;
    });
  }

  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }

  getRef(index) {
    index = index - 1;
    this.selectedRef = this.refers[index];
  }

  getMicroCheck(value) {
    this.service
      .get('common.php?type=getTests&test_type=' + value)
      .subscribe((response) => {
        this.tests = response;
        this.AddSpecificationTest?.get('selectedTest')?.setValue(this.tests[0]);
      });
  }

  getMicroCheckClient(value) {
    this.service
      .get('common.php?type=getTests&test_type=' + value)
      .subscribe((response) => {
        this.ctests = response;
       });
  }
  test_method_no = '';

  specSubTests;
  test_master_id = '';
  getSubtest1(index) {
    this.specSubTests =[];
    this.specSubTests = this.tests[index - 1].subtests;
    this.test_master_id = this.tests[index - 1].id;
  }

  getSubtestClient(index) {
    this.subtestsClient =[];
      this.subtestsClient = this.ctests[index - 1].subtests;
      this.test_master_id = this.ctests[index - 1].id;
   }

 

  addTests(data) {
    let temp = this.AddSpecificationTest.value;
    console.log(temp);
    if (temp['limit'] == 'Range') {
      if (temp['unit'] != null) {
        temp['limits'] =
          temp['lower_limit'] +
          temp['unit'] +
          ' to ' +
          temp['upper_limit'] +
          temp['unit'];
      } else {
        temp['limits'] = temp['lower_limit'] + ' to ' + temp['upper_limit'];
      }
    } else if (temp['limit'] == 'Not MoreThan') {
      temp['limits'] = 'NMT ' + temp['lower_limit'] + temp['unit'];
    } else if (temp['limit'] == 'Not LessThan') {
      temp['limits'] = 'NLT ' + temp['upper_limit'] + temp['unit'];
    } else if (temp['limit'] == 'Compliances') {
      temp['limits'] = temp['compCriteria'];
      console.log(this.compCriteria + " -----" + temp['limits']);
    } else if (temp['limit'] == 'Description') {
      temp['limits'] = temp['descr'];
    }
     if (temp['shelf_life_limit'] == 'Same As Release') {
    temp['Shelf_descr']=temp['descr']
    temp['Shelf_compCriteria']=temp['compCriteria']
    temp['Shelf_lower_limit']=temp['lower_limit']
    temp['Shelf_lower_limit']=temp['lower_limit']
    temp['Shelf_upper_limit']=temp['upper_limit']
    temp['Shelf_upper_limit']=temp['upper_limit']
    temp['Shelf_unit']=temp['unit']
    }
    temp['test_master_id'] = this.test_master_id;
    temp['test_method_no'] = this.test_method_no;
     temp['test'] = temp['selectedTest'].test;
    temp['grade'] = this.AddSpecificationTest.value.grade;
    console.log(this.AddSpecificationTest.value);

    if (this.AddSpecificationTest.value.retest_applicable == '') {
      temp['retest'] = this.AddSpecificationTest.value.retest_applicable;
    }
    this.companies.push(temp);
    console.log("hii");
    console.log(this.companies);
    console.log("hii");


    if(this.sampling_plan=='Test Specific'){
      this.calc_total_sample_qty();
    }
 
    this.AddSpecificationTest.reset();
  }

 
  compCriteria = '';

  addSample(){

    this.totalsample_qty = +this.additional_sample + +this.control_sample;
  }










  calc_total_sample_qty() {
    // Initialize the total quantities
    let totalChemicalQty = 0;
    let totalPhysicalQty = 0;
    let totalMicroQty = 0;
    let totalIdentificationQty = 0;
  
    // Reset the form controls
    this.samplingForm.patchValue({
      control_sample: 0,
      totalsample_qty: 0,
      totalchemical_qty: 0,
      total_physical_qty: 0,
      totalmicro_qty: 0,
      indentification_qty: 0
    });
  
    // Sum the quantities based on the test_type
    this.companies.forEach((item) => {
      if (item.test_type === 'Chemical') {
        totalChemicalQty += Number(item.sample_qty);
      } else if (item.test_type === 'Physcial') {
        totalPhysicalQty += Number(item.sample_qty);
      } else if (item.test_type === 'Microbiology') {
        totalMicroQty += Number(item.sample_qty);
      } else if (item.test_type === 'Indentification') {
        totalIdentificationQty += Number(item.sample_qty);
      }
    });
  
    // Set the total quantities in the form controls
    this.samplingForm.patchValue({
      totalchemical_qty: totalChemicalQty,
      total_physical_qty: totalPhysicalQty,
      totalmicro_qty: totalMicroQty,
      indentification_qty: totalIdentificationQty
    });
  
    // Calculate the total sample quantity
    this.totalsample_qty =
      totalChemicalQty +
      totalPhysicalQty +
      totalMicroQty +
      totalIdentificationQty +
      Number(this.samplingForm.value.additional_sample);
  
    // Calculate the control sample
    this.control_sample = this.totalsample_qty * 2; // or * 3, depending on your requirements
  
    // Set the calculated values in the form controls
    this.samplingForm.patchValue({
      control_sample: this.control_sample,
      totalsample_qty: this.totalsample_qty
    });
  
    console.log('Total Chemical:', totalChemicalQty);
    console.log('Total Physical:', totalPhysicalQty);
    console.log('Total Microbiology:', totalMicroQty);
    console.log('Total Identification:', totalIdentificationQty);
    console.log('Total Sample Qty:', this.totalsample_qty);
    console.log('Control Sample:', this.control_sample);
  }
  
 
  addClient(data) {
    console.log(data);

    let temp = this.ClientTest.value;

    temp['limit'] == temp['limit'];
    if (temp['limit'] == 'Range') {
      temp['limits'] =
        temp['upper_limit'] +
        temp['unit'] +
        ' to ' +
        temp['lower_limit'] +
        temp['unit'];
    } else if (temp['limit'] == 'LessThan') {
      temp['limits'] = 'NMT ' + temp['lessthan'] + temp['unit'];
    } else if (temp['limit'] == 'MoreThan') {
      temp['limits'] = 'NLT ' + temp['morethan'] + temp['unit'];
    } else if (temp['limit'] == 'Compliances') {
      temp['limits'] = 'complies';
    } else if (temp['limit'] == 'Description') {
      temp['limits'] = temp['limit'];
    } else if (temp['limit'] == 'Not LessThan') {
      temp['limits'] = temp['upper_limit'] + temp['unit'];
    } else if (temp['limit'] == 'Not MoreThan') {
      temp['limits'] = temp['lower_limit'] + temp['unit'];
    }
 
    temp['reference_type'] = temp['reference_type'];
    temp['subtest'] = temp['subtests'];
    temp['client_code'] = temp['client_code'];
    temp['test'] = temp['selectedTest'].test;
    temp['TrdNm'] = this.ClientTest.value.client_code.TrdNm;
    temp['test_type'] = temp['test_type'];
    temp['test_master_id'] = this.test_master_id;
     

    let totalchemical_qty = this.samplingForm.get('totalchemical_qty')?.value;
    let totalmicro_qty = this.samplingForm.get('totalmicro_qty')?.value;
    let total_physical_qty = this.samplingForm.get('total_physical_qty')?.value;
    let indentification_qty = this.samplingForm.get(
      'indentification_qty'
    )?.value;

    console.log('this.totalchemical_qty' + totalchemical_qty);

    if (temp['test_type'] == 'Chemical') {
      this.samplingForm
        .get('totalchemical_qty')
        .setValue(Number(temp['sample_qty']) + Number(totalchemical_qty));
    } else if (temp['test_type'] == 'Microbiology') {
      this.samplingForm
        .get('totalmicro_qty')
        .setValue(Number(temp['sample_qty']) + Number(totalmicro_qty));
    } else if (temp['test_type'] == 'Physcial') {
      this.samplingForm
        .get('total_physical_qty')
        .setValue(Number(temp['sample_qty']) + Number(total_physical_qty));
    } else {
      this.samplingForm
        .get('indentification_qty')
        .setValue(Number(temp['sample_qty']) + Number(indentification_qty));
    }

    this.samplingForm
      .get('totalsample_qty')
      .setValue(
        this.samplingForm.get('totalchemical_qty')?.value * 1 +
          this.samplingForm.get('totalmicro_qty')?.value * 1
      );
    this.control_sample = this.totalsample_qty * 1 * 3;
    this.clientList.push(temp);
    console.log(this.clientList);
    console.log(this.clientList[0].selectedSubTest.subtest);
    this.ClientSelectedSubtest = this.clientList[0].selectedSubTest.subtest;
    this.ClientTest.reset();
    this.test_master_id = '';
 
      console.log(this.clientList);
      console.log("clientList");
  }
  clientCalc() {
    for (let i = 0; i < this.companies.length; i++) {
      if (this.companies[i]['test_type'] == 'Chemical') {
        this.samplingForm
          .get('totalchemical_qty')
          .setValue(
            Number(this.companies[i]['sample_qty']) +
              Number(this.totalchemical_qty)
          );
      } else if (this.companies[i]['test_type'] == 'Microbiology') {
        this.samplingForm
          .get('totalmicro_qty')
          .setValue(
            Number(this.companies[i]['sample_qty']) +
              Number(this.totalmicro_qty)
          );
      } else if (this.companies[i]['test_type'] == 'Physcial') {
        this.samplingForm
          .get('total_physical_qty')
          .setValue(
            Number(this.companies[i]['sample_qty']) +
              Number(this.total_physical_qty)
          );
      } else {
        this.samplingForm
          .get('indentification_qty')
          .setValue(
            Number(this.companies[i]['sample_qty']) +
              Number(this.indentification_qty)
          );
      }
    }
  }
  samplecalc() {}
  addRevision(data) {
    
    let temp = this.revisionHistory.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    // this.revisionHistory.controls.resetForm();
    this.revisionHistory.reset();
    const element1 = document.getElementById('spec_no') as HTMLElement;
    element1.focus();
  }

 

  deleteProduct(index) {
    const item = this.companies[index];
    this.companies.splice(index, 1);
    this.remove_total_sample_qty(item);
  }
  
  remove_total_sample_qty(item) {
    // Subtract the sample quantities based on the test type
    if (item.test_type == 'Chemical') {
      let chem_qty: number = this.samplingForm.value.totalchemical_qty;
      this.samplingForm
        .get('totalchemical_qty')
        .setValue(Number(chem_qty) - Number(item.sample_qty));
  
    } else if (item.test_type == 'Physcial') {
      let physical_qty: number = this.samplingForm.value.total_physical_qty;
      this.samplingForm
        .get('total_physical_qty')
        .setValue(Number(physical_qty) - Number(item.sample_qty));
  
    } else if (item.test_type == 'Microbiology') {
      let micro_qty: number = this.samplingForm.value.totalmicro_qty;
      this.samplingForm
        .get('totalmicro_qty')
        .setValue(Number(micro_qty) - Number(item.sample_qty));
  
    } else if (item.test_type == 'Indentification') {
      let indentification_qty: number =
        this.samplingForm.value.indentification_qty;
      this.samplingForm
        .get('indentification_qty')
        .setValue(Number(indentification_qty) - Number(item.sample_qty));
    }
  
    // Recalculate total sample quantities
    this.totalsample_qty =
      Number(this.samplingForm.value.totalchemical_qty) +
      Number(this.samplingForm.value.total_physical_qty) +
      Number(this.samplingForm.value.totalmicro_qty) +
      Number(this.samplingForm.value.indentification_qty) +
      Number(this.samplingForm.value.additional_sample);
    this.control_sample = Number(this.totalsample_qty * 2); //* 3;
  
    // Update the form fields with the new totals
    this.samplingForm.get('control_sample').setValue(this.control_sample);
    this.samplingForm.get('totalsample_qty').setValue(this.totalsample_qty);
  }
  

  deleteClient(index) {
    if (this.clientList[index].test_type == 'Microbiology') {
      this.micro_qty =
        this.micro_qty - parseFloat(this.clientList[index].sample_qty);
      this.clientList.splice(index, 1);
    } else if (this.clientList[index].test_type == 'Chemical') {
      this.composite =
        this.composite - parseFloat(this.clientList[index].sample_qty);
      this.clientList.splice(index, 1);
    }
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  formatDate(date) {
    var d = new Date(date),
      month = '' + (d.getMonth() + 1),
      day = '' + d.getDate(),
      year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  }

  addMaterial(data) {
    // alert('saveData');
    console.log(data);
    let temp = {};
    console.log(temp);
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    console.log(JSON.stringify(temp));
    // const uploadData = new FormData();
    // for (let key in temp) {
    //   let value = temp[key];
    //   uploadData.append(key, value);
    // }
    // if (this.selectedFile !== undefined) {
    //   uploadData.append('structural_formula', this.selectedFile, this.selectedFile.name);
    // }
    // uploadData.append('specification', this.specification);
    // uploadData.append('material_code', this.selectedProd['material_code']);
    // uploadData.append('material_type', this.material_subtype);
    // uploadData.append('tests', JSON.stringify(this.companies));
    // uploadData.append('clientlist', JSON.stringify(this.clientList));
    // uploadData.append('revisionHistory', JSON.stringify(this.revisionList));

    temp['material_code'] = this.rowMaterialForm.value;
    this.material_type = this.rowMaterialForm.value;
    //  this.product_code = this.productCode[0].grade;
    temp['molecularForm'] = this.molecularForm.value;
    temp['samplingForm'] = this.samplingForm.value;
    temp['companies'] = this.companies;
    temp['clientlist'] = this.clientList;
    temp['revisionHistory'] = this.revisionList;
    console.log(temp);
    this.service
      .post(
        'qc/specification/raw.php?type=saveSpecification',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Data saved Successfully');
          this.router.navigate(['/master/specification/raw']);
        } else {
          alertify.error(response['status']);
        }
      });
  }

  onFilechange(event) {
    if (event.target.files.length > 0) {
      this.selectedFile = event.target.files[0];
      this.isupload = true;
    } else {
      this.isupload = false;
    }
  }

  Calculation() {
    this.samplingForm.get('totalchemical_qty').setValue(0);
    this.samplingForm.get('total_physical_qty').setValue(0);
    this.samplingForm.get('totalmicro_qty').setValue(0);
    this.samplingForm.get('indentification_qty').setValue(0);
    this.companies.forEach((item) => {
      if (item.test_type == 'Chemical') {
        let chem_qty: number = this.samplingForm.value.totalchemical_qty;
        this.samplingForm
          .get('totalchemical_qty')
          .setValue(Number(chem_qty) + Number(item.sample_qty));
      } else if (item.test_type == 'Physcial') {
        let physical_qty: number = this.samplingForm.value.total_physical_qty;
        this.samplingForm
          .get('total_physical_qty')
          .setValue(Number(physical_qty) + Number(item.sample_qty));
      } else if (item.test_type == 'Microbiology') {
        let micro_qty: number = this.samplingForm.value.totalmicro_qty;
        this.samplingForm
          .get('totalmicro_qty')
          .setValue(Number(micro_qty) + Number(item.sample_qty));
      } else if (item.test_type == 'Indentification') {
        let indentification_qty: number =
          this.samplingForm.value.indentification_qty;
        this.samplingForm
          .get('indentification_qty')
          .setValue(Number(indentification_qty) + Number(item.sample_qty));
      }
    });
    this.totalsample_qty =
      Number(this.samplingForm.value.totalchemical_qty) +
      Number(this.samplingForm.value.total_physical_qty) +
      Number(this.samplingForm.value.totalmicro_qty) +
      Number(this.samplingForm.value.indentification_qty) +
      Number(this.samplingForm.value.additional_sample);
    if (
      this.samplingForm.value.control_sample_criteria ==
      'Two Times (of single anal'
    ) {
      let ControlSample = 2 * Number(this.totalsample_qty);
      this.samplingForm.get('control_sample').setValue(ControlSample);
    }
    if (
      this.samplingForm.value.control_sample_criteria ==
      'Three Times (of single anal'
    ) {
      let ControlSample = 3 * Number(this.totalsample_qty);
      this.samplingForm.get('control_sample').setValue(ControlSample);
    }
  }

  newUnit(value) {
    if (value == 'ADD NEW') {
      this.unit = this.unit1;
      this.isNewLocation = true;
    } else {
      this.isNewLocation = false;
    }
  }
  // newTest(value){
  //   if(value == 'ADD NEW'){
  //     this.test = this.test1;
  //     this.isTest = true;
  //   }else{
  //     this.isTest = false;
  //   }
  // }

  saveUnit(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.unit = this.unit1;
    this.service
      .post('qa/unit.php?type=saveUnit', JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.unit1 = '';
          this.isNewLocation = false;
          this.getUnits();
          alertify.success('Data Save Successfully');
        } else {
          alertify.error('Failed: An error occured, Please try again!');
        }
      });
  }

  getStorage() {
    this.service
      .get('qa/master.php?type=getStorageConditions')
      .subscribe((response) => {
        this.storages = response;
      });
  }

  newStorage(value) {
    if (value == 'ADD NEW') {
      this.storage_condition = '';
      this.isStorage = true;
    }
  }

  saveStorage() {
    if (this.storage_condition.length !== 0) {
      this.service
        .get(
          'qa/master.php?type=saveStorageCondition&storage_condition=' +
            this.storage_condition
        )
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getStorage();
            this.isStorage = false;
            this.storage_condition = '';
            alertify.success('Storage life saved successfully');
          } else {
            alertify.error('Failed: An error occured');
          }
        });
    }
  }

  saveSample() {
    if (this.sampling_plan.length !== 0) {
      this.service
        .get(
          'qa/master.php?type=saveStorageCondition&storage_condition=' +
            this.storage_condition
        )
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getSample();
            this.isSample = false;
            this.sampling_plan = '';
            alertify.success('Sampling Plan saved successfully');
          } else {
            alertify.error('Failed: An error occured');
          }
        });
    }
  }
  getSample() {
    this.service
      .get('master/master.php?type=getSamplingPlans')
      .subscribe((response) => {
        this.samples = response;
      });
  }

  selectedSamplingPlan: any[];
  filteredSamplingPlan: any[];
  filterSampling(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered = [];
    let query = event.query;
    for (let i = 0; i < this.samples.length; i++) {
      let country = this.samples[i];
      if (
        country.sampling_plan.toLowerCase().indexOf(query.toLowerCase()) == 0
      ) {
        filtered[filtered.length] = country;
      }
    }

    this.filteredSamplingPlan = filtered;
  }

  getSampleDrawn() {
    this.service
      .get('master/master.php?type=getSampleDrawnFroms')
      .subscribe((response) => {
        this.sample_drawns = response;
      });
  }

  selectedSamplingDrawn: any[];
  filteredSamplingDrawn: any[];
  filteredSamplingD(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered = [];
    let query = event.query;
    for (let i = 0; i < this.sample_drawns.length; i++) {
      let country = this.sample_drawns[i];
      if (
        country.sample_drawn_from.toLowerCase().indexOf(query.toLowerCase()) ==
        0
      ) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredSamplingDrawn = filtered;
  }

  getSampleWithD() {
    this.service
      .get('master/master.php?type=getSampleDrawnWiths')
      .subscribe((response) => {
        this.sample_withs = response;
      });
  }

  selectedSamplingWith: any[];
  filteredSamplingWith: any[];
  filteredSamplingW(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered = [];
    let query = event.query;
    for (let i = 0; i < this.sample_drawns.length; i++) {
      let country = this.sample_withs[i];
      if (
        country.sample_drawn_with.toLowerCase().indexOf(query.toLowerCase()) ==
        0
      ) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredSamplingWith = filtered;
  }

  getHazards() {
    this.service
      .get('master/master.php?type=getPrecautions')
      .subscribe((response) => {
        this.hazards = response;
      });
  }

  selectedHazards: any[];
  filteredHazards: any[];
  filteredH(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered = [];
    let query = event.query;
    for (let i = 0; i < this.hazards.length; i++) {
      let country = this.hazards[i];
      if (country.precautions.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredHazards = filtered;
  }

  addSpecialGrade() {
    if (this.special_grade == 'Add New') {
      this.isGrade = true;
    }
  }

  saveGrade(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'master/specialgrade.php?type=saveSpecialGrade',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isGrade = false;
          this.getSpecialGrdae();
          alertify.success('Data saved Successfully');
        } else {
          alertify.error('Failed, An error occured, please try again!');
        }
      });
  }

  getSpecialGrdae() {
    this.service
      .get('master/specialgrade.php?type=getSpecialGrade')
      .subscribe((response) => {
        this.special_grades = response;
      });
  }

  getMaterialGrdae() {
    this.service
      .get('master/specialgrade.php?type=getSpecialGrade')
      .subscribe((response) => {
        // this.special_grades = response;
      });
  }

  toggleShow() {}

  saveSampling() {}

  validateLimitorNot() {
    /*  { value: 'Description', label: 'Description' },
  { value: 'Range', label: 'Range' },
  { value: 'Not LessThan', label: 'Not LessThan' },
  { value: 'Not MoreThan', label: 'Not MoreThan' },
  { value: 'Compliances', label: 'Compliances' }
  */

    let limitType = this.AddSpecificationTest.value.limit;

    if (limitType == 'Description') {
      this.AddSpecificationTest.get('upper_limit')?.setValidators(null);
      this.AddSpecificationTest.get('upper_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('lower_limit')?.setValidators(null);
      this.AddSpecificationTest.get('lower_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('unit')?.setValidators(null);
      this.AddSpecificationTest.get('unit')?.updateValueAndValidity();
    } else if (limitType == 'Range') {
      this.AddSpecificationTest.get('upper_limit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('upper_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('lower_limit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('lower_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('unit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('unit')?.updateValueAndValidity();
    }
    // required error//null value pass
    else if (limitType == 'Not LessThan') {
      this.AddSpecificationTest.get('lower_limit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('lower_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('upper_limit')?.setValidators(null);
      this.AddSpecificationTest.get('upper_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('unit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('unit')?.updateValueAndValidity();
    } else if (limitType == 'Not MoreThan') {
      this.AddSpecificationTest.get('upper_limit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('upper_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('lower_limit')?.setValidators(null);
      this.AddSpecificationTest.get('lower_limit')?.updateValueAndValidity();

      this.AddSpecificationTest.get('unit')?.setValidators([
        Validators.required,
      ]);
      this.AddSpecificationTest.get('unit')?.updateValueAndValidity();
    }
  }

  cacluation(event) {
    this.total_Chem_Sampling_qty = event.target.value;
    this.total_Sampling_qty =
      this.total_Chem_Sampling_qty * 1 + this.total_Micro_Sampling_qty * 1;
    this.fixed_control_sample_criteria = this.total_Sampling_qty * 2;
    console.log('hi1');
    console.log(this.total_Chem_Sampling_qty);
    console.log('hi2');
    console.log(this.total_Sampling_qty);
  }
  cacluation1(event) {
    this.total_Micro_Sampling_qty = event.target.value;

    this.total_Sampling_qty =
      this.total_Chem_Sampling_qty * 1 + this.total_Micro_Sampling_qty * 1;
    this.fixed_control_sample_criteria = this.total_Sampling_qty * 2;
    console.log('hi3');
    console.log(this.total_Micro_Sampling_qty);
    console.log('hi4');
    console.log(this.total_Sampling_qty);
  }
}
