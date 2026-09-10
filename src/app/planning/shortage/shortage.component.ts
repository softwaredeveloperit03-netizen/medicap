import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-shortage',
  templateUrl: './shortage.component.html',
  styleUrls: ['./shortage.component.css']
})
export class ShortageComponent implements OnInit {
  plant_type = '';
  results;
  dosages;
  batches;
  bfr_list;
  mfr_list;
  dosage_form = '';
  product_name = '';
  product_code;
  grade = '';
  grades;
  products;
  pack_sizes=[];
  materials = [];
  number_of_batches = 0;
  mfr_batch_size = 0;
  bfr_batch_size = 0;
  selectedBatch = [];
  raw_materials;
  packing_materials;
  required_for = '';
  requirement = '';
  bom_no = '';
  bfr_no = ''
  mfr_no = ''
  fg_sub_materials;
  isShortage = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.getDosageTypes();
  }
  getProductsByDosage(value) {
    this.products = [];
    this.bfr_list = [];
    this.mfr_list = [];
    this.mfr_batch_size = 0;
    this.bfr_batch_size = 0;
    this.service.get('planning/raw.php?type=get_products_formulation_shortage_calculation&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });

  }

  getDosageForms() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }
  getDosageTypes() {
    this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
      this.fg_sub_materials = response;
    });
  }

  getProducts() {
    this.products = [];
    this.bfr_list = [];
    this.mfr_list = [];
    this.mfr_batch_size = 0;
    this.bfr_batch_size = 0;
    this.service.get('planning/raw.php?type=get_products_for_shortage_calculation&product_type=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }
  getRawMaterialsByBfrNo(index) {
    this.raw_materials = [];
    this.bfr_batch_size = this.bfr_list[index - 1]['batch_formula_weight'];
    this.service.get('planning/raw.php?type=get_raw_materials_by_bfr_no&bfr_no=' + this.bfr_no).subscribe(response => {
      this.raw_materials = response['raw_materials'];
      this.pack_sizes=response['pack_sizes'];
    });
  }

  getProductGrades() {
    this.service.get('production.php?type=getProductGrades&dosage_form=' + this.dosage_form + '&product_name=' + this.product_name).subscribe(response => {
      this.grades = response;
    });
  }
  getBfrList(index) {
    this.bfr_list = []
    this.mfr_batch_size = this.mfr_list[index - 1]['batch_size'];
    this.bfr_list = this.mfr_list[index - 1]['bfr_records']
    this.bfr_batch_size = 0;

  }
  getMfrList(index) {
    this.mfr_list = []
    this.grade = this.products[index - 1 ]['grade'];
    this.mfr_list = this.products[index - 1]['mfr_records'];


  }
  getMaterial(index) {
    this.bfr_batch_size = this.bfr_list[index - 1]['batch_formula_weight'];
    this.raw_materials = JSON.parse(this.bfr_list[index - 1]['raw_materials']);

  }
  getPlanQty() {
    this.isShortage = false;
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      material['plan_qty'] = +material['batch_qty'] * +this.number_of_batches;
      material['lod_qty'] = +parseFloat(((+material['plan_qty'] * +material['lod_per']) / 100) + '').toFixed(2);
      material['required_qty'] = +material['plan_qty'] + +material['lod_qty'];
      material['shortage_qty'] = +material['required_qty'] - +material['available_qty'];
      if (+material['shortage_qty'] < 0) {
        material['shortage_qty'] = 0;
      } else {
        this.isShortage = true;
      }
      this.materials[i] = material;
    }
  }
  sendIndend(shortageForm) {
    let temp = shortageForm.value;
    temp['material'] = this.raw_materials;
    temp['requirement'] = this.requirement;
    temp['required_for'] = this.required_for;

    this.service.post('production/shortage.php?type=sendIndend', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        // this.isNewForm=false;
        alertify.success('Indend Send successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
  calculateBatchQty() {
    // /number_of_batches
    for (let i = 0; i < this.raw_materials.length; i++) {
      let qty= Number( this.raw_materials[i]['batch_qty']* Number(this.number_of_batches));
      this.raw_materials[i]['plan_qty']  =parseFloat(qty + '').toFixed(2);

    }
    for (let i = 0; i < this.packing_materials.length; i++) {
      let qty= Number( this.packing_materials[i]['batch_qty']* Number(this.number_of_batches));
      this.packing_materials[i]['plan_qty'] =parseFloat(qty + '').toFixed(2);

    }
  }
  getPackingMaterial(idx){
    this.packing_materials = [];
    let pack_size = this.pack_sizes[idx-1]['pack_size'];
    let pack_unit = this.pack_sizes[idx-1]['pack_unit'];
    this.service.get('planning/raw.php?type=get_packing_materials_by_bfr_no&bfr_no=' + this.bfr_no +'&pack_size=' + pack_size +'&pack_unit=' + pack_unit).subscribe(response => {
      this.packing_materials = response;
    });
  }
  // download(){
  //   this.service.get('master/product.php?type=download_log&bfr_no=');
    
  // }
  download(){
    // this.service.open('pdf1/sampling.php?type=samplinglog');
    this.service.open('master/product.php?type=download_log');

}
}
