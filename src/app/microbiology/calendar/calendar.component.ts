import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-calendar',
  templateUrl: './calendar.component.html',
  styleUrls: ['./calendar.component.css']
})
export class CalendarComponent implements OnInit {

  equipments;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getQCMicroEquipments();
  }

  getQCMicroEquipments() {
    this.service.get('equipments.php?type=getQCMicroEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

}
