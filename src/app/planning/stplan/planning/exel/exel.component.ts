import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-exel',
  templateUrl: './exel.component.html',
  styleUrls: ['./exel.component.css']
})
export class ExelComponent implements OnInit {

 constructor(private service: DataAccessService) { }
loading=false
pendingpo
  ngOnInit(): void {
    this.getPendingWOs();

  }

    getPendingWOs() {
        this.loading = true;
        this.service.get('marketing/po.php?type=ExcelFormat').subscribe(response => {
     this.pendingpo = response;

let stockTracker: any = {}; // store running stock for each material

this.pendingpo = this.pendingpo.map((row: any) => {
  
  let material = row.mother_material_code;
  let requiredQty = Number(row.batch_plan_qty);
  let stock = Number(row.stock_in_hand);

  // If first time seeing this material → initialize
  if (!stockTracker[material]) {
    stockTracker[material] = stock;
  }

  // Deduct
  let newBalance = stockTracker[material] - requiredQty;

  // Store for table
  row.stock_deducted = newBalance < 0 ? 0 : newBalance;      // show excess/short
  row.inHand=Number(row.stock_deducted)+Number(requiredQty)
  row.stock_negative = newBalance < 0 ? newBalance : ''; // negative value only

  // Update tracker
  stockTracker[material] = newBalance;

  return row;
});

      
       this.loading = false;
    });
    
    
  }
  

}
