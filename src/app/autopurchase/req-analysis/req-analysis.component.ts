import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-req-analysis',
  templateUrl: './req-analysis.component.html',
  styleUrls: ['./req-analysis.component.css']
})
export class ReqAnalysisComponent implements OnInit {

  
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
      this.groupOrdersByGroupCode();
    });
  }
groupedOrders:any=[]; 

groupOrdersByGroupCode() {
  const groups: any = {};

  // Step 1: Group by groupcode
  this.pendingpo.forEach(order => {
    const key = order.groupcode || 'NoGroup';
    if (!groups[key]) {
      groups[key] = {
        mainGroupName: order.mainGroupName || 'No Group',
        groupcode: order.groupcode || '-',
        orders: []
      };
    }
    groups[key].orders.push(order);
  });

  // Step 2: For each group, group by product_code and ord_unit, then by 7-day ranges
  this.groupedOrders = Object.values(groups).map(group => {
    const products: any = {};

    group['orders'].forEach(order => {
      const code = order.product_code;
      const unit = order.ord_unit || 'NoUnit';
      const prodKey = `${code}_${unit}`; // combine code + unit for segregation

      if (!products[prodKey]) {
        products[prodKey] = {
          product_name: order.product_name,
          product_code: order.product_code,
          ord_unit: unit,
          orders: [],
          total_qty: 0,
          ordersByWeek: [] // new array for weekly grouping
        };
      }
      products[prodKey].orders.push(order);
      products[prodKey].total_qty += +order.order_qty; // sum order_qty
    });

    // Step 3: Group each product's orders by 7-day ranges
    Object.values(products).forEach(product => {
      // Sort orders by order_recivedDate
      product['orders'].sort((a, b) => new Date(a.order_recivedDate).getTime() - new Date(b.order_recivedDate).getTime());

      let weekGroup: any[] = [];
      let startDate: Date | null = null;

      product['orders'].forEach(order => {
        const orderDate = new Date(order.order_recivedDate);

        if (!startDate) {
          startDate = orderDate;
        }

        const diffDays = Math.floor((orderDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));

        if (diffDays < 7) {
          // Same 7-day period
          weekGroup.push(order);
        } else {
          // Push the previous 7-day group
          product['ordersByWeek'].push({
            startDate: startDate,
            endDate: new Date(startDate.getTime() + 6 * 24 * 60 * 60 * 1000),
            orders: weekGroup,
            total_qty: weekGroup.reduce((sum, o) => sum + +o.order_qty, 0)
          });

          // Start new week group
          startDate = orderDate;
          weekGroup = [order];
        }
      });

      // Push the last week group
      if (weekGroup.length > 0 && startDate) {
        product['ordersByWeek'].push({
          startDate: startDate,
          endDate: new Date(startDate.getTime() + 6 * 24 * 60 * 60 * 1000),
          orders: weekGroup,
          total_qty: weekGroup.reduce((sum, o) => sum + +o.order_qty, 0)
        });
      }
    });

    group['ordersByProduct'] = Object.values(products);
    return group;
    console.log(this.groupedOrders);
  });
  console.log(this.groupedOrders);
}









selectedProducts: any[] = [];

isProductSelected(product: any): boolean {
  return this.selectedProducts.some(p => p.product_code === product.product_code);
}

