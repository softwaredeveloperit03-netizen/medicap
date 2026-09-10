import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  selectedResult = [];
  isView=false;
  equipment_type = '';
  equipments;
  equipment_name = '';
  status = '';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getCalibrationIdentifications();
    this.getEquipmentNames();
  }
  getEquipmentNames() {
    this.service.get('equipments.php?type=getEquipmentsByType&equipment_type=' + this.equipment_type).subscribe(response => {
      this.equipments = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getCalibrationIdentifications() {
    this.service.get('qa/calibration.php?type=getCalibrationIdentifications&equipment_type='+ this.equipment_type+'&equipment_name='+this.equipment_name+'&status='+this.status).subscribe(response=>{
      this.results=response;
    })
  }
}
