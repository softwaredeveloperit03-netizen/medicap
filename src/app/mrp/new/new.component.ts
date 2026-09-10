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

  isLoading = true;
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
    this.getPOsLog1();
    this.getmrp_raised_indnd_qty();
    
    this.getManufactures();
    this.getStoreEmployee();

    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
   }
bookedQtys;
   getmrp_raised_indnd_qty() {
  this.service.get('mrp/mrp.php?type=getmrp_raised_indnd_qty').subscribe(response => {
      this.bookedQtys = response;    
       
  });
  
}
pendingpos;
   getPOsLog1() {
  this.service.get('mrp/mrp.php?type=getPOsLogForSplits').subscribe(response => {
      this.pendingpos = response['prepares'];    
      this.setNoOfBatches();
  });
  
}
getPOsLog() {
  this.service.get('marketing/po.php?type=getInprocessReqAnalysis').subscribe(response => {
    this.pendingpo = response;

    for (let i = 0; i < this.pendingpo.length; i++) {
      let consumeableMaterial = this.pendingpo[i].consumeableMaterial;
      let primary_pm_list = this.pendingpo[i].primary_pm_list;
      let raw_materials = this.pendingpo[i].raw_materials;
      let packing_configuration = this.pendingpo[i].packing_configuration;
      let splits = this.pendingpo[i].splits;

      // ✅ Fix: handle each individually
      raw_materials = Array.isArray(raw_materials) ? raw_materials : [];
      primary_pm_list = Array.isArray(primary_pm_list) ? primary_pm_list : [];
      packing_configuration = Array.isArray(packing_configuration) ? packing_configuration : [];
      consumeableMaterial = Array.isArray(consumeableMaterial) ? consumeableMaterial : [];

      console.log(`📦 Processing PO: ${this.pendingpo[i].order_no}`);
      console.log("  Raw Materials:", raw_materials.length);
      console.log("  Consumables:", consumeableMaterial.length);
      console.log("  Primary PM List:", primary_pm_list.length);
      console.log("  Packing Config:", packing_configuration.length);
      console.log("  Splits:", splits?.length || 0);

      // Extract month and year from the current pendingpo
      let month = this.pendingpo[i].month;
      let year = this.pendingpo[i].year;

      // ✅ Raw Materials
      for (let j = 0; j < raw_materials.length; j++) {
        raw_materials[j]['splistCal'] = [];
        let calculatedValueTotal = 0;

        for (let k = 0; k < splits.length; k++) {
          if (!splits[k]) continue;
          let unit = raw_materials[j].unit;
          let calculatedValue = 0;

          if (unit == 'gm') {
            calculatedValue = (raw_materials[j].qty * splits[k].oder_qty) / 1000;
          } else if (unit == 'mg') {
            calculatedValue = (raw_materials[j].qty * splits[k].oder_qty) / 1000000;
          } else {
            calculatedValue = (raw_materials[j].qty * splits[k].oder_qty);
          }

          raw_materials[j]['splistCal'].push({
            month: splits[k].month,
            year: splits[k].year,
            calculatedValue
          });
          calculatedValueTotal += calculatedValue;
        }

        raw_materials[j].required_qty = calculatedValueTotal;
      }

      // ✅ Consumables
      for (let j = 0; j < consumeableMaterial.length; j++) {
        consumeableMaterial[j]['splistCal'] = [];
        let calculatedValueTotal = 0;

        for (let k = 0; k < splits.length; k++) {
          if (!splits[k]) continue;
          let unit = consumeableMaterial[j].unit_name;
          let calculatedValue = 0;

          if (unit == 'gm') {
            calculatedValue = (consumeableMaterial[j].qty * splits[k].oder_qty) / 1000;
          } else if (unit == 'mg') {
            calculatedValue = (consumeableMaterial[j].qty * splits[k].oder_qty) / 1000000;
          } else {
            let orderQty = parseFloat(splits[k].oder_qty || 0);
            let requiredQty = parseFloat(consumeableMaterial[j].required_qty || 0);
            let configOrderQty = parseFloat(this.pendingpo[i]['order_qty']);
            calculatedValue = (orderQty * requiredQty) / configOrderQty;
          }

          consumeableMaterial[j]['splistCal'].push({
            month: splits[k].month,
            year: splits[k].year,
            calculatedValue
          });
          calculatedValueTotal += calculatedValue;
        }

        consumeableMaterial[j].required_qty = calculatedValueTotal;
        consumeableMaterial[j].material_type = consumeableMaterial[j].material_type || 'Consumable';
      }

      // // ✅ Primary PM List
      for (let j = 0; j < primary_pm_list.length; j++) {
        primary_pm_list[j]['splistCal'] = [];
        let calculatedValueTotal = 0;

        for (let k = 0; k < splits.length; k++) {
          if (!splits[k]) continue;
          let unit = primary_pm_list[j].unit_name;
          let calculatedValue = 0;

          if (unit == 'gm') {
            calculatedValue = (primary_pm_list[j].qty * splits[k].oder_qty) / 1000;
          } else if (unit == 'mg') {
            calculatedValue = (primary_pm_list[j].qty * splits[k].oder_qty) / 1000000;
          } else {
            let orderQty = parseFloat(splits[k].oder_qty || 0);
            let requiredQty = parseFloat(primary_pm_list[j].required_qty || 0);
            let configOrderQty = parseFloat(this.pendingpo[i]['order_qty']);
            calculatedValue = (orderQty * requiredQty) / configOrderQty;
          }

          primary_pm_list[j]['splistCal'].push({
            month: splits[k].month,
            year: splits[k].year,
            calculatedValue
          });
          calculatedValueTotal += calculatedValue;
        }

        primary_pm_list[j].required_qty = calculatedValueTotal;
        primary_pm_list[j].material_type = primary_pm_list[j].material_type || 'Primary Packing Material';
      }

      // ✅ Packing Config
      for (let j = 0; j < packing_configuration.length; j++) {
        packing_configuration[j]['splistCal'] = [];
        let calculatedValueTotal = 0;

        for (let k = 0; k < splits.length; k++) {
          if (!splits[k]) continue;
          let unit = packing_configuration[j].unit;
          let calculatedValue = 0;

          if (unit == 'gm') {
            calculatedValue = (packing_configuration[j].qty * splits[k].oder_qty) / 1000;
          } else if (unit == 'mg') {
            calculatedValue = (packing_configuration[j].qty * splits[k].oder_qty) / 1000000;
          } else {
            let orderQty = parseFloat(splits[k].oder_qty || 0);
            let requiredQty = parseFloat(packing_configuration[j].required_qty || 0);
            let configOrderQty = parseFloat(this.pendingpo[i]['order_qty']);
            calculatedValue = (orderQty * requiredQty) / configOrderQty;
          }

          packing_configuration[j]['splistCal'].push({
            month: splits[k].month,
            year: splits[k].year,
            calculatedValue
          });
          calculatedValueTotal += calculatedValue;
        }

        packing_configuration[j].required_qty = calculatedValueTotal;
      }
    }

    this.commonMaterials();
  });
}
commonMaterials() {
  console.log('function called');

  // ✅ Initialize all merged arrays
  this.mergedRawMaterials = [];
  this.mergedPackingMaterials = [];
  this.mergedConsumablesPackingMaterials = [];
  this.mergedprimaryPackingMaterials = [];

  // ✅ Initialize unique/common arrays
  this.commonRawMaterials = [];
  this.uniqueRawMaterials = [];
  this.commonPackMaterials = [];
  this.uniquePackMaterials = [];
  this.commonprimaryPackMaterials = [];
  this.uniqueprimaryPackMaterials = [];
  this.commonConsumablesPackMaterials = [];
  this.uniqueConsumablesPackMaterials = [];

  // ✅ Collect data from pendingpo
  this.pendingpo.forEach((index) => {
    this.mergedRawMaterials.push(...(index.raw_materials || []));
    this.mergedPackingMaterials.push(...(index.packing_configuration || []));
    this.mergedConsumablesPackingMaterials.push(...(index.Consumables_packing_configuration || index.consumeableMaterial || []));
    this.mergedprimaryPackingMaterials.push(...(index.primary_packing_configuration || index.primary_pm_list || []));
  });

  console.log("Raw Materials:", this.mergedRawMaterials);
  console.log("Packing Materials:", this.mergedPackingMaterials);
  console.log("Consumables:", this.mergedConsumablesPackingMaterials);
  console.log("Primary Packing:", this.mergedprimaryPackingMaterials);

  // ---------------- Consumables ----------------
  const materialMapConsumables = new Map();

  this.mergedConsumablesPackingMaterials.forEach(material => {
    if (materialMapConsumables.has(material.material_code)) {
      const existingMaterial = materialMapConsumables.get(material.material_code);
      const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
      existingMaterial.required_qty = Number(updatedQty);

      // Merge splistCal values by month and year
      const monthYearMap = new Map();
      [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
        const key = `${entry.month}-${entry.year}`;
        if (monthYearMap.has(key)) {
          monthYearMap.get(key).calculatedValue += entry.calculatedValue;
        } else {
          monthYearMap.set(key, { ...entry });
        }
      });
      existingMaterial.splistCal = Array.from(monthYearMap.values());

      materialMapConsumables.set(material.material_code, existingMaterial);
    } else {
      material.required_qty = Number(Number(material.required_qty).toFixed(4));
      materialMapConsumables.set(material.material_code, { ...material });
    }
  });

  materialMapConsumables.forEach((material, code) => {
    const occurrences = this.mergedConsumablesPackingMaterials.filter(m => m.material_code === code).length;
    if (occurrences > 1) {
      this.commonConsumablesPackMaterials.push(material);
    } else {
      this.uniqueConsumablesPackMaterials.push(material);
    }
  });

  // ---------------- Primary Packing ----------------
  const materialMapPrimary = new Map();

  this.mergedprimaryPackingMaterials.forEach(material => {
    if (materialMapPrimary.has(material.material_code)) {
      const existingMaterial = materialMapPrimary.get(material.material_code);
      const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
      existingMaterial.required_qty = Number(updatedQty);

      // Merge splistCal
      const monthYearMap = new Map();
      [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
        const key = `${entry.month}-${entry.year}`;
        if (monthYearMap.has(key)) {
          monthYearMap.get(key).calculatedValue += entry.calculatedValue;
        } else {
          monthYearMap.set(key, { ...entry });
        }
      });
      existingMaterial.splistCal = Array.from(monthYearMap.values());

      materialMapPrimary.set(material.material_code, existingMaterial);
    } else {
      material.required_qty = Number(Number(material.required_qty).toFixed(4));
      materialMapPrimary.set(material.material_code, { ...material });
    }
  });

  materialMapPrimary.forEach((material, code) => {
    const occurrences = this.mergedprimaryPackingMaterials.filter(m => m.material_code === code).length;
    if (occurrences > 1) {
      this.commonprimaryPackMaterials.push(material);
    } else {
      this.uniqueprimaryPackMaterials.push(material);
    }
  });

  // ---------------- Raw Materials ----------------
  const materialMapraw = new Map();

  this.mergedRawMaterials.forEach(material => {
    if (materialMapraw.has(material.material_code)) {
      const existingMaterial = materialMapraw.get(material.material_code);
      const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
      existingMaterial.required_qty = Number(updatedQty);

      // Merge splistCal
      const monthYearMap = new Map();
      [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
        const key = `${entry.month}-${entry.year}`;
        if (monthYearMap.has(key)) {
          monthYearMap.get(key).calculatedValue += entry.calculatedValue;
        } else {
          monthYearMap.set(key, { ...entry });
        }
      });
      existingMaterial.splistCal = Array.from(monthYearMap.values());

      materialMapraw.set(material.material_code, existingMaterial);
    } else {
      material.required_qty = Number(Number(material.required_qty).toFixed(4));
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

  // ---------------- Packing Materials ----------------
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

  this.isView = true;

  console.log('function 2 called')
  this.sendForIndent();
}




  // commonMaterials() {
  //   console.log('function called')
  //   this.mergedRawMaterials = [];
  //   this.mergedPackingMaterials = [];

  //   this.commonRawMaterials = [];
  //   this.uniqueRawMaterials = [];
  //   this.commonPackMaterials = [];
  //   this.uniquePackMaterials = [];
  //   this.commonprimaryPackMaterials = [];
  //   this.uniqueprimaryPackMaterials = [];
  //   this.commonConsumablesPackMaterials = [];
  //   this.uniqueConsumablesPackMaterials = [];

  //   this.pendingpo.forEach((index) => {
  //     this.mergedRawMaterials.push(...index.raw_materials);
  //     this.mergedPackingMaterials.push(...index.packing_configuration);
  //     this.mergedConsumablesPackingMaterials.push(...index.Consumables_packing_configuration);
  //   });

  //   console.log(this.mergedRawMaterials);
  //   console.log(this.mergedPackingMaterials);
  //   console.log(this.mergedConsumablesPackingMaterials);

  //   const materialMapConsumables = new Map();

  //   this.mergedConsumablesPackingMaterials.forEach(material => {
  //     if (materialMapConsumables.has(material.material_code)) {
  //       const existingMaterial = materialMapConsumables.get(material.material_code);
  //       const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
  //       existingMaterial.required_qty = Number(updatedQty);

  //       // Merge splistCal values by month and year
  //       const monthYearMap = new Map();

  //       [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
  //         const key = `${entry.month}-${entry.year}`;
  //         if (monthYearMap.has(key)) {
  //           monthYearMap.get(key).calculatedValue += entry.calculatedValue;
  //         } else {
  //           monthYearMap.set(key, {
  //             month: entry.month,
  //             year: entry.year,
  //             calculatedValue: entry.calculatedValue
  //           });
  //         }
  //       });

  //       existingMaterial.splistCal = Array.from(monthYearMap.values());

  //       materialMapConsumables.set(material.material_code, existingMaterial);
  //     } else {
  //       material.required_qty = Number(Number(material.required_qty).toFixed(4));
  //       materialMapConsumables.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapConsumables.forEach((material, code) => {
  //     const occurrences = this.mergedConsumablesPackingMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonConsumablesPackMaterials.push(material);
  //     } else {
  //       this.uniqueConsumablesPackMaterials.push(material);
  //     }
  //   });














  //   const materialMapPrimary = new Map();

  //   this.mergedprimaryPackingMaterials.forEach(material => {
  //     if (materialMapPrimary.has(material.material_code)) {
  //       const existingMaterial = materialMapPrimary.get(material.material_code);
  //       const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
  //       existingMaterial.required_qty = Number(updatedQty);

  //       // Merge splistCal values by month and year
  //       const monthYearMap = new Map();

  //       [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
  //         const key = `${entry.month}-${entry.year}`;
  //         if (monthYearMap.has(key)) {
  //           monthYearMap.get(key).calculatedValue += entry.calculatedValue;
  //         } else {
  //           monthYearMap.set(key, {
  //             month: entry.month,
  //             year: entry.year,
  //             calculatedValue: entry.calculatedValue
  //           });
  //         }
  //       });

  //       existingMaterial.splistCal = Array.from(monthYearMap.values());

  //       materialMapPrimary.set(material.material_code, existingMaterial);
  //     } else {
  //       material.required_qty = Number(Number(material.required_qty).toFixed(4));
  //       materialMapPrimary.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapPrimary.forEach((material, code) => {
  //     const occurrences = this.mergedprimaryPackingMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonprimaryPackMaterials.push(material);
  //     } else {
  //       this.uniqueprimaryPackMaterials.push(material);
  //     }
  //   });












  //   const materialMapraw = new Map();

  //   this.mergedRawMaterials.forEach(material => {
  //     if (materialMapraw.has(material.material_code)) {
  //       const existingMaterial = materialMapraw.get(material.material_code);
  //       const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
  //       existingMaterial.required_qty = Number(updatedQty);

  //       // Merge splistCal values by month and year
  //       const monthYearMap = new Map();

  //       [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
  //         const key = `${entry.month}-${entry.year}`;
  //         if (monthYearMap.has(key)) {
  //           monthYearMap.get(key).calculatedValue += entry.calculatedValue;
  //         } else {
  //           monthYearMap.set(key, {
  //             month: entry.month,
  //             year: entry.year,
  //             calculatedValue: entry.calculatedValue
  //           });
  //         }
  //       });

  //       existingMaterial.splistCal = Array.from(monthYearMap.values());

  //       materialMapraw.set(material.material_code, existingMaterial);
  //     } else {
  //       material.required_qty = Number(Number(material.required_qty).toFixed(4));
  //       materialMapraw.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapraw.forEach((material, code) => {
  //     const occurrences = this.mergedRawMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonRawMaterials.push(material);
  //     } else {
  //       this.uniqueRawMaterials.push(material);
  //     }
  //   });

  //   const materialMapPack = new Map();

  //   this.mergedPackingMaterials.forEach(material => {
  //     if (materialMapPack.has(material.material_code)) {
  //       const existingMaterial = materialMapPack.get(material.material_code);
  //       existingMaterial.required_qty = Number(existingMaterial.required_qty) + Number(material.required_qty);
  //       materialMapPack.set(material.material_code, existingMaterial);
  //     } else {
  //       materialMapPack.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapPack.forEach((material, code) => {
  //     const occurrences = this.mergedPackingMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonPackMaterials.push(material);
  //     } else {
  //       this.uniquePackMaterials.push(material);
  //     }
  //   });
 

  //   this.isView = true;
  //   this.sendForIndent();
  // }

  // getPOsLog(){
  //   this.service.get('marketing/po.php?type=getInprocessReqAnalysis').subscribe(response =>{
  //     this.pendingpo =response;
  //     for (let i = 0; i < this.pendingpo.length; i++) {

  //       let primary_pm_list = this.pendingpo[i].primary_pm_list;
  //       let raw_materials = this.pendingpo[i].raw_materials;
  //       let packing_configuration = this.pendingpo[i].packing_configuration;
  //       let splits = this.pendingpo[i].splits;
  //       if(raw_materials.length == 0 || raw_materials==null || raw_materials == undefined || primary_pm_list.length == 0 || primary_pm_list==null || primary_pm_list == undefined ||packing_configuration.length == 0 || packing_configuration==null || packing_configuration == undefined ){
  //       raw_materials = [];
  //       packing_configuration = [];
  //       primary_pm_list = [];
  //       }

  //       // Extract month and year from the current pendingpo
  //       let month = this.pendingpo[i].month;
  //       let year = this.pendingpo[i].year;

  //       for (let j = 0; j < raw_materials.length; j++) {
  //         // Initialize splistCal as an empty array for each raw_material
  //         raw_materials[j]['splistCal'] = [];
  //         let calculatedValueTotal = 0;

  //         // Loop through the splits for the current raw material
  //         for (let k = 0; k < splits.length; k++) {
  //           // Check if split entry exists (important for safety)
  //           if (splits[k]) {
  //             // Calculate the value
  //             let unit =raw_materials[j].unit;
  //             if (unit == 'gm') {
  //               let calculatedValue = (raw_materials[j].qty * splits[k].oder_qty) / 1000;
  //               raw_materials[j]['splistCal'].push({
  //                 month: splits[k].month,
  //                 year:  splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }else if (unit == 'mg') {
  //             let calculatedValue = (raw_materials[j].qty * splits[k].oder_qty) / 1000000;
  //             raw_materials[j]['splistCal'].push({
  //               month: splits[k].month,
  //               year: splits[k].year,
  //               calculatedValue: calculatedValue
  //             });
  //                              calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             else{
  //               let calculatedValue = (raw_materials[j].qty * splits[k].oder_qty);
  //               raw_materials[j]['splistCal'].push({
  //                 month:splits[k].month,
  //                 year: splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             // Push the calculated value along with month and year into the splistCal array

  //           }
  //         }


  //           raw_materials[j].required_qty = calculatedValueTotal;
  //           calculatedValueTotal = 0;

  //         // Console log the splistCal array for the current raw_material
       
  //       }
  //    for (let j = 0; j < primary_pm_list.length; j++) {
  //         // Initialize splistCal as an empty array for each raw_material
  //         primary_pm_list[j]['splistCal'] = [];
  //         let calculatedValueTotal = 0;

  //         // Loop through the splits for the current raw material
  //         for (let k = 0; k < splits.length; k++) {
  //           // Check if split entry exists (important for safety)
  //           if (splits[k]) {
  //             // Calculate the value
  //             let unit =primary_pm_list[j].unit_name;
  //             if (unit == 'gm') {
  //               let calculatedValue = (primary_pm_list[j].qty * splits[k].oder_qty) / 1000;
  //               primary_pm_list[j]['splistCal'].push({
  //                 month: splits[k].month,
  //                 year:  splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }else if (unit == 'mg') {
  //             let calculatedValue = (primary_pm_list[j].qty * splits[k].oder_qty) / 1000000;
  //             primary_pm_list[j]['splistCal'].push({
  //               month: splits[k].month,
  //               year: splits[k].year,
  //               calculatedValue: calculatedValue
  //             });
  //                              calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             else{
  //               // let calculatedValue = (primary_pm_list[j].qty * splits[k].oder_qty);
  //               let orderQty = parseFloat(splits[k].oder_qty || 0);
  //               let requiredQty = parseFloat(primary_pm_list[j].required_qty || 0);
  //               let configOrderQty = parseFloat(this.pendingpo[i]['order_qty']);

  //               let calculatedValue = (orderQty * requiredQty) / configOrderQty;

  //               primary_pm_list[j]['splistCal'].push({
  //                 month:splits[k].month,
  //                 year: splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             // Push the calculated value along with month and year into the splistCal array

  //           }
  //         }


  //           primary_pm_list[j].required_qty = calculatedValueTotal;
  //           calculatedValueTotal = 0;

  //         // Console log the splistCal array for the current raw_material
  //        }

        
      
      
  //        for (let j = 0; j < packing_configuration.length; j++) {
  //         // Initialize splistCal as an empty array for each raw_material
  //         packing_configuration[j]['splistCal'] = [];
  //         let calculatedValueTotal = 0;

  //         // Loop through the splits for the current raw material
  //         for (let k = 0; k < splits.length; k++) {
  //           // Check if split entry exists (important for safety)
  //           if (splits[k]) {
  //             // Calculate the value
  //             let unit =packing_configuration[j].unit;
  //             if (unit == 'gm') {
  //               let calculatedValue = (packing_configuration[j].qty * splits[k].oder_qty) / 1000;
  //               packing_configuration[j]['splistCal'].push({
  //                 month: splits[k].month,
  //                 year:  splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }else if (unit == 'mg') {
  //             let calculatedValue = (packing_configuration[j].qty * splits[k].oder_qty) / 1000000;
  //             packing_configuration[j]['splistCal'].push({
  //               month: splits[k].month,
  //               year: splits[k].year,
  //               calculatedValue: calculatedValue
  //             });
  //                              calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             else{
  //               // let calculatedValue = (packing_configuration[j].qty * splits[k].oder_qty);
  //               let orderQty = parseFloat(splits[k].oder_qty || 0);
  //               let requiredQty = parseFloat(packing_configuration[j].required_qty || 0);
  //               let configOrderQty = parseFloat(this.pendingpo[i]['order_qty']);

  //               let calculatedValue = (orderQty * requiredQty) / configOrderQty;

  //               packing_configuration[j]['splistCal'].push({
  //                 month:splits[k].month,
  //                 year: splits[k].year,
  //                 calculatedValue: calculatedValue
  //               });
  //                                calculatedValueTotal = +calculatedValueTotal + +calculatedValue;

  //             }
  //             // Push the calculated value along with month and year into the splistCal array

  //           }
  //         }


  //           packing_configuration[j].required_qty = calculatedValueTotal;
  //           calculatedValueTotal = 0;

  //         // Console log the splistCal array for the current raw_material
  //        }
  //     }
      

  //     this.commonMaterials();
  //   });

  // }



  getManufactures() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.vendors = response;
    });
  }
  storeEmployees
    getStoreEmployee() {
    this.service.get('common.php?type=getStoreEmployee').subscribe(response => {
      this.storeEmployees = response;
    });
  }


  mergedRawMaterials =[];
  mergedPackingMaterials =[];
  mergedprimaryPackingMaterials =[];
  mergedConsumablesPackingMaterials=[];

   commonRawMaterials = [];
   uniqueRawMaterials = [];
   commonPackMaterials = [];
   mergedArray:any = [];
   uniquePackMaterials = [];
   commonprimaryPackMaterials = [];
