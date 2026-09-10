import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-status',
  templateUrl: './status.component.html',
  styleUrls: ['./status.component.css']
})
export class StatusComponent implements OnInit {

  results;
  equipment_type = '';
  status = '';

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDeptAllEquipments();
  }

  getDeptAllEquipments(){
    this.service.get('equipments.php?type=getDeptAllEquipments&equipment_type=' + this.equipment_type + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  download(){
    this.service.open('equipments.php?type=downloadDeptAllEquipments&equipment_type=' + this.equipment_type + '&status=' + this.status);
  }

}
