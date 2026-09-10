import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]

})
export class LogComponent implements OnInit {
  results: any[] = [];
  equipmentNames: any[] = [];
  equipment_name = '';
  from_date = '';
  to_date = '';
  equipment_type = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getEquipmentNames();
    this.getEquipmentUsagesLog();
  }

  getEquipmentNames() {
    this.service.get('equipments.php?type=getEquipmentsByType').subscribe((response: any) => {
      this.equipmentNames = Array.isArray(response) ? response : [];
    });
  }

  getEquipmentUsagesLog() {
    this.service
      .get(
        'equipments.php?type=getEquipmentUsagesLog&equipment_name=' +
          encodeURIComponent(this.equipment_name || '') +
          '&from_date=' +
          encodeURIComponent(this.from_date || '') +
          '&to_date=' +
          encodeURIComponent(this.to_date || '') +
          '&equipment_type=' +
          encodeURIComponent(this.equipment_type || '')
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  download() {
    this.service.open(
      'equipments.php?type=downloadEquipmentUsagesLog&equipment_name=' +
        encodeURIComponent(this.equipment_name || '') +
        '&from_date=' +
        encodeURIComponent(this.from_date || '') +
        '&to_date=' +
        encodeURIComponent(this.to_date || '') +
        '&equipment_type=' +
        encodeURIComponent(this.equipment_type || '')
    );
  }
}