uniqueprimaryPackMaterials = [];
commonConsumablesPackMaterials = [];
uniqueConsumablesPackMaterials = [];

  // commonMaterials() {
  //   this.mergedRawMaterials = [];
  //   this.mergedPackingMaterials = [];
  //   this.mergedprimaryPackingMaterials = [];

  //   this.commonRawMaterials = [];
  //   this.uniqueRawMaterials = [];
  //   this.commonPackMaterials = [];
  //   this.uniquePackMaterials = [];
  //   this.commonprimaryPackMaterials = [];
  //   this.uniqueprimaryPackMaterials = [];

  //   this.pendingpo.forEach((index) => {
  //     this.mergedRawMaterials.push(...index.raw_materials);
  //     this.mergedPackingMaterials.push(...index.packing_configuration);
  //   });

  //   console.log(this.mergedRawMaterials);
  //   console.log(this.mergedPackingMaterials);

  //   const materialMapraw = new Map();

  //   this.mergedRawMaterials.forEach(material => {
  //     if (materialMapraw.has(material.material_code)) {
  //       const existingMaterial = materialMapraw.get(material.material_code);
  //       const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
  //       existingMaterial.required_qty = Number(updatedQty);

  //       // Merge splistCal values by month and year
  //       const monthYearMap = new Map();

  //       [...existingMaterial.splistCal || [], ...material.splistCal || []].forEach(entry => {
  //         const key = `${entry.month}-${entry.year}`;
  //         if (monthYearMap.has(key)) {
  //           monthYearMap.get(key).calculatedValue += entry.calculatedValue;
  //         } else {
  //           monthYearMap.set(key, {
  //             month: entry.month,
  //             year: entry.year,
  //             calculatedValue: entry.calculatedValue
  //           });
  //         }
  //       });

  //       existingMaterial.splistCal = Array.from(monthYearMap.values());

  //       materialMapraw.set(material.material_code, existingMaterial);
  //     } else {
  //       material.required_qty = Number(Number(material.required_qty).toFixed(4));
  //       materialMapraw.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapraw.forEach((material, code) => {
  //     const occurrences = this.mergedRawMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonRawMaterials.push(material);
  //     } else {
  //       this.uniqueRawMaterials.push(material);
  //     }
  //   });

  //   const materialMapPack = new Map();

  //   this.mergedPackingMaterials.forEach(material => {
  //     if (materialMapPack.has(material.material_code)) {
  //       const existingMaterial = materialMapPack.get(material.material_code);
  //       existingMaterial.required_qty = Number(existingMaterial.required_qty) + Number(material.required_qty);
  //       materialMapPack.set(material.material_code, existingMaterial);
  //     } else {
  //       materialMapPack.set(material.material_code, { ...material });
  //     }
  //   });

  //   materialMapPack.forEach((material, code) => {
  //     const occurrences = this.mergedPackingMaterials.filter(m => m.material_code === code).length;
  //     if (occurrences > 1) {
  //       this.commonPackMaterials.push(material);
  //     } else {
  //       this.uniquePackMaterials.push(material);
  //     }
  //   });
 

  //   this.isView = true;
  //   this.sendForIndent();
  // }
    
  
  

  isMerge = false;





