import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingGeneralEquipmentCleaning();
  }
  getPendingGeneralEquipmentCleaning(){
    this.service.get('equipments.php?type=getPendingGeneralEquipmentCleaning').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updateEquipmentCleaning(status) {
    this.service.get('equipments.php?type=updateEquipmentCleaning&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Equipment Cleaninge Form Updated Successfully');
        this.isView = false;
        this.getPendingGeneralEquipmentCleaning();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
 }

}
