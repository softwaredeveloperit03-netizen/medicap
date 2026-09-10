import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})

export class NewComponent implements OnInit {
  fg_sub_materials:any = []
  stages = [];
  products: any;
  inprocess_testing = '';
  ipqc_testing = '';
  product_code = '';
  plant_type = 'Formulation';
  process_types;
  stages_list;
  iqpc_tests;
  iqpc_sub_tests = [];
  steps;
  units;

  yieldRequired = 'Yes';
  yeild_unit = '%';


  // product_name: '';
  constructor(private service: DataAccessService,private router:Router,) { }

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.getIqpcTests();
    this.getproductType();
    this.getUnits();
  }
 
  tests;
  getMicroCheck() {
    this.service.get('common.php?type=getTests&test_type=IPQC').subscribe(response => {
      this.tests = response;
    });
  } 
  getproductType() {
    this.service.get('common.php?type=getproductTypeBmr').subscribe(response => {
      this.fg_sub_materials = response;
    });
  }


  getspecSubTests;
 
  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
  getSubTests(idx) {
    this.iqpc_sub_tests = [];
    this.iqpc_sub_tests = this.iqpc_tests[idx - 1]['subtests']
  }

  getIqpcTests() {
    this.service.get('common.php?type=get_iqpc_tests').subscribe(response => {
      this.iqpc_tests = response;
    });
  }


  getProductsByType(type) {
    this.service.get('production/product.php?type=getProductsByType&product_type=' + type).subscribe(response => {
      this.products = response;
    });
  }

  getStepsAndStages(dosage_form) {
    this.service.get('production/stage.php?type=get_stages_by_process_type&dosage_form=' + dosage_form).subscribe(response => {
      this.process_types = response;
    });
  }
 
  selectedProcesstype=[];
  selected_Stage:any=[];
  getStages(index){
    this.selectedProcesstype=this.StagesData[index-1];
    this.selected_Stage=this.selectedProcesstype['stages'];
  }
  getSteps(idx) {
    this.steps = [];
    this.steps = this.selected_Stage[idx - 1]['steps']
  }
  getProductsByDosageForm(type) {
    this.service.get('production/product.php?type=getProductsByDosageForm&product_type=' + type).subscribe(response => {
      this.products = response;
      this.getStepsAndStages(type);
      this.getProcessTypes(type);
    });
  }
  StagesData;
  getProcessTypes(value){
    this.service.get('bmr/process.php?type=getProcessesmaster&dosage_form='+value).subscribe(response=>{
      this.StagesData = response;
    });
  }
  spec_nos;

  getSpec_no(value){
    this.service.get('bmr_new/product.php?type=getSpecification_no&product_no='+value).subscribe(response=>{
      this.spec_nos = response;
    });
  }
  SelectedSpecTest:any=[]
  SelectedSpecTestTests:any=[]
  getSpec_tests(index){
    this.SelectedSpecTest=this.spec_nos[index-1]
    this.SelectedSpecTestTests=this.SelectedSpecTest['spec_tests']
    
    console.log('this.SelectedSpecTest :>> ', this.SelectedSpecTest);
  }

  process_type='';
  getTestData=[];
  limit
lower_limit
upper_limit;
unit;
min;
max;
notlessthan;
notmorethan;

  getSubtest1(index) {

    this.getTestData=this.SelectedSpecTestTests[index-1];

    this.limit=this.getTestData['limit_type'];
    this.min=this.getTestData['lower_limit'];
    this.max=this.getTestData['upper_limit'];
    this.notlessthan=this.getTestData['lower_limit'];
    this.notmorethan=this.getTestData['upper_limit'];
    this.unit=this.getTestData['unit'];
  
}
 
  selectedProduct=[];
  getProductCode(index) {
    // console.log(val, this.products[val-1]);
    // this.product_name = this.products[val-1].product_name;
    this.selectedProduct = this.products[index-1];

    this.getSpec_no(this.selectedProduct['product_code']);
  }
  fg_sampling;
  addStage(val) {
    if (!val.valid) {
      alert('All Fields are required');
      return;
    }

    let temp = val.value;
    temp['yeild_unit'] = '%';
 
    this.stages.push(val.value);
    val.reset();

    this.yieldRequired = 'Yes';
 
  }

  deleteStage(val) {
    this.stages.splice(val, 1);
  }

  save(form) {    

    if (!form.valid) {
      alert('All Fields are required');
      return;
    }

    let temp = form.value;

    temp["product_type"] = temp["dosage_form"];
    temp["material_type"] = 'Raw Material';
    temp["stages_test"] =this.stages;
    
    this.service.post('production/stage.php?type=save_iqpc_stage', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/production/stages-master']);
        alert('Stages Created Successfully');
        form.reset();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  // getFGMaterials(value) {
  //   this.service.observableFGTypes.subscribe(response => {
  //     // let data =response;
  //     if (value == "") {
  //       return;
  //     }
  //     let idx = -1;
  //     for (let i = 0; i < response.length; i++) {
  //       if (response[i]['material_subtype'] == value) {
  //         idx = i;
  //       }
  //     }
  //     if (idx >= 0) {
  //       let data = response[idx]
  //       this.fg_sub_materials = data['sub_materials'];

  //     } else {
  //       this.fg_sub_materials = []
  //     }
  //   })
  // }
}
