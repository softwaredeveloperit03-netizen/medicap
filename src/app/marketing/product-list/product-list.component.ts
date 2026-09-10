import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-product-list',
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.css']
})
export class ProductListComponent implements OnInit {

  
isView = false;
isEdit = false;
results;
isMrpModel = false;

selectedResult = [];
styles;
product_name = '';
style = '';
isStyle = false;
selected = [];  
dosage_form ='';
doseunits;
grade = '';
grades;
type;
isDelete = false;
dosages;
isMaterial = false;
isDoseUnit = false;
mrp;
shelf_life;
product_code;
manufactured_for;
manufactured_under;
dosage_type = '';
generic_name;
packing_style;
testing_time;
retest;
thera;
storage_condition;
apperance;
tshape;
tsize;
hsn;
isShelf = false;
gtin;
colors;
fg_sub_materials=[];
storages;
isStorage = false;
isColor = false;
color = '';
plant;
fg_shapes = [];
fg_sub_types = [];
fg_sizes = [];
shelf = '';
short_code;
dose_unit = '';
shelfs;
product_color = '';
labels = [];
// category = 'Branded';
category = '';
flag = 0;
results1=[];
batch_type;
label_claim	;
tertiary_packing;
master_cartain;
wad_sealing;
shrink;
scoop_add;
artwork;
mopcup;
material_type: any;
plant_id:any;
packing_type: any;
addpacking = false;
add_primary_packing=false;
add_secondary_packing=false;
add_pack_size=false;
add_no_pouch=false;
add_capsule_size=false;
add_mono_cartain=false;
add_mono_qty=false;
add_master_mono_qty=false;
Addtertiary_packing=false;
unit;
similar_name;
selectedFile1: File;
selectedFile2: File;
selectedFile3: File;
selectedFile4: File;
selectedFile5: File;
selectedFile6: File;
artworkList = [];
list = [];

searchQuery;
  productsList: any
  isCategory = false;
clientsList:any
plantID = '';
plants;

constructor(private service: DataAccessService, private router: Router) { 
  this.plant_id = this.service.getPlantConfigFields('plant_id');
}
ngOnInit(): void {
  this.plants = JSON.parse(localStorage.getItem('all_plants'));
}

 


 
 

 

samplejjj() {
  this.isCategory = true
}


 

 

 

getproductlist() {
  this.service.get('master/product.php?type=getBrandProductsLogForHO&plantID='+this.plantID).subscribe(response => {
    this.results = response;
   
  });
}
 
 
 
 

 
 

 

 
view(index) {
  this.selectedResult = this.filteredMaterials[index];
  this.label_claim = this.selectedResult['label_claim'];
  this.artwork = this.selectedResult['artwork'];
  this.mopcup = this.selectedResult['mopcup'];

  // Process the rest of the fields similarly
  this.selectedResult['packing_type'] = this.selectedResult['packing_type'] || 'NA';
  this.selectedResult['primary_packing'] = this.selectedResult['primary_packing'] || 'NA';
  this.selectedResult['secondary_packing'] = this.selectedResult['secondary_packing'] || 'NA';
  this.selectedResult['pack_sizes'] = this.selectedResult['pack_sizes'] || 'NA';
  this.selectedResult['nos_pouch'] = this.selectedResult['nos_pouch'] || 'NA';
  this.selectedResult['capsule_size'] = this.selectedResult['capsule_size'] || 'NA';
  this.selectedResult['mono_cartain'] = this.selectedResult['mono_cartain'] || 'NA';
  this.selectedResult['scoop_add'] = this.selectedResult['scoop_add'] || 'NA';
  this.selectedResult['Shrink'] = this.selectedResult['Shrink'] || 'NA';
  this.selectedResult['wad_sealing'] = this.selectedResult['wad_sealing'] || 'NA';
  this.selectedResult['mono_qty'] = this.selectedResult['mono_qty'] || 'NA';
  this.selectedResult['master_cartain'] = this.selectedResult['master_cartain'] || 'NA';
  this.selectedResult['master_mono_qty'] = this.selectedResult['master_mono_qty'] || 'NA';
  this.selectedResult['tertiary_packing'] = this.selectedResult['tertiary_packing'] || 'NA';
  this.selectedResult['tertiary_packing_data'] = this.selectedResult['tertiary_packing_data'] || 'NA';
  this.isView = true;
  console.log(this.label_claim);
}
id;
artwork_file;
artwork_status;
 
 
isShown = false;
toggleShow() {
  this.isShown = !this.isShown;
}
  









 
close(value) {
  if (value == 'style') {
    this.isStyle = false;
  }
}
close1(){
  this.isDelete =false;
}
 
 
 

viewArt(url) {

   window.open(this.service.url+'../../upload/product/' + this.selectedResult['artwork_file']);
  
  window.open(url, '_blank');
}
viewShade(url) {
 
   window.open(this.service.url+'../../upload/product/' + this.selectedResult['mopcup_file']);
  
  window.open(url, '_blank');
}



get filteredMaterials(): any[] {
  if (!this.searchQuery || this.searchQuery.trim() === '') {
    return this.results; // If search query is empty or whitespace, return all materials
  }
  
  const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

  return this.results.filter(material => {
    // Check if any field of the material contains the search query
    return Object.entries(material).some(([key, value]) => {
      if (key === 'entry_date') {
        // Convert the value to a Date object if it's not already
        const dateValue = typeof value === 'string' ? new Date(value) : value;
        // Check if the date value is valid and includes the search query
        return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
      } else {
        // Convert field value to lowercase and check if it includes the search query
        return value && value.toString().toLowerCase().includes(query);
      }
    });
  });
}

 
}
