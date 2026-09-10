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
  from_date = '';
  to_date = '';
  today = '';
  equipment_type = '';
  equipment_code = '';
  category: any;
  selectedData: any[] = [];
  isView = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.getEquipmentCleaningLog();
    this.getCategory();
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

  getCategory(): void {
    this.service.get('equipments.php?type=getEquipmentCategories').subscribe((response) => {
      this.category = response;
    });
  }

  getName(index: number): void {
    index = index - 1;
    if (index !== -1 && Array.isArray(this.category)) {
      this.selectedData = this.category[index];
    }
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