calculateDifference1(item) {
  const balance_qty = parseFloat(item.balance_qty);
  const requiredQty = parseFloat(item.required_qty);

  if (isNaN(balance_qty) || isNaN(requiredQty)) {
    return 'NaN';
  }

  return Math.abs(balance_qty - requiredQty).toFixed(2);
}



calculateDifference(item) {
  const balance_qty = parseFloat(item.balance_qty);
  const requiredQty = parseFloat(item.required_qty);

  if (isNaN(balance_qty) || isNaN(requiredQty)) {
    return 'NaN';
  }

  return Math.max(requiredQty - balance_qty, 0).toFixed(2);
}







  mrpDataIndned;
sendForIndent() {
  console.log('Recevied For Indent:');
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

  // Define keys to extract
  const keysToExtract = [
    'unit', 'required_qty_unit', 'uom', 'min_order_qty', 'min_inventory',
    'required_qty', 'balance_qty', 'gross_balance_qty', 'booked_qty', 'hold_qty', 'net_available_qty',
    'material_name', 'material_code', 'material_type',
    'splistCal', 'vendor_code', 'moisture', 'order_no', 'product_code'
  ];

  // Merge all common & unique arrays
  const mergedArray = [
    ...extractKeyValuePairs(this.commonRawMaterials, keysToExtract),
    ...extractKeyValuePairs(this.uniqueRawMaterials, keysToExtract),
    ...extractKeyValuePairs(this.commonPackMaterials, keysToExtract),
    ...extractKeyValuePairs(this.uniquePackMaterials, keysToExtract),
    ...extractKeyValuePairs(this.commonprimaryPackMaterials, keysToExtract),
    ...extractKeyValuePairs(this.uniqueprimaryPackMaterials, keysToExtract),
    ...extractKeyValuePairs(this.commonConsumablesPackMaterials, keysToExtract),
    ...extractKeyValuePairs(this.uniqueConsumablesPackMaterials, keysToExtract)
  ];

  // Filter only shortage items & calculate required_qty1
  this.mergedArray = mergedArray
    .filter(item => Number(item.balance_qty || 0) < Number(item.required_qty || 0))
    .map(item => {
      const balance = Number(item.balance_qty || 0);
      const required = Number(item.required_qty || 0);
      item.required_qty1 = Math.max(required - balance, 0); // ✅ never negative
      return item;
    });

  // ✅ Use actual max length function
  this.maxLength = this.getMaxSplistCalLength();
  console.log('Biggest splistCal length:', this.maxLength);

  console.log('Final merged array before API:', this.mergedArray);

  this.filtersFO = this.pendingpo
    .filter(pending => pending.pid)
    .map(({ pid, order_no, product_code }) => ({ pid, order_no, product_code }));

  // API call
  this.service.post('purchase/autopurchase.php?type=getDataMrpIndend', this.mergedArray)
    .subscribe(response => {
      this.mergedArray = Array.isArray(response) ? response : [];
      const orderNos = [...new Set(this.filtersFO.map((f) => f.order_no).filter(Boolean))].join(', ');
      const productCodes = [...new Set(this.filtersFO.map((f) => f.product_code).filter(Boolean))].join(', ');
      this.mergedArray.forEach((item) => {
        item.order_no = orderNos;
        item.product_code = productCodes;
      });
      console.log('After API update:', this.mergedArray);
    });

  this.isMerge = true;
  this.isView = false;
}

  maxLength: number = 0;
  getMaxSplistCalLength(): number {
    let max = 0;
    this.mergedArray.forEach(item => {
      if (item.splistCal && item.splistCal.length > max) {
        max = item.splistCal.length;
      }
    });
    return max;
  }

  // getMonthYearColumns() {
  //   let monthYearList = [];
  //   try {
  //     if (Array.isArray(this.mergedArray)) {
  //       this.mergedArray.forEach(item => {
  //         if (Array.isArray(item.splistCal)) {
  //           item.splistCal.forEach(entry => {
  //             if (entry && entry.month && entry.year !== undefined) {
  //               if (!monthYearList.some(e => e.month === entry.month && e.year === entry.year)) {
  //                 monthYearList.push({ month: entry.month, year: entry.year, calculatedValue: entry.calculatedValue });
  //               }
  //             }
  //           });
  //         }
  //       });
  //     }
  //   } catch (error) {
  //     console.error('Error while processing mergedArray or splistCal:', error);
  //   }
  //   return monthYearList;
  // }



  filtersFO;

  delIndent(index){
    this.mergedArray.splice(index,1);
  }



  calculateSum(value,index){

      this.mergedArray[index]['orderBatch'] = Math.ceil(this.mergedArray[index]['sum'] / value);
      let OrderQtyy=this.mergedArray[index]['orderBatch'] * value;
console.log('OrderQtyy :>> ', OrderQtyy);
if(this.material_type!='Packing Material'){
  this.mergedArray[index]['totalOrderQty'] = Number(OrderQtyy)+Number(OrderQtyy * this.mergedArray[index]['moisture'] / 100);
}else{
  this.mergedArray[index]['totalOrderQty'] = Number(OrderQtyy);

}
      // this.mergedArray[index]['totalOrderQty'] = this.mergedArray[index]['orderBatch'] * value;

    this.LatestmergedArray=  this.mergedArray.filter(item => item.sum !== undefined && item.sum !== 0 && item.totalOrderQty > item.balance_qty);
  }

  btn = true;
  save(){
console.log('this.LatestmergedArray :>> ', this.LatestmergedArray);
    this.btn = false;

    if (this.LatestmergedArray.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    console.log(this.LatestmergedArray);
    this.service.post('purchase/autopurchase.php?type=autoPurchaseSaveIndent', JSON.stringify(this.LatestmergedArray)).subscribe(response => {
      if (response['status'] == 'success') {
        this.mergedArray = [];
        // Marks the order lines Complete so they leave the requirement-analysis queue.
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
        alertify.success('Purchase Requisition records saved successfully');
         this.getPOsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

material_type;

  toggleMainRowCheckbox(item: any, checked: boolean,material_type): void {
    item.check = checked;
this.material_type=material_type
    // When the row is checked, update the sum of all month cards that are checked
    if (checked) {
      item.sum = this.calculateSumForRow(item);
    } else {
      // If unchecked, reset the sum or subtract based on your requirement
      item.sum = 0;
      // Also uncheck all month cards if the row checkbox is unchecked
      item.splistCal.forEach((monthItem: any) => {
        monthItem.check = false;
      });
    }

    // Log the current sum for the item
    console.log(`Sum for item at index: ${item.index} is ${item.sum}`);

    // Update the checkedItems array
    this.updateCheckedItems();
  }


// Define array at component level
checkedMonthCards: any[] = [];

toggleMonthCardCheckbox(item: any, idx: number): void {
  const selectedMonthCard = item.splistCal[idx];

  if (selectedMonthCard.check) {
    // Add the calculated value to sum if the month card is checked
    item.sum += selectedMonthCard.calculatedValue || 0;

    // ✅ Push selected month card into array
    this.checkedMonthCards.push(selectedMonthCard);
  } else {
    // Subtract the calculated value from sum if the month card is unchecked
    item.sum -= selectedMonthCard.calculatedValue || 0;

    // ✅ Remove unchecked card from array
    this.checkedMonthCards = this.checkedMonthCards.filter(
      card => card !== selectedMonthCard
    );
  }

  // Log the updated sum for the item
  console.log(`Updated sum for item at index: ${item.index} is ${item.sum}`);
  console.log('selectedMonthCard:', selectedMonthCard);

  // ✅ Log all selected month cards
  console.log('All selected month cards:', this.checkedMonthCards);

  // Update the checkedItems array
  this.updateCheckedItems();
}




  // Function to calculate the sum of the 'calculatedValue' of selected month cards for the row
  LatestmergedArray: any[] = [];
  calculateSumForRow(item: any): number {
    let total = 0;
    if (item.splistCal && item.splistCal.length) {
      item.splistCal.forEach((monthItem: any) => {
        if (monthItem.check) {
          total += monthItem.calculatedValue || 0; // Add calculated value if the card is checked
        }
      });
    }
    return total;
  }

  checkedItems: any[] = []; // Array to hold checked items
  // Function to update the array of checked items based on the main row and month card checkboxes
  updateCheckedItems(): void {
    this.checkedItems = [];

    // Loop through each item in mergedArray and check for selected items
    this.mergedArray.forEach(item => {
      if (item.check && item.sum != 0) {
        // Add the item (row) to checkedItems array if the main checkbox is checked and the sum is non-zero
        this.checkedItems.push(item);
      }

      // Loop through each month card and check if any are selected
      if (item.splistCal && item.splistCal.length) {
        item.splistCal.forEach(monthItem => {
          if (monthItem.check) {
            // Add the month item to the checkedItems array if it's selected
            this.checkedItems.push(monthItem);
          }
        });
      }
    });

    // Log the checkedItems array
    console.log('mergedArray Items:', this.mergedArray);
    console.log('Checked Items:', this.checkedItems);

    // Filter out items where sum is 0
   // Assuming this.mergedArray is the original array
let filteredArray = this.mergedArray.filter(item => item.sum !== undefined && item.sum !== 0 && item.totalOrderQty > item.balance_qty);

console.log('Checked filteredArray:',filteredArray);
this.LatestmergedArray = filteredArray;

  }


  delIndent1(index: number): void {
    this.mergedArray.splice(index, 1);
  }




















































setNoOfBatches() {
  for (let i = 0; i < this.pendingpos.length; i++) {
    const item = this.pendingpos[i];

    item.planned_qty = item.order_qty
      let bqty = Number(item.planned_qty) / Number(item.bfr_batch_size);

      if (bqty > 0) {
        item.number_of_batches = Math.round(bqty);
      } else {
        item.number_of_batches = 0;
      }

      this.calculateBatchQty(item);
    

    // ✅ Option 1: See as object
    // console.log('item:', item);

    // ✅ Option 2: Pretty JSON string
    // console.log('item JSON=', JSON.stringify(item, null, 2));
  }
}


groupedMaterials: any = {};

calculateBatchQty(item: any) {
  let possible_batches: number[] = [];

  const calculateMaterialPlan = (
    material: any,
    batchQty: number,
    stock: number,
    numberOfBatches: number,
    planKey: string = 'plan_qty'
  ) => {
    console.log('-----------------------------');
    console.log('🔍 Material:', material.material_code || material);
    console.log('Qty2:', material.Qty2);
    console.log('item.bfr_batch_size:', item.bfr_batch_size);
    console.log('item.batch_weight:', item.batch_weight);
    console.log('Computed batchQty:', batchQty);
    console.log('stock (avbl_stock):', stock);
    console.log('numberOfBatches:', numberOfBatches);

    if (!batchQty || isNaN(batchQty) || batchQty <= 0) {
      console.warn('⚠️ batchQty invalid, skipping calculation');
      material[planKey] = 0;
      material.can_plan_batches = 0;
      material.shortage_qty = 0;
      possible_batches.push(0);
      return material;
    }

    const planQty = batchQty * numberOfBatches;
    console.log('planQty:', planQty);

    material[planKey] = parseFloat(planQty.toString()).toFixed(2);

    const canPlan = stock / batchQty;
    console.log('canPlan (stock/batchQty):', canPlan);

    material.can_plan_batches = Math.floor(canPlan);
    console.log('can_plan_batches (floored):', material.can_plan_batches);

    const shortage = stock - planQty;
    console.log('shortage (stock - planQty):', shortage);

    material.shortage_qty = shortage < 0 ? Math.abs(shortage) : 0;
    console.log('shortage_qty (final):', material.shortage_qty);

    possible_batches.push(material.can_plan_batches);
    return material;
  };

  // ✅ Raw materials loop
  if (item.raw_materials && item.raw_materials.length > 0) {
    item.raw_materials = item.raw_materials.map((m: any) =>
      calculateMaterialPlan(
        m,
        Number(((m.Qty2 ?? 0) * (item.bfr_batch_size || 1)) / (item.batch_weight || 1)),
        Number(m.avbl_stock ?? 0),
        item.number_of_batches
      )
    );
  }

  // ✅ Calculate min/max possible batches
  const valid_batches = possible_batches.filter(v => !isNaN(v));

  const min = valid_batches.length > 0 ? Math.min(...valid_batches) : 0;
  const max = valid_batches.length > 0 ? Math.max(...valid_batches) : 0;

  item.lowest_batch = min < 0 ? 0 : min;
  item.max_possible_batch = max < 0 ? 0 : max;

  item.can_plan_qty = item.lowest_batch * Number(item.bfr_batch_size || 0);

  console.log('=============================');
  console.log('📦 Final Item:', item);
  console.log('Lowest possible batches:', item.lowest_batch);
  console.log('Max possible batches:', item.max_possible_batch);
}





 







  
}
