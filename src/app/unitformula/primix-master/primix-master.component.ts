import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-primix-master',
  templateUrl: './primix-master.component.html',
  styleUrls: ['./primix-master.component.css']
})
export class PrimixMasterComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getRawMaterialType();
    this.getPrimixLog();
  }
  
  material_type = 'Raw Material';
  materialSubTypes;
  getRawMaterialType(){
    this.service.get('master/materialtype.php?type=getMatTypeByMatType&material_type='+this.material_type).subscribe(response => {
      this.materialSubTypes= response;
     });
  }

  materials;
  material_subtype = '';
  getApprovedRawMaterials(value) {
      this.service.get('common.php?type=getMaterialsByType&material_type=Raw Material&material_subtype=' + this.material_subtype).subscribe(response => {
        this.materials = response;
      });
  }



  results;
  getPrimixLog() {
      this.service.get('production/master.php?type=getPrimixLog').subscribe(response => {
        this.results = response;
      });
  }


  selectedMaterial= {};
  getSelectedMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
    }
  }



  materialList = [];
  add(data) {
    if(!data.valid){
      alertify.error("All Field Required!!!!!!!");
      return;
    }
    let temp = data.value;    
    temp['material_type'] = 'Raw Material';
    temp['role'] = 'Primix';                                                                                                                          
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['grade'] = this.selectedMaterial['grade'];
    this.materialList.push(temp);
    data.resetForm();
  }


  delRawMat(index) {
    this.materialList.splice(index, 1);
  }


  savePremix(data) {

    if (!data.valid) {
      alertify.error('All fields are required!!!!!!!!');
      return;
    }
  
    let temp = data.value;
    temp['materialList'] = this.materialList;

    this.service.post('production/master.php?type=savePremix', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Premix Saved successfully!');
        this.getPrimixLog();
        this.isNew = false;
        this.materialList = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 

  isView = false; 
  isNew = false;

  selectedPremix = [];
  view(data){
    this.selectedPremix = data;
    this.isView = true;
  }









   searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }








}
