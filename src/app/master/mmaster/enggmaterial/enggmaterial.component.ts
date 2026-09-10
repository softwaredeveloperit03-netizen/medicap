import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-enggmaterial',
  templateUrl: './enggmaterial.component.html',
  styleUrls: ['./enggmaterial.component.css']
})
export class EnggmaterialComponent implements OnInit {

  plant_id;
  Hsn:any;
  showCodingPattern = false;

  constructor(public service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }
  

  ngOnInit(): void {
    this.getMaterialType();
    this.getSorageConditions();
    this.getGST();
    this.getUnits();
    this.getequipment();
  }


  material_type = 'Stationary';

  types;
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getMatTypeByMatType&material_type='+this.material_type).subscribe(response => {
      this.types= response;
     });
  }


  storage_conditions;

  getSorageConditions() {
     this.service.get('common.php?type=getStorageConditions').subscribe(response => {
      this.storage_conditions = response
    })
  } 

  gst_list;
  getGST() {
    this.service.get('common.php?type=getGST').subscribe(response => {
       this.gst_list = response;
    });
  }
  

  units;
  getUnits() {
    this.service.get('common.php?type=getUnits_List').subscribe(response => {
      this.units= response;
    });
  }

  equipments;
  getequipment() {
     this.service.get('master/general.php?type=getequipments').subscribe(response => {
      this.equipments = response
    })
  }



   materialSubTypeCode='';

  setSubMaterialType(index){
    this.materialSubTypeCode='';
    this.materialSubTypeCode = this.types[index-1].Short_Code;
  }


 

 
 
  saveGeneralMaterial(data) {
    if (!data.valid) {
      alertify.error('Please enter all required fields.');
      return;
    }

    const temp = { ...data.value };
    temp['plant_code'] = localStorage.getItem('plant_code') || '';
    temp['materialSubTypeCode'] = this.materialSubTypeCode;
    temp['storage_condition'] = temp['storage_condition'] || 'NA';
    temp['hsn'] = temp['hsn'] || '';
    temp['capacity'] = temp['capacity'] || '';

    this.service
      .postTextResponse('master/general.php?type=saveGeneralMaterial', JSON.stringify(temp))
      .subscribe({
        next: (raw: string) => {
          let response: any;
          try {
            response = this.service.parsePhpJson(raw);
          } catch {
            alertify.error('Invalid server response. Deploy master/general.php if not done yet.');
            return;
          }
          if (response?.status === 'success') {
            data.resetForm();
            alertify.success(response?.material_code
              ? `Material saved (${response.material_code})`
              : 'Material saved successfully');
            this.router.navigate(['/master/mmaster/generalmat']);
          } else {
            alertify.error(response?.message || response?.status || 'Save failed');
          }
        },
        error: () => alertify.error('Save failed. Check your connection.'),
      });
  }



















}


