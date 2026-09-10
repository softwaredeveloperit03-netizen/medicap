import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  product_type = '';
  products;
  units;
  product_id =0;
  materials;
  packings;
  materialList = [];
  packingList = [];
  selecteddosage=[];
  amaterials;
  dosages;
  dosage_form='';
  unit='';
  stageListadd=[];
  selectedPacking=[];

  selectedMaterial=[];

  min_per = 0;
  max_per = 0;
  min_output_qty = 0;
  max_output_qty = 0;
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }

  getProductsByDosage(value) {
    this.service.get('common.php?type=getProductsByDosage&product_type=' + value).subscribe(response => {
      this.products = response;
    });
  }

  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype='+ value).subscribe(response => {
      this.materials = response;
    });
  }

  getSelectedMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial=this.materials[index];
    }
  }
  getProduct(idx){
    this.product_id = this.products[idx-1].id;
  }

  getMaterial(value){
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' +value).subscribe(response => {
      this.packings = response;
    });
  }

  getPackings(index){
    index = index - 1;
    if (index !== -1) {
      this.selectedPacking = this.packings[index];
    }
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['material_name']=this.selectedMaterial['material_name'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }

  dele(index) {
    this.materialList.splice(index, 1);
  }

  addPage(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['material_name']=this.selectedPacking['material_name'];
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }

  deletestage(index){
    this.stageListadd.splice(index, 1);
  }
  del(index) {
    this.packingList.splice(index, 1);
  }

  save(data) {
    // if (!data.valid) {
    //   alertify.error('All fields are required!');
    //   return;
    // }
    let temp = data.value;
    if (temp['bom_type'] == 'Fresh Batch') {
      if (this.materialList.length == 0) {
        alertify.error('Raw Materials are required');
        return;
      }
    }
    
    if (this.packingList.length == 0) {
      alertify.error('Packings are required');
      return;
    }
    temp['raw_materials'] = this.materialList;
    temp['packing_materials'] = this.packingList;
    temp['unit']=this.unit;
    temp['product_id']= this.product_id;
    this.service.post('production/master.php?type=saveMFR', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product MFR initiated successfully!');
        this.router.navigate(['/planning/unitformula']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  input_qty = 200;
  calculate() {
    this.min_output_qty = +parseFloat((this.input_qty * this.min_per) + '').toFixed(2);
    this.max_output_qty = +parseFloat((this.input_qty * this.max_per) + '').toFixed(2)
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }

  bomType(event) {
    if (event == 'Fresh Batch') {
      this.input_qty = 200;
    } else {
      this.input_qty = 502;
    }
  }
}
