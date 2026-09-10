import { DatePipe } from '@angular/common';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-req-collection',
  templateUrl: './req-collection.component.html',
  styleUrls: ['./req-collection.component.css'],
  providers: [DatePipe],
})
export class ReqCollectionComponent implements OnInit {
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPOsLog(); 
  }


  // Track previous order_no to assign alternate bg
lastOrderNo: string = '';
rowColorMap: { [orderNo: string]: string } = {};

// getRowBgColor(po: any): string {
//   // If this order_no is already mapped, return the color
//   if (this.rowColorMap[po.po_order_no]) {
//     return this.rowColorMap[po.po_order_no];
//   }

//   // If this is a new po_order_no, alternate color
//   const prevColor = Object.keys(this.rowColorMap).length % 2 === 0 ? '#df88c9ff' : '#f04074ff'; // Example colors
//   this.rowColorMap[po.po_order_no] = prevColor;

//   return prevColor;
// }
getRowBgColor(po: any): string {
  // Unique key for po_order_no + batchId
  const key = `${po.po_order_no}_${po.batchId}`;

  // Return color if already mapped
  if (this.rowColorMap[key]) {
    return this.rowColorMap[key];
  }

  // Define multiple colors
  const colors = ['#df88c9ff', '#f04074ff', '#88d9cfff', '#40b0b0ff', '#ffc888ff', '#ff8040ff'];

  // Pick color based on existing keys count
  const colorIndex = Object.keys(this.rowColorMap).length % colors.length;
  const chosenColor = colors[colorIndex];

  // Map this key to the chosen color
  this.rowColorMap[key] = chosenColor;

  return chosenColor;
}

mfr_list = [];
result = [];
ord_unit: any;
lowestBatchSize: any;
remainingQty: any;
remainingQtyToPlan: any;

batches = [];
summaryRawMaterials = [];
summaryPackingMaterials = [];

// ========================== GET POs ==========================
getPOsLog() {
  this.service
    .get("marketing/po.php?type=getPOsLogForCheckedBox")
    .subscribe((response: any) => {
      this.result = response;
      
      for (let i = 0; i < this.result.length; i++) {
        let po = this.result[i];
          po.batchPlan = this.calculateBatchPlan(po.planQty, po.batch_formula);
        let remainingQty = po.order_qty;
        this.ord_unit = po.ord_unit;

        // ---------------- GET MFR LIST ----------------
        this.mfr_list = po.mfr_records || [];
        if (!this.mfr_list.length) {
          alert("No MFR found!");
          continue;
        }

        // ---------------- COLLECT ALL BATCH SIZES ----------------
        let batchSizes: number[] = [];

        for (const mfr of this.mfr_list) {
          for (const bfr of mfr.bfr_records || []) {
            if (bfr.uni.toLowerCase() !== this.ord_unit.toLowerCase()) continue;

            for (const pack of bfr.Packs || []) {
              batchSizes.push(Number(bfr.batch_formula_weight));
            }
          }
        }

        batchSizes = Array.from(new Set(batchSizes)).sort((a, b) => b - a);

        if (!batchSizes.length) {
          alert("No batch sizes available!");
          continue;
        }

        this.lowestBatchSize = Math.min(...batchSizes);

        // ---------------- STOCK MAP INIT ----------------
        const rmStockMap: any = {};
        const pmStockMap: any = {};

        const {
          batch_formula_weight,
          mainGroupName,
          mfr_no,
          ord_unit,
          category,
          order_qty,
          order_no,
        } = po;

        for (const mfr of this.mfr_list) {
          for (const bfr of mfr.bfr_records || []) {
            for (const pack of bfr.Packs || []) {
              const rawMaterials =
                typeof pack.raw_materials === "string"
                  ? JSON.parse(pack.raw_materials)
                  : pack.raw_materials || [];

              const packingMaterials =
                typeof pack.packing_materials === "string" &&
                pack.packing_materials !== "null"
                  ? JSON.parse(pack.packing_materials)
                  : pack.packing_materials || [];

              for (const rm of rawMaterials)
                rmStockMap[rm.material_name] = rm.avbl_stock || 0;

              for (const pm of packingMaterials)
                pmStockMap[pm.material_name] = pm.avbl_stock || 0;
            }
          }
        }

        // ---------------- PLAN BATCHES ----------------
        let batchPlan: number[] = [];

        for (const size of batchSizes) {
          while (remainingQty >= size) {
            batchPlan.push(size);
            remainingQty -= size;
          }
        }

        this.remainingQtyToPlan = remainingQty > 0 ? remainingQty : 0;

        // ---------------- BUILD BATCHES ----------------
        this.batches = [];
        let batchIndex = 1;

        for (const batchQty of batchPlan) {
          this.createBatch(
            batchQty,
            batchIndex,
            rmStockMap,
            pmStockMap,
            batch_formula_weight,
            mainGroupName,
            mfr_no,
            ord_unit,
            category,
            order_qty,
            order_no
          );
          batchIndex++;
        }

        // ---------------- CALCULATE SUMMARY ----------------
        this.calculateBatchSummary();

        // =====================================================
        //   STORE IN RESULT[i] (THIS IS WHAT YOU ASKED FOR)
        // =====================================================
        this.result[i].batches = JSON.parse(JSON.stringify(this.batches));
        this.result[i].summaryRaw = JSON.parse(
          JSON.stringify(this.summaryRawMaterials)
        );
        this.result[i].summaryPack = JSON.parse(
          JSON.stringify(this.summaryPackingMaterials)
        );

        console.log("Stored batches for index :", i, this.result[i]);
        let canPlanCount = 0;

for (let j = 0; j < this.result[i].batches.length; j++) {
  const batch = this.result[i].batches[j];

  // Check raw materials and packing materials for short_qty
  const rawOk = batch.raw_materials.every(rm => rm.short_qty === 0);
  const packOk = batch.packing_materials.every(pm => pm.short_qty === 0);

  // If both raw and packing materials have no shortage, batch can be planned
  batch.canPlan = rawOk && packOk;

  if (batch.canPlan) canPlanCount++;
}

// Store count in the PO object
this.result[i].canPlanBatches = canPlanCount;

console.log("Stored batches for index :", i, this.result[i]);
      }
      
    });
}

