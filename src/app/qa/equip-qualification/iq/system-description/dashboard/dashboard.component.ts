import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  equipment_name='';
  department_name='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getEquipmentsLog();
  }

  getEquipmentsLog(){
    this.service.get('master/equipment.php?type=getEquipments'+'&equipment_name='+ this.equipment_name +'&department_name='+this. department_name).subscribe(response=>{
      this.results=response;
    });
  }
  download(){
    this.service.open('');
   }
}
