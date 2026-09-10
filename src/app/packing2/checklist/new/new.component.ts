import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  fg_sub_materials;
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
  constructor(private service: DataAccessService, private router: Router,) { }

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.getIqpcTests();
    this.getDosageTypes();
  }
  getSubTests(idx) {
    this.iqpc_sub_tests = [];
    this.iqpc_sub_tests = this.iqpc_tests[idx - 1]['subtests']
  }

  getIqpcTests() {
    this.service.get('common.php?type=get_packing_tests').subscribe(response => {
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
  getStages(idx) {
    this.stages_list = [];
    this.stages_list = this.process_types[idx - 1]['stages']
  }
  getSteps(idx) {
    this.steps = [];
    this.steps = this.stages_list[idx - 1]['steps']
  }
  getProductsByDosageForm(type) {
    this.service.get('production/product.php?type=getProductsByDosageForm&product_type=' + type).subscribe(response => {
      this.products = response;
      this.getStepsAndStages(type);
    });
  }

  getProductCode(val) {
    console.log(val, this.products[val - 1]);
    this.product_code = this.products[val - 1].product_code;
  }

  addStage(val) {
    if (!val.valid) {
      alert('All Fields are required');
      return;
    }
    this.stages.push(val.value);

  }

  deleteStage(val) {
    this.stages.splice(val, 1);
  }

  save(form) {
    form.value["product_type"] = form.value["dosage_form"];
    form.value["material_type"] = 'Packing Material';
    form.value.stages_test = this.stages;
    console.log(form.value);
    this.service.post('production/stage.php?type=save_iqpc_stage', JSON.stringify(form.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/packing/checklist']);
        alert('Stages Created Successfully');
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  getDosageTypes() {
    this.fg_sub_materials = []
    this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
      this.fg_sub_materials = response;
    });
    // this.service.observableFGTypes.subscribe(response => {
    //   // let data =response;
    //   if (value == "") {
    //     return;
    //   }
    //   let idx = -1;
    //   for (let i = 0; i < response.length; i++) {
    //     if (response[i]['material_subtype'] == value) {
    //       idx = i;
    //     }
    //   }
    //   if (idx >= 0) {
    //     let data = response[idx]
    //     this.fg_sub_materials = data['sub_materials'];

    //   } else {
    //     this.fg_sub_materials = []
    //   }
    // })
  }

}
