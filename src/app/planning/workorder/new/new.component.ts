import { HttpClient } from '@angular/common/http';
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

  
  
  pendingpo;
  selectresult = [];
  isView = false;
  reqAnaData;

  units;
  departments;
  vendors;
  

  constructor(private service:DataAccessService  , private router: Router) { 
 
  }

  ngOnInit() {
    this.getPOsLog();
    this.getManufactures();

    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
   }

  getPOsLog(){
    this.service.get('marketing/po.php?type=getInprocessReqAnalysis').subscribe(response =>{
      this.pendingpo =response;
    });
  }

 
 
  getManufactures() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.vendors = response;
    });
  }
 

  mergedRawMaterials =[];
  mergedPackingMaterials =[];
  
   commonRawMaterials = [];
   uniqueRawMaterials = [];
   commonPackMaterials = [];
   mergedArray = [];
   uniquePackMaterials = [];

  commonMaterials(){
    

    this.mergedRawMaterials =[];
    this.mergedPackingMaterials =[];
    
    this.commonRawMaterials = [];
    this.uniqueRawMaterials = [];
    this.commonPackMaterials = [];
    this.uniquePackMaterials = [];
   


 
      this.pendingpo.forEach((index) => {
        this.mergedRawMaterials.push(...index.raw_materials);
        this.mergedPackingMaterials.push(...index.packing_configuration);
      });


      console.log(this.mergedRawMaterials);
      console.log(this.mergedPackingMaterials);

      const materialMapraw = new Map();

      this.mergedRawMaterials.forEach(material => {
        if (materialMapraw.has(material.material_code)) {
          const existingMaterial = materialMapraw.get(material.material_code);
          existingMaterial.required_qty = Number(existingMaterial.required_qty) + Number(material.required_qty);
          materialMapraw.set(material.material_code, existingMaterial);
        } else {
          materialMapraw.set(material.material_code, { ...material });
        }
      });

      materialMapraw.forEach((material, code) => {
        const occurrences = this.mergedRawMaterials.filter(m => m.material_code === code).length;
        if (occurrences > 1) {
          this.commonRawMaterials.push(material);
        } else {
          this.uniqueRawMaterials.push(material);               
        }
      });




      const materialMapPack = new Map();

      this.mergedPackingMaterials.forEach(material => {
        if (materialMapPack.has(material.material_code)) {
          const existingMaterial = materialMapPack.get(material.material_code);
          existingMaterial.required_qty = Number(existingMaterial.required_qty) + Number(material.required_qty);
          materialMapPack.set(material.material_code, existingMaterial);
        } else {
          materialMapPack.set(material.material_code, { ...material });
        }
      });

      materialMapPack.forEach((material, code) => {
        const occurrences = this.mergedPackingMaterials.filter(m => m.material_code === code).length;
        if (occurrences > 1) {
          this.commonPackMaterials.push(material);
        } else {
          this.uniquePackMaterials.push(material);               
        }
      });



      console.log('Common Raw Materials:');
      console.log(this.commonRawMaterials);
      console.log('Unique Raw Materials:');
      console.log(this.uniqueRawMaterials);

      console.log('Common Pack Materials:');
      console.log(this.commonPackMaterials);
      console.log('Unique Pack Materials:');
      console.log(this.uniquePackMaterials);

      this.isView = true;






  }

  isMerge = false;
 

  sendForIndent(){

    this.btn = true;

    this.mergedArray = [];


    const extractKeyValuePairs = (array, keys) => {
      return array.map(item => {
        let newObj = {};
        keys.forEach(key => {
          if (item.hasOwnProperty(key)) {
              newObj[key] = item[key];
          }
        });
        return newObj;
      });
    };
    
    // Define the keys you want to extract
    const keysToExtract = ['unit', 'required_qty', 'balance_qty','material_name', 'material_code', 'material_type']; // Replace 'key1', 'key2' with your actual keys
    
    // Extract key-value pairs and merge the arrays
    this.mergedArray = [
      ...extractKeyValuePairs(this.commonRawMaterials, keysToExtract),
      ...extractKeyValuePairs(this.uniqueRawMaterials, keysToExtract),
      ...extractKeyValuePairs(this.commonPackMaterials, keysToExtract),
      ...extractKeyValuePairs(this.uniquePackMaterials, keysToExtract)
    ];


    console.log(this.mergedArray);


    this.filtersFO = this.pendingpo.filter(pending => pending.pid).map(({ pid, order_no,product_code }) => ({ pid, order_no,product_code  }));

    this.isMerge = true;
  }
  filtersFO;

  delIndent(index){
    this.mergedArray.splice(index,1);
  }

  btn = true;
  save(){

    this.btn = false;

    if (this.mergedArray.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    console.log(this.mergedArray);
    this.service.post('purchase/indent.php?type=autoPurchaseSaveIndent', JSON.stringify(this.mergedArray)).subscribe(response => {
      if (response['status'] == 'success') {
        this.mergedArray = [];
        this.completActivity();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  completActivity(){

    let temp ={};
    temp['filtersFO'] = this.filtersFO;
     
    this.service.post('purchase/indent.php?type=CompletIndentReqAnalyasis', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.btn = true;
        this.isMerge = false;
        this.isView = false;
        alertify.success('Indend records saved successfully');
         this.getPOsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

 

}
  