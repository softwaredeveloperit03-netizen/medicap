import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {


  vendors;
  departments;
  materials_data: any;
  show_materials =[];
  selectedMaterial: any;
   
  constructor(private service: DataAccessService, private router: Router) {  }

  ngOnInit() {
    this.getallmaterial();
    this.getDepartment();
   }

  material_type = 'Raw Material';

  getallmaterial() {
    const mt = encodeURIComponent(this.material_type);
    this.service.get('master/rnd_material.php?type=getallmatdataForIndent&material_type=' + mt).subscribe(response => {
      this.materials_data = response;
    });
  }

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
    
  searchTerm: string;
 
  get filteredItems() {
    const list = Array.isArray(this.materials_data) ? this.materials_data : [];
    return list.filter(item => (item.material_name || '').toLowerCase().includes(this.searchTerm?.toLowerCase() || ''));
  }

  selectItem(item: any) {
    this.selectedMaterial = item;
  }
 
  add(){
    if(this.selectedMaterial['artwork'] == 'YES'){
      this.getStockBookByMaterialCode(this.selectedMaterial['material_code']);
    }else{
      this.selectedMaterial['department'] = 'Product Development';
      this.show_materials.push(this.selectedMaterial);
    }
    this.getManufactures();
  }


  stock_data ={};
  isPopUp = false;

  getStockBookByMaterialCode(material_code) {
    this.service.get('store/opening.php?type=getStockBookByMaterialCode&material_code='+material_code).subscribe(response => {
      this.stock_data = response;
      if(response['stock_version_no'] ==  response['curr_version_no']){
        this.selectedMaterial['department'] = 'Store';
        this.show_materials.push(this.selectedMaterial);
      }else{
        if(response['balance_qty'] > 0){
          this.isPopUp = true;
        }else{
          this.selectedMaterial['department'] = 'Store';
          this.show_materials.push(this.selectedMaterial);
        }
      }
    });
  }

  getManufactures() {
    this.service.get('common.php?type=getManufacturersForIndent&material_code='+this.selectedMaterial['material_code']).subscribe(response => {
      this.vendors = response;
    });
  }


  CheckValue(value){
    if(value == 'Map Material'){
      window.open('#/purchase/vendor/material/new', '_blank');
    }
  }

  requirement ='';

  final_material =[];

  addindent(data){
    if (!data.valid) {
      alertify.error('Please enter required field');
      return;
    }
    let temp = data.value;
    temp['requirement'] = this.requirement;
    this.final_material.push(temp);
    this.show_materials=[];
  }

  deleteshowmat(index){
    this.final_material.splice(index,1);
  }
 
  save(){

    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    console.log(this.final_material);
    this.service.post('purchase/indent.php?type=saveIndentStore', JSON.stringify(this.final_material)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.final_material = [];
        this.router.navigate(['/rnd/indend/raw']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    
  }
 



}
