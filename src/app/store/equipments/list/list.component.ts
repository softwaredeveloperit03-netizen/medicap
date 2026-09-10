import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ListComponent implements OnInit {
  results;
  equipment_name = '';
  equipment_type = '';
  constructor(private service: DataAccessService) { }


  ngOnInit(): void {
    this.getEquipmentList();
  }

  getEquipmentList() {
    this.service.get('store/equipment.php?type=getEquipmentList&equipment_type=' + this.equipment_type).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('store/equipment.php?type=downloadEquipmentList&equipment_type=' + this.equipment_type);
  }
}