// ========================== CREATE BATCH ==========================
createBatch(
  batchQty: number,
  batchIndex: number,
  rmStockMap?: any,
  pmStockMap?: any,
  batch_formula_weight?: any,
  mainGroupName?: any,
  mfr_no?: any,
  ord_unit?: any,
  category?: any,
  order_qty?: any,
  order_no?: any
) {
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

  const rawMaterials =
    typeof pack.raw_materials === "string"
      ? JSON.parse(pack.raw_materials)
      : pack.raw_materials || [];

  const packingMaterials =
    typeof pack.packing_materials === "string" &&
    pack.packing_materials !== "null"
      ? JSON.parse(pack.packing_materials)
      : pack.packing_materials || [];

  rmStockMap = rmStockMap || {};
  pmStockMap = pmStockMap || {};

  const batchData = {
    batchIndex,
    batchQty,

    batch_formula_weight,
    mainGroupName,
    mfr_no,
    ord_unit,
    category,
    order_qty,
    order_no,

    raw_materials: rawMaterials.map((rm) => {
      const multiplier = batchQty / bestBFR.batch_formula_weight;

      let required_qty = +(rm.batch_qty * multiplier).toFixed(3);
      if (required_qty < 0) required_qty = 0;

      let available = rmStockMap[rm.material_name] ?? rm.avbl_stock ?? 0;
      if (available < 0) available = 0;

      const short_qty = Math.max(0, required_qty - available);
      rmStockMap[rm.material_name] = available - required_qty;

      return {
        ...rm,
        required_qty,
        short_qty,
        plan_qty: required_qty,
        avbl_stock: available,
      };
    }),

    packing_materials: packingMaterials.map((pm) => {
      const multiplier = batchQty / bestBFR.batch_formula_weight;

      let required_qty = +(pm.batch_qty * multiplier).toFixed(3);
      if (required_qty < 0) required_qty = 0;

      let available = pmStockMap[pm.material_name] ?? pm.avbl_stock ?? 0;
      if (available < 0) available = 0;

      const short_qty = Math.max(0, required_qty - available);
      pmStockMap[pm.material_name] = available - required_qty;

      return {
        ...pm,
        required_qty,
        short_qty,
        plan_qty: required_qty,
        avbl_stock: available,
      };
    }),
  };

  this.batches.push(batchData);
}

// ========================== SUMMARY ==========================
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
          total_available: rm.avbl_stock,
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
          total_available: pm.avbl_stock,
        };
      }

      packMap[pm.material_name].total_required += pm.required_qty;
      packMap[pm.material_name].total_short += pm.short_qty;
    }
  }

  this.summaryPackingMaterials = Object.values(packMap);
}

  // Batch calculation function
  calculateBatchPlan(planQty: number, batchSizes: any[]): any {
    if (!planQty || !batchSizes || batchSizes.length === 0) return { batches1: [], leftover: planQty, excess: 0 };

    let remainingQty = planQty;
    const batches1: any[] = [];
    let totalProduced = 0;

    // Sort batchSizes descending to use largest batches1 first
    const sortedBatches1 = batchSizes.sort((a: any, b: any) => b.batch_formula_weight - a.batch_formula_weight);

    for (let batch of sortedBatches1) {
      const size = parseFloat(batch.batch_formula_weight);
      const count = Math.floor(remainingQty / size);
      if (count > 0) {
        batches1.push({ size, count });
        totalProduced += count * size;
        remainingQty -= count * size;
      }
    }

    // Check if any leftover remains
    const leftover = remainingQty;
    const smallestBatch = parseFloat(sortedBatches1[sortedBatches1.length - 1].batch_formula_weight);
    const excess = leftover > 0 ? smallestBatch - leftover : 0;

    return { batches1, leftover, excess };
  }

 
  isView = false;
  selectedResult = [];

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }
    
 
  

 
 
 


  send(){
    let temp={};
    temp['selectedPOs']=this.result;
      this.service.post('purchase/autopurchase.php?type=sendOrdersForWorkorder', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
           
              this.getPOsLog(); 
 location.reload();
            } else {
              alertify.error('Failed: An error occured, please try again!');
            }
          });
  }
 








 





hoveredPO: any = null;
hoverX: number = 0;
hoverY: number = 0;

@ViewChild('datagridWrapper', { static: true }) datagridWrapper: ElementRef;

showHover(po: any, event: MouseEvent) {
  this.hoveredPO = po;
  this.updateHoverPosition(event);
}

moveHover(event: MouseEvent) {
  this.updateHoverPosition(event);
}

hideHover() {
  this.hoveredPO = null;
}

updateHoverPosition(event: MouseEvent) {
  const offset = 15; // optional offset from cursor
  this.hoverX = event.clientX + offset;
  this.hoverY = event.clientY + offset;
}

 
ViewWorkOrder(data){
  this.isView=true;
   this.selectedResult = data;
}
   
}


