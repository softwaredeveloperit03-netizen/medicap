import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-fo',
  templateUrl: './fo.component.html',
  styleUrls: ['./fo.component.css'],
  providers: [DatePipe],
})
export class FoComponent implements OnInit {
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPOsLog(); 
  }

  // Store colors per PO
rowColorMap: { [poOrderNo: string]: string } = {};
colors = ['#9ae2dfff', '#e6f7ff']; // alternating colors

getRowBgColor(po: any): string {
  const key = po.po_order_no; // group by po_order_no

  if (!this.rowColorMap[key]) {
    // assign next color based on existing groups
    const nextColorIndex = Object.keys(this.rowColorMap).length % this.colors.length;
    this.rowColorMap[key] = this.colors[nextColorIndex];
  }

  return this.rowColorMap[key];
}

// Component.ts
getHoverInfo(po: any): string {
  return `Product Code: ${po.parent_product_code}
Product Name: ${po.parent_name}
Plan Qty: ${po.plan_qty} ${po.planUnit} `;
}


  result;
    selectedIndex = -1;
  getPOsLog(){
    this.service.get('marketing/po.php?type=getPOsLogForcast').subscribe(response =>{
      this.result =response;
       if (this.result.length > 0) {
        if (this.selectedIndex !== -1) {
          this.Prepare(this.selectedData,this.selectedIndex)
          
          this.isPrepare = true;
        } else {
          this.isView = false;
          this.isPrepare = false;
        }
      } else {
        this.isPrepare = false;
        this.isView = false;
      }
    });
  }

 
  isPrepare = false;
  isView = false;
  selectedResult = [];
  selectedData = [];
bal_plan_qty=0;
 Prepare(data,index) {
  this.selectedData = data;
  this.selectedIndex = index;
  this.selectedResult = data;
  this.isPrepare = true;

  this.plannedProductsList = this.selectedResult['PlannedOrders'];  
  this.showBatch = new Array(this.plannedProductsList.length).fill(false);

let totalPlannedQty = 0;

for (let i = 0; i < this.plannedProductsList.length; i++) {
  const batch = this.plannedProductsList[i];

  if (batch.length > 0) {
    totalPlannedQty += Number(batch[0].plan_qty) || 0;
  }
}

this.bal_plan_qty = Number(this.selectedResult['plan_qty']) - totalPlannedQty;

console.log("Balance Plan Qty:", this.bal_plan_qty);
}

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }
    
  

 
  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
  }



  
   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.result; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.result.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
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


// Component.ts
deliveryDate: string = '';
remark: string = '';
plan_month: string = '';
plan_qty: number = 0;

plannedProductsList: any[] = []; // Array of batches
plannedProducts: any[] = []; // Array of batches

PreparePlan() {
  if (!this.plannedProducts) {
    this.plannedProducts = [];
     
  }

  const batch: any[] = [];
 const batchId = this.plannedProductsList.length + 1;
  for (let i = 0; i < this.selectedResult['products'].length; i++) {
    const product = this.selectedResult['products'][i];

    // Calculate plannedQty
    const plannedQty = this.plan_qty;

    batch.push({
       batchId: batchId,
      Fo_code: product.Fo_code,
      deliveryDate: this.deliveryDate,
      groupcode: product.groupcode,
      OrderMaterials_id: product.id,
      mainGroupName: product.mainGroupName,
      order_no: product.order_no,
      packingStyle: product.packingStyle,
      packingUnit: product.packingUnit,
      parent_product_code: product.parent_product_code,
      planMonth: this.plan_month,
      planQty: plannedQty,
      planUnit: product.planUnit,
      plan_qty: this.plan_qty,
      plant_id: product.plant_id,
      po_entry_id: product.po_entry_id,
      product_code: product.product_code,
      product_name: product.product_name,
      remark: this.remark,      
      po_order_no: this.selectedResult['order_no'],      
      subClient: product.subClient,
    
      
    });
  }

  // Add this batch to the plannedProducts array
  this.plannedProducts.push(batch);
  this.plannedProductsList.push(batch);
  this.plan_month=''
  this.plan_qty=0
  this.deliveryDate=''
  this.remark=''
  console.log('Planned Products:', this.plannedProducts);
  this.SaveForcast();
}


 


SaveForcast(){
 
    let temp = {};
    temp['plannedProducts'] = this.plannedProducts;
 
    this.service.post('marketing/po.php?type=saveForcastPlan', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getPOsLog();
          this.plannedProducts = [];
          this.isView = false;
          this.isPrepare = false;
          alertify.success('Saved successfully');
        } else {
          alertify.error('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alertify.error('An error has occurred.');
        } else {
          alertify.error('An error has occurred, http status:' + error.status);
        }
      });
  }




  showBatch: boolean[] = [];
toggleShow(batchIndex: number) {
  this.showBatch[batchIndex] = !this.showBatch[batchIndex];
}
   
}


