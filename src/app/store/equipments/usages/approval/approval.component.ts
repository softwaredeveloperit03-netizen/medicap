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
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingGeneralEquipmentUsages();
  }
  getPendingGeneralEquipmentUsages(){
    this.service.get('equipments.php?type=getPendingGeneralEquipmentUsages').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updateEquipmentsusage(status) {
    this.service.get('equipments.php?type=updateEquipmentUsage&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Equipment Usage Updated Successfully');
        this.isView = false;
        this.getPendingGeneralEquipmentUsages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
 }
}
