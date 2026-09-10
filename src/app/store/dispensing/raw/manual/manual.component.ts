import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-manual',
  templateUrl: './manual.component.html',
  styleUrls: ['./manual.component.css']
})
export class ManualComponent implements OnInit {
 plants;
 products;
 materials;
 selectedMaterial=[];
 materialList = [];
 units;
 material_subtype='';
 material_code='';
 dispensing_for='';
 cleaning_to='';
 cleaning_from='';
 rlaf_start='';
 rlaf_stop='';
 dispensing_start='';
 dispensing_stop='';
 operator;
 ars;
 storePersons;
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getPlants();
    this.getProducts();
    this.getUnits();
     this.getOperators();
     this.getStorePersons();
  }

  getPlants(){
    this.service.get('common.php?type=getCompanyUnits').subscribe(response =>{
      this.plants = response;
    });
  }
  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  getRawMaterials() {
    this.service.get('store/requisition.php?type=getRawMaterials&material_subtype='+this.material_subtype+'&dispensing_for='+this.dispensing_for).subscribe(response => {
      this.materials = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getOperators(){
    this.service.get('common.php?type=getOperators').subscribe(response => {
      this.operator = response;
    });
  }
  
  getStorePersons(){
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.storePersons = response;
    });
  }

  getSelectedMaterial(index){
    index = index-1;
    this.selectedMaterial=this.materials[index];
    this.ars = this.selectedMaterial['ars']
/*     console.log(this.ars)
    console.log(this.selectedMaterial['material_name']) */
  }
  add(data) {
    let temp=data.value;
    temp['material_name']=this.selectedMaterial['material_name'];
    temp['material_subtype']=this.selectedMaterial['material_subtype'];
    temp['material_code']=this.selectedMaterial['material_code'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (this.materialList.length == 0) {
      alertify.error('Materials are required');
      return;
    }
    let temp = data.value;
    temp['materials'] = this.materialList;

    this.service.post('store/requisition.php?type=saveDispensing', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Save successfully!');
        this.router.navigate(['/store/dispensing']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
