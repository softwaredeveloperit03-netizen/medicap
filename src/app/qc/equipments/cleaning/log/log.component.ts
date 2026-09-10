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
  selectedResult: any;
  equipment_name = '';
  equipment_code = '';
  from_date = '';
  to_date = '';
  today = '';
  equipment_type = '';
  equipmentOptions: any[] = [];
  isView = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.loadEquipmentOptions();
    this.getEquipmentCleaningLog();
  }

  loadEquipmentOptions(): void {
    this.service.get('equipments.php?type=getEquipmentsByType').subscribe((response) => {
      this.equipmentOptions = Array.isArray(response) ? response : [];
    });
  }

  getEquipmentCleaningLog(): void {
    this.service
      .loadList(
        'equipments.php?type=getEquipmentCleaningLog' +
          '&equipment_code=' +
          encodeURIComponent(this.equipment_code || '') +
          '&from_date=' +
          encodeURIComponent(this.from_date || '') +
          '&to_date=' +
          encodeURIComponent(this.to_date || '') +
          '&equipment_type=' +
          encodeURIComponent(this.equipment_type || '') +
          '&equipment_name=' +
          encodeURIComponent(this.equipment_name || '')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index: number): void {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(): void {
    this.service.open(
      'equipments.php?type=downloadEquipmentCleaningLog' +
        '&equipment_code=' +
        encodeURIComponent(this.equipment_code || '') +
        '&from_date=' +
        encodeURIComponent(this.from_date || '') +
        '&to_date=' +
        encodeURIComponent(this.to_date || '') +
        '&equipment_type=' +
        encodeURIComponent(this.equipment_type || '') +
        '&equipment_name=' +
        encodeURIComponent(this.equipment_name || '')
    );
  }
}