toggleProductSelection(product: any, event: any) {
  if (event.target.checked) {
    this.selectedProducts.push(product);
  } else {
    this.selectedProducts = this.selectedProducts.filter(
      p => p.product_code !== product.product_code
    );
  }

  console.log("Selected Products:", this.selectedProducts);
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
          const updatedQty = (Number(existingMaterial.required_qty) + Number(material.required_qty)).toFixed(4);
          existingMaterial.required_qty = Number(updatedQty);
          materialMapraw.set(material.material_code, existingMaterial);
        } else {
          // Ensure the initial required_qty is also formatted to 4 decimal places
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
 




  calculateDifference1(item) {
    const minOrderQty = parseFloat(item.min_order_qty);
    const requiredQty = parseFloat(item.required_qty);

    if (isNaN(minOrderQty) || isNaN(requiredQty)) {
      return 'NaN';
    }

    return (minOrderQty - requiredQty).toFixed(2);
  }

  calculateDifference(item) {
    const balance_qty = parseFloat(item.balance_qty);
    const requiredQty = parseFloat(item.required_qty);

    if (isNaN(balance_qty) || isNaN(requiredQty)) {
      return 'NaN';
    }

    return (balance_qty - requiredQty).toFixed(2);
  }







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
    const keysToExtract = ['unit','required_qty_unit','uom','min_order_qty','min_inventory', 'required_qty', 'balance_qty','material_name', 'material_code', 'material_type']; // Replace 'key1', 'key2' with your actual keys
    
    // Extract key-value pairs and merge the arrays
    const mergedArray = [
      ...extractKeyValuePairs(this.commonRawMaterials, keysToExtract),
      ...extractKeyValuePairs(this.uniqueRawMaterials, keysToExtract),
      ...extractKeyValuePairs(this.commonPackMaterials, keysToExtract),
      ...extractKeyValuePairs(this.uniquePackMaterials, keysToExtract)
    ];




    //this.mergedArray = mergedArray.filter(item => Number(item.balance_qty) < Number(item.required_qty));

    this.mergedArray = mergedArray
  .filter(item => Number(item.balance_qty) < Number(item.required_qty))
  .map(item => {
    item.required_qty1 =  Number(item.required_qty) - Number(item.balance_qty);  
    return item;
  });

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
        alertify.success('Purchase Requisition records saved successfully');
         this.getPOsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

  

planned_qty:any
  isPlan=false; 
products:any = [];

bfr_list = [];

mfr_list = [];

mfr_batch_size = 0;
bfr_batch_size = 0;
  bfr_batch_size_unit
  Pack_Sizess=[];
  raw_materials=[];
  packing_materials=[];
number_of_batches=0;
can_plan_qty=0;
lowest_batch=0
unit_formula_id
  ord_unit='';

// plan(data, product,unit) {
//   console.log("data",unit);
//   console.log("data", data);
//   console.log("Planning initiated for", product);
//   this.isPlan = true;
//   this.planned_qty = Number(data['total_qty']);
//   this.ord_unit = unit;

//   this.service.get(
//     'purchase/autopurchase.php?type=get_products_formulation_shortage_calculation&product_code=' + product
//   ).subscribe(response => {
//       this.products = response;
//       if (!this.products.length) {
//         alert("No formulation found for this product!");
//         return;
//       }

//       // Step 1: Get MFR List
//       this.mfr_list = this.products[0]['mfr_records'] || [];
//       if (!this.mfr_list.length) {
//         alert("No MFR found for this product!");
//         return;
//       }

//       // Step 2: Collect valid BFRs matching order unit & sufficient stock
//       let validBatchSizes = [];

//       for (const mfr of this.mfr_list) {
//         const bfrList = mfr['bfr_records'] || [];
//         for (const bfr of bfrList) {
//           if (bfr['uni'] !== this.ord_unit) continue; // skip if unit mismatch

//           const packList = bfr['Packs'] || [];
//           const batchSize = Number(bfr['batch_formula_weight']);
//           if (!batchSize || packList.length === 0) continue;

//           const pack = packList[0];

//           // Check if all raw & packing materials can cover planned_qty
//           const numBatches = this.planned_qty / batchSize;
//           const rawMaterials = pack['raw_materials'] || [];
//           const packingMaterials = pack['packing_materials'] || [];

//           let canPlan = true;

//           for (const rm of rawMaterials) {
//             if ((rm.avbl_stock || 0) < rm.batch_qty * numBatches) {
//               canPlan = false;
//               break;
//             }
//           }

//           if (canPlan) {
//             for (const pm of packingMaterials) {
//               if ((pm.avbl_stock || 0) < pm.batch_qty * numBatches) {
//                 canPlan = false;
//                 break;
//               }
//             }
//           }

//           if (canPlan) {
//             validBatchSizes.push({ mfr, bfr, pack, batchSize, numBatches });
//           }
//         }
//       }

//       // if (validBatchSizes.length === 0) {
//       //   alert(`No BFR found matching unit "${this.ord_unit}" with sufficient stock to plan.`);
//       //   return;
//       // }

//       // Step 3: Pick closest batch size to planned quantity
//       validBatchSizes.sort((a, b) => Math.abs(this.planned_qty - a.batchSize) - Math.abs(this.planned_qty - b.batchSize));
//       const bestOption = validBatchSizes[0];

//       // Step 4: Apply selected best option
//       this.mfr_batch_size = bestOption.batchSize;
//       this.number_of_batches = Math.round(bestOption.numBatches);
//       this.bfr_batch_size = bestOption.batchSize;
//       this.bfr_batch_size_unit = bestOption.bfr['uni'];
//       this.unit_formula_id = bestOption.bfr['id'];

//       this.Pack_Sizess = [bestOption.pack];
//       this.raw_materials = bestOption.pack['raw_materials'] || [];
//       this.packing_materials = bestOption.pack['packing_materials'] || [];

//       // Step 5: Calculate quantities + shortages
//       this.calculateBatchQty();

//       console.log("✅ Auto-selected formula:", {
//         batchSize: bestOption.batchSize,
//         batches: this.number_of_batches,
//         selected_mfr: bestOption.mfr['mfr_no'],
//         selected_bfr: bestOption.bfr['bfr_no'],
//         pack_size: bestOption.pack['pack_sizes'],
//         bfr_unit: bestOption.bfr['uni']
//       });
//   });
// }



// calculateBatchQty() {
//     if (!this.raw_materials || !this.packing_materials) return;

//     let minBatchArr: number[] = [];

//     // ---- RAW MATERIALS ----
//     for (let i = 0; i < this.raw_materials.length; i++) {
//         const rm = this.raw_materials[i];
//         const planQty = Number(rm['batch_qty']) * Number(this.number_of_batches);
//         rm['plan_qty'] = parseFloat(planQty.toFixed(3)); // 3 decimal precision

//         // Calculate max batches possible based on stock
//         const maxBatches = Number(rm['avbl_stock']) / Number(rm['batch_qty']);
//         rm['can_plan_batches'] = Math.floor(maxBatches); // floor instead of round for safe planning

//         // Shortage
//         rm['shortage_qty'] = parseFloat((Number(rm['avbl_stock']) - planQty).toFixed(3));

//         minBatchArr.push(rm['can_plan_batches']);
//     }

//     // ---- PACKING MATERIALS ----
//     for (let i = 0; i < this.packing_materials.length; i++) {
//         const pm = this.packing_materials[i];
//         const planQty = Number(pm['batch_qty']) * Number(this.number_of_batches);
//         pm['plan'] = parseFloat(planQty.toFixed(3));

//         pm['short_qt'] = parseFloat((Number(pm['avbl_stock']) - planQty).toFixed(3));

//         // Include packing material in minBatch calculation if needed
//         const maxBatches = Number(pm['avbl_stock']) / Number(pm['batch_qty']);
//         minBatchArr.push(Math.floor(maxBatches));
//     }

//     // ---- FINAL CALCULATION ----
//     const minBatches = Math.min(...minBatchArr);
//     this.lowest_batch = minBatches < 0 ? 0 : minBatches;
//     this.can_plan_qty = this.lowest_batch * this.bfr_batch_size;

//     console.log("Calculated batch quantities:", {
//         lowest_batch: this.lowest_batch,
//         can_plan_qty: this.can_plan_qty
//     });
// }

 

// Component variables
showShortageModal: boolean = false;
shortageMaterials: any[] = [];

// Component properties
 
batches: any[] = [];
summaryRawMaterials: any[] = [];
summaryPackingMaterials: any[] = [];



 
remainingQtyToPlan: number = 0;
lowestBatchSize: number = 0;
 

plan(data: any, product: string, unit: string) {
  console.log("Planning initiated for", product, "Unit:", unit);
  this.isPlan = true;
  let remainingQty = Number(data['total_qty']);
  this.remainingQtyToPlan = remainingQty;
  this.ord_unit = unit;

  this.service.get(
    'purchase/autopurchase.php?type=get_products_formulation_shortage_calculation&product_code=' + product
  ).subscribe(response => {
    this.products = response;
    if (!this.products.length) return alert("No formulation found!");
    this.mfr_list = this.products[0]['mfr_records'] || [];
    if (!this.mfr_list.length) return alert("No MFR found!");

    // Collect all unique batch sizes descending
    let batchSizes: number[] = [];
    for (const mfr of this.mfr_list) {
      for (const bfr of mfr.bfr_records || []) {
        if (bfr.uni.toLowerCase() !== this.ord_unit.toLowerCase()) continue;
        for (const pack of bfr.Packs || []) {
          batchSizes.push(Number(bfr.batch_formula_weight));
        }
      }
    }
     
    console.log("Available batch sizes :", batchSizes);
    batchSizes = Array.from(new Set(batchSizes)).sort((a, b) => b - a);
    console.log("Available batch sizes (descending):", batchSizes);
    if (!batchSizes.length) return alert("No batch sizes available!");
    this.lowestBatchSize = Math.min(...batchSizes);

    // Initialize stock maps for decrement
    const rmStockMap: any = {};
    const pmStockMap: any = {};
    for (const mfr of this.mfr_list) {
      for (const bfr of mfr.bfr_records || []) {
        for (const pack of bfr.Packs || []) {
          const rawMaterials = typeof pack.raw_materials === 'string' ? JSON.parse(pack.raw_materials) : pack.raw_materials || [];
          const packingMaterials = typeof pack.packing_materials === 'string' && pack.packing_materials !== "null" ? JSON.parse(pack.packing_materials) : pack.packing_materials || [];

          for (const rm of rawMaterials) rmStockMap[rm.material_name] = rm.avbl_stock || 0;
          for (const pm of packingMaterials) pmStockMap[pm.material_name] = pm.avbl_stock || 0;
        }
      }
    }

    // Plan batches
    let batchPlan: number[] = [];
    for (const size of batchSizes) {
      while (remainingQty >= size) {
        batchPlan.push(size);
        remainingQty -= size;
      }
    }

    // Remaining quantity logic
    this.remainingQtyToPlan = remainingQty > 0 ? remainingQty : 0;
    console.log("Planned batches:", batchPlan, "Remaining Qty:", this.remainingQtyToPlan);

    // Prepare batches array
    this.batches = [];
    let batchIndex = 1;

    for (const batchQty of batchPlan) {
      this.createBatch(batchQty, batchIndex, rmStockMap, pmStockMap);
      batchIndex++;
    }

    this.calculateBatchSummary();
    console.log("Batches ready for Angular table:", this.batches);
  });
}

// Batch summary calculation
calculateBatchSummary() {
  if (!this.batches || this.batches.length === 0) return;

  this.summaryRawMaterials = [];
  this.summaryPackingMaterials = [];

  const rawMap: any = {};
  for (const batch of this.batches) {
    for (const rm of batch.raw_materials) {
      if (!rawMap[rm.material_name]) {
        rawMap[rm.material_name] = {
          material_name: rm.material_name,
          total_required: 0,
          total_short: 0,
          total_available: rm.avbl_stock
        };
      }
      rawMap[rm.material_name].total_required += rm.required_qty;
      rawMap[rm.material_name].total_short += rm.short_qty;
    }
  }
  this.summaryRawMaterials = Object.values(rawMap);

  const packMap: any = {};
  for (const batch of this.batches) {
    for (const pm of batch.packing_materials) {
      if (!packMap[pm.material_name]) {
        packMap[pm.material_name] = {
          material_name: pm.material_name,
          total_required: 0,
          total_short: 0,
          total_available: pm.avbl_stock
        };
      }
      packMap[pm.material_name].total_required += pm.required_qty;
      packMap[pm.material_name].total_short += pm.short_qty;
    }
  }
  this.summaryPackingMaterials = Object.values(packMap);

  console.log("Raw Material Summary:", this.summaryRawMaterials);
  console.log("Packing Material Summary:", this.summaryPackingMaterials);
}

// Adjust with lowest batch size
adjustWithLowestBatch() {
  if (!this.remainingQtyToPlan || this.remainingQtyToPlan <= 0) return;
  console.log(`Adjusting remaining ${this.remainingQtyToPlan} with lowest batch ${this.lowestBatchSize}`);
  this.createBatch(this.lowestBatchSize, this.batches.length + 1);
  this.remainingQtyToPlan = 0;
  this.calculateBatchSummary();
}

// Plan next month
planNextMonth() {
  if (!this.remainingQtyToPlan || this.remainingQtyToPlan <= 0) return;
  console.log(`Remaining ${this.remainingQtyToPlan} will be planned next month.`);
  this.remainingQtyToPlan = 0;
}

// Helper to create batch
createBatch(batchQty: number, batchIndex: number, rmStockMap?: any, pmStockMap?: any) {
  let bestBFR: any;
  outer: for (const mfr of this.mfr_list) {
    for (const bfr of mfr.bfr_records || []) {
      if (bfr.uni.toLowerCase() === this.ord_unit.toLowerCase()) {
        bestBFR = bfr;
        break outer;
      }
    }
  }
  if (!bestBFR) return;

  const pack = bestBFR.Packs[0];
  const rawMaterials = typeof pack.raw_materials === 'string' ? JSON.parse(pack.raw_materials) : pack.raw_materials || [];
  const packingMaterials = typeof pack.packing_materials === 'string' && pack.packing_materials !== "null" ? JSON.parse(pack.packing_materials) : pack.packing_materials || [];

  rmStockMap = rmStockMap || {};
  pmStockMap = pmStockMap || {};

  const batchData = {
    batchIndex,
    batchQty,
    raw_materials: rawMaterials.map(rm => {
      const multiplier = batchQty / bestBFR.batch_formula_weight;
      const required_qty = +(rm.batch_qty * multiplier).toFixed(3);

      const available = rmStockMap[rm.material_name] || rm.avbl_stock || 0;
      const short_qty = Math.max(0, required_qty - available);
      rmStockMap[rm.material_name] = available - required_qty;

      return {
        ...rm,
        required_qty,
        short_qty,
        plan_qty: required_qty,
        avbl_stock: available
      };
    }),
    packing_materials: packingMaterials.map(pm => {
      const multiplier = batchQty / bestBFR.batch_formula_weight;
      const required_qty = +(pm.batch_qty * multiplier).toFixed(3);

      const available = pmStockMap[pm.material_name] || pm.avbl_stock || 0;
      const short_qty = Math.max(0, required_qty - available);
      pmStockMap[pm.material_name] = available - required_qty;

      return {
        ...pm,
        required_qty,
        short_qty,
        plan_qty: required_qty,
        avbl_stock: available
      };
    })
  };

  this.batches.push(batchData);
}

}
  