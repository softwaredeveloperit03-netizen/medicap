import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 
@Component({
  selector: 'app-pendingplan',
  templateUrl: './pendingplan.component.html',
  styleUrls: ['./pendingplan.component.css']
})
export class PendingplanComponent implements OnInit {
  
  ngOnInit() {
    this.getPendingWOs();
  }
    constructor(private service: DataAccessService) { }
  loading: boolean = false;
finalDeductions: any[] = []; // This will store the final output

pendingpo


    searchText: string = '';
pendingpoBackup: any[] = [];  
  applyFilter() {
  const query = this.searchText.toLowerCase();

  this.pendingpo = this.pendingpoBackup.filter(po =>
    JSON.stringify(po).toLowerCase().includes(query)
  );
}
  getPendingWOs() {

        this.loading = true;


        this.service.get('marketing/po.php?type=getPendingPlannedWO').subscribe(response => {
      this.pendingpo = response;
        this.pendingpoBackup =this.pendingpo;
       this.loading = false;
    });
    
    
  }
  


 isShortage=false;
toggleMC(mat: any, useMC: boolean) {

  if (!mat.original_MC) {
    mat.original_MC = mat.deducted_from_MC;   // Save original MC qty
  }

  if (useMC) {
    // YES → Accept MC use
    mat.deducted_from_MC = mat.original_MC;
    // no changes to shortage
  } else {
    // NO → Shift MC qty to shortage
    mat.shortage = (mat.shortage || 0) + mat.deducted_from_MC;
    mat.deducted_from_MC = 0;
  }

  // After either choice, buttons must disappear
  mat.hideButtons = true;

 // 🔥 FIXED SHORTAGE CHECK
  this.isShortage = false;   // reset first

  for (let item of this.selectedWo['Deductions']) {
    if (item.shortage > 0) {
      this.isShortage = true;
      break; // no need to check further
    }
  }
}



 isView=false;
 selectedWo=[]
 View(wo:any){
  this.isView=true;
  this.selectedWo=wo;
  for(let i=0;i<this.selectedWo['Deductions'].length;i++){
    let wo=this.selectedWo['Deductions'][i];
    wo['requiredQty']=Number(wo['deducted_from_MC'])+Number(wo['deducted_from_RM'])+Number(wo['shortage']);
  }
 }



 
 SendPlan(){ 
 
let temp=this.selectedWo;
    this.service.post(
      `marketing/po.php?type=send_for_Planning`,
      JSON.stringify(temp)
    ).subscribe((response: any) => {
      if (response.status === 'success') {
        alert('Selected Orders Approved Successfully');      
        this.getPendingWOs();       
      } else {
        alert('An error has occurred, please try again');
      }
    });
 }
 Short(){ 
 
let temp=this.selectedWo;
    this.service.post(
      `marketing/po.php?type=send_for_Shortages`,
      JSON.stringify(temp)
    ).subscribe((response: any) => {
      if (response.status === 'success') {
        alert('Selected Orders Approved Successfully');      
        this.getPendingWOs();       
      } else {
        alert('An error has occurred, please try again');
      }
    });
 }
 

}





