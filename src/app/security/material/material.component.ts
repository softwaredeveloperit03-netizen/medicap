import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component ({
  selector: 'app-material',
  templateUrl: './material.component.html',
  styleUrls: ['./material.component.css']
})
export class MaterialComponent implements OnInit {
  inwords;
  vendors;
  materials;
  po_details;
  materialTypes;
  materialSubtypes;
  materials1;
  isPO = true;
  isFirst = false;
  isShow = false;
  selectedMaterialType;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getMaterialInwords();
    this.getTodaysMaterials();
    this.getMaterialTypes();
  }

  checkInwordtype(value) {
    if(value === "Purchase Order") {
      this.isPO = true;
    } else {
      this.isPO = false;
    }
  }

  getTodaysMaterials() {
    this.service.get('security.php?type=getTodaysInword')
    .subscribe(response => {
      this.inwords = response;
    });
  }

  getMaterialInwords() {
    this.service.get('security.php?type=getMaterialInwords')
    .subscribe(response => {
      this.materials = response;
    });
  }

  getPODetails(index) {
    index = index-1;
    this.po_details = this.materials[index];
    this.isShow = true;
  }

  getMaterialTypes() {
    this.service.get('security.php?type=getMaterialTypes').subscribe(response => {
      this.materialTypes = response;
    });
  }

  saveMaterial(materialForm) {
    if (!materialForm.valid) {
      alert('All fields are required');
      return;
    }
   let temp = materialForm.value;
   temp["vendor_no"] = this.po_details["vendor_no"];
   temp['isgeneral'] = this.po_details["isgeneral"];
    this.service.post('security.php?type=saveInwordMaterial', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
        alert('Material Inword successfully');
        this.getMaterialInwords();
        this.getTodaysMaterials();
        materialForm.resetForm();
        this.isFirst = false;
      } else {
        alert('Please Try Again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error occured');
      }
    });
  }

  getMaterialSubtype(value) {
    this.selectedMaterialType = value;
    this.service.get('security.php?type=getMaterialSubtypes&material_type=' + value).subscribe(response => {
      this.materialSubtypes = response;
    });
  }

  getMaterialName(value) {
    this.service.get('security.php?type=getMaterialNames&material_type=' + this.selectedMaterialType + '&material_subtype=' + value).subscribe(response => {
      this.materials1 = response;
    });
  }
}
