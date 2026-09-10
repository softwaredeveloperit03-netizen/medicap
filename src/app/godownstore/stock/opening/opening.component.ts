import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-opening',
  templateUrl: './opening.component.html',
  styleUrls: ['./opening.component.css'],
  providers:[DatePipe]
})
export class OpeningComponent implements OnInit {

  isNew = false;
  results;
  clicked: boolean = false;
  materials;
  vendors;
  products;
  material_type='';
  material_subtype = '';
  units;
  mfg_date='';
  exp_date='';
  pack_size='';
  batches = [];
  selectedMaterial=[];
  selectedProduct=[];
  isMaterial = false;
  material_code;
  constructor(private service: DataAccessService,private router:Router,private datePipe : DatePipe) {
   }

  ngOnInit() {
    // this.getVendors();
    this.service.observableUnit.subscribe(response=>{
      this.units=response;
    });
   
  }
  productList = [];
  addProduct(data) {
    const selectedItems = this.materials.filter((term) => term.selected);
  
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    if(this.material_type!='Finish Product'){
      this.productList.push(
        ...selectedItems.map((item) => ({
          material_name: item.material_name,
          material_code: item.material_code,
          batch_no: item.batch_no,
          pack_size: item.pack_size,
          qty: item.qty,
          unit: item.unit,
          ar_no: item.ar_no,
          grn_no: item.grn_no,
          grn_date: item.grn_date,
          assay: item.assay,
          mfg_date: item.mfg_date,
          exp_date: item.exp_date,
          status: item.status,
        }))
      );
    }
    else if(this.material_type=='Finish Product'){
      this.productList.push(
        ...selectedItems.map((item) => ({
          material_name: item.product_name,
          material_code: item.product_code,
          batch_no: item.batch_no,
          pack_size: item.pack_size,
          qty: item.qty,
          unit: item.unit,
          ar_no: item.ar_no,
          grn_no: item.grn_no,
          grn_date: item.grn_date,
          mfg_date: item.mfg_date,
          exp_date: item.exp_date,
          status: item.status,
        }))
      );
    }
    this.productList.forEach((item) => {
      // Check if the unit is "gms" and convert to kg
      if (item.unit === 'gms') {
        item.qty /= 1000; // Convert grams to kilograms
        item.unit = 'kg'; // Update the unit to kilograms
      }
    });
  
    // Reset selected property for each selected item
    for (const item of selectedItems) {
      item.selected = false;
    }
  
    data.resetForm();
  console.log(this.productList)
  }
  deleteProducts(index) {
    this.productList.splice(index, 1);
  }
  selected_master_type = '';
  sub_types;
  material_sub_type_id;
  table;
  getSubMaterials(value) {
    if(value=='Raw Material'){
      this.table='material';
      this.sub_types='Raw Material';
    }
   else if(value=='Packing Material'){
      this.table='material';
      this.sub_types='Packing Material';
    }
   else if(value=='General Material'){
      this.table='general_material';
      this.sub_types='General Material';
    }
   else if(value=='Chemical Material'){
      this.table='chemical';
      this.sub_types='General Material';
    }
   else if(value=='Engineering'){
      this.table='general_material';
      this.sub_types='Engineering Spares';
    }
   else if(value=='Equipments'){
      this.table='equipment';
      this.sub_types='';
    }
   else if(value=='Glassware Material'){
      this.table='glassware';
      this.sub_types='';
    }
   else if(value=='Finish Product'){
      this.table='product';
      this.sub_types='';
    }

    this.service.get('common.php?type=getMaterialsforstock&table=' + this.table + '&sub_types='+this.sub_types).subscribe(response => {
      this.materials = response;
    });

  }

  
  //   this.service.observableMaterialTypes.subscribe(response => {
  //     if (value == 'Raw Material' || value == 'Packing Material' || value == 'General Material' ||
  //       value == 'Engineering' || value == 'Equipments' || value == 'Finish Product' || value == 'Services') {
  //       let idx = 0;
  //       this.selected_master_type = value;
  //       this.service.observableMaterialTypes.subscribe(response => {
  //         for (let i = 0; i < response.length; i++) {
  //           if (response[i]['material_type'] == value) {
  //             idx = i;
  //           }
  //         }
  //         this.sub_types = [];
  //         this.material_type = value;
  //         this.material_sub_type_id = response[idx]['id'];
  //         this.sub_types = response[idx]['sub_materials'];

  //       })
  //     } else {
  //       this.getMaterials(value);
  //       this.material_type = value;
  //       this.material_sub_type_id = 0;
  //     }
  //     // this.sub_types = [];
  //     // this.sub_types = response[idx - 1]['sub_materials'];
  //     // this.has_nature_of_material= response[idx - 1]['has_nature_of_material'];
  //   });

  // }
  getMaterialsBySubType(value) {

    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value + "&material_nature=").subscribe(response => {
      this.materials = response;
    });
  }
  selectMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
    } else {
      this.selectedMaterial = [];
    }
  }

  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype='+value).subscribe(response => {
      this.materials = response;
      this.isMaterial=true;
    });
  }

  getProducts(value) {
    this.service.get('common.php?type=getProductsByDosage&product_type='+value).subscribe(response => {
      this.products = response;
    });
  }
;
  saveOpeningStock(data) {
    this.clicked = true;
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    // if(this.material_type=='Finish Goods'){
    //   temp['product_code'] = temp['product_code'].product_code;
    // }
    // temp['material_code'] = this.material_code
    // temp['batches'] = this.batches;
    temp['material_list'] = this.productList;
    console.log('ve', temp['vendor_no'] );
    // if(this.material_type!='Finish Product'){
      this.service.post('store/dispensing.php?type=saveStock', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Record Saved Successfully');
          this.router.navigate(['/store']);
  
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    // }
    // else if(this.material_type=='Finish Product'){
    //   this.service.post('store/opening.php?type=saveStock1', JSON.stringify(temp)).subscribe(response => {
    //     if (response['status'] == 'success') {
    //       alert('Record Saved Successfully');
    //       this.router.navigate(['/store']);
  
    //     } else {
    //       alert('Failed: An error occured, please try again!');
    //     }
    //   });
    // }
 
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    this.batches[this.batches.length] = data.value;
    data.reset();
  }

  deleteBatch(index){
    this.batches.splice(index,1);
  }



  /* saveOpeningStock(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('store/opening.php?type=saveDirectStock', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.router.navigate(['/store']);

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  } */


}
