import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-batch-planning',
  templateUrl: './batch-planning.component.html',
  styleUrls: ['./batch-planning.component.css']
})
export class BatchPlanningComponent implements OnInit {

  results;
  isNewBP = false;
  isView = false;

  dosages;
  batches;
  dosage_form = '';
  product_name = '';
  product_code = '';
  grade = '';
  grades;
  products;
  bom_no;
  materials;
  isnote = false;
  shortMaterial = [];

  bombtn = false;
  isBOM = false;
  bom = {};
  isShowAPI = false;
  selectedMaterial = [];

  selectedBatch = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getBatchPlanningLog();
    this.getDosageForms();
  }

  getBatchPlanningLog() {
    this.service.get('production.php?type=getBatchPlanningLog').subscribe(response => {
      this.results = response;
    });
  }

  getDosageForms() {
    this.service.get('qaDepartment.php?type=getDosageForms').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts() {
    this.service.get('production.php?type=getDosageformByProduct&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getProductGrades() {
    this.service.get('production.php?type=getProductGrades&dosage_form=' + this.dosage_form + '&product_name=' + this.product_name).subscribe(response => {
      this.grades = response;
    });
  }

  getBatchSizes(index) {
    index = index - 1;
    this.product_code = this.grades[index].product_code;
    this.service.get('production.php?type=getBatchSizes&product_code=' + this.product_code).subscribe(response => {
      this.batches = response;
    });
  }

  getBOM() {
    this.service.get('production.php?type=getbombyno&bom_no=' + this.bom_no).subscribe(response => {
      this.bombtn = true;
      this.bom = response;
      this.materials = response['materials'];

      for (let i = 0; i < this.materials.length; i++) {
        if (this.materials[i].isShortage == 'yes') {
          this.isnote = true;
          this.shortMaterial = this.materials[i];
        }
      }
    });
  }

  showAPICalc(index) {
    this.selectedMaterial = this.materials[index];
    console.log(this.selectedMaterial['materials']);
    this.isShowAPI = true;
  }

  saveBatchPlanning() {
    if (this.dosage_form == '' || this.product_code == '' || this.bom_no == '' || this.materials.length == 0) {
      alert('All fields are required');
      return;
    }
    let temp = {};
    temp['dosage_form'] = this.dosage_form;
    temp['product_code'] = this.product_code;
    temp['bom_no'] = this.bom_no;
    temp['materials'] = this.materials;
    this.service.post('production.php?type=saveBatchPlanning', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alert('saved successfully');
        this.isNewBP = false;
        this.product_name = '';
        this.product_code = '';
        this.dosage_form = '';
        this.grade = '';
        this.getBatchPlanningLog();
      } else {
        alert('error');
      }
    });
  }

  addIndend(index, indend) {
    if (indend == 'no') {
      this.materials[index].indend = 'yes';
    } else if (indend == 'yes') {
      this.materials[index].indend = 'no';
    }
  }

  sortage_indend() {

  }

  viewBatch(index) {
    this.selectedBatch = this.results[index];
    this.isView = true;
  }

}
