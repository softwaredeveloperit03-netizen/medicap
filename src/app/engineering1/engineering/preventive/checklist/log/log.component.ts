import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  preventive;
  equipments;
  equipment_type = '';
  equipment_name = '';
  selectedResult = [];
  selectedEquip = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPreventives();
    this.getEquipments();
  }

  
  getPendingPreventives(){
    this.service.get('engineering/preventive.php?type=getPreventivesLog&equipment_type=' + this.equipment_type + '&equipment_name=' + this.equipment_name).subscribe(response =>{
      this.preventive = response;
    });
  }

  download(){
    this.service.open('engineering/preventive.php?type=downloadPreventivesLog&equipment_type=' + this.equipment_type + '&equipment_name=' + this.equipment_name)
  }

  getEquipments(){
    this.service.get('equipments.php?type=getEquipmentCategories').subscribe(response => {
      this.equipments = response;
    });
  }

  getEquipmentsDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedEquip = this.equipments[index];
    }
  }

  view(index){
    this.selectedResult = this.preventive[index];
    this.isView = true;
  }
  close(){
    this.isView=false;
  }

}
