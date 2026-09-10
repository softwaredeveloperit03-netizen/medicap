import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 

@Component({
  selector: 'app-linebooking',
  templateUrl: './linebooking.component.html',
  styleUrls: ['./linebooking.component.css']
})
export class LinebookingComponent implements OnInit {
  
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


        this.service.get('marketing/po.php?type=getCanPlannedWOPlaning_STP').subscribe(response => {
      this.pendingpo = response;
        this.pendingpoBackup =this.pendingpo;
       this.loading = false;
    });
    
    
  }

  


 SENDfORaNALYSIS(){
 
let temp={}
temp['Worders']=this.pendingpo;
    this.service.post(
      `marketing/po.php?type=update_Wo_SENDfORaNALYSIS`,
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
}


