import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  fg_sub_materials = []
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
    this.getStagesForRevision();
   }
 


  results ;
  
  getStagesForRevision() {
    this.service.get("production/stage.php?type=get_iqpc_stagesForRevision&material_type='Raw Material'").subscribe((response) => {
        this.results = response;
      });
  }

  selectedResult = [];
  isview = false;




  view(val) {
    this.selectedResult = this.results[val];
    this.stages =  this.selectedResult['stages_test'];
    this.isview = true;
     this.getProcessTypes(this.selectedResult['product_type']);
     this.getSpec_no(this.selectedResult['product_code']);
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
    this.notlessthan=this.getTestData['upper_limit'];
    this.notmorethan=this.getTestData['lower_limit'];
    this.unit=this.getTestData['unit'];
  }

 

  
 


  selectedStage ;
  changeStage(val) {
    this.selectedStage = this.stages[val];
  }



  save(form) {    
    if (!form.valid) {
      alert('All Fields are required');
      return;
    }
    let temp = form.value;
    temp["product_type"] = temp["dosage_form"];
    temp["material_type"] = 'Finish Product';
    temp["stages_test"] =this.stages;

    
    this.service.post('production/stage.php?type=revised_iqpc_stage&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/production/stages-master']);
        alert('Stages Created Successfully');
        form.reset();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }



  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe(response => {
      // let data =response;
      if (value == "") {
        return;
      }
      let idx = -1;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      if (idx >= 0) {
        let data = response[idx]
        this.fg_sub_materials = data['sub_materials'];

      } else {
        this.fg_sub_materials = []
      }
    })
  }


}
