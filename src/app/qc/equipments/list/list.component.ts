import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css'],
  providers:[DatePipe]
})
export class ListComponent implements OnInit {
  results: any[] = [];
  selectedResult: any = {};
  isView = false;
  equipment_name = '';
  from_date = '';
  to_date = '';
  equipment_type = '';
  category: any[] = [];
  selectedData: any[] = [];
  department = 'Quality Control';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.getCategory();
    this.getEquipmentList();
  }

  getCategory() {
    this.service.get('equipments.php?type=getEquipmentTypes').subscribe((response: any) => {
      this.category = Array.isArray(response) ? response : [];
    });
  }

  getName(index: number) {
    index = index - 1;
    if (index !== -1) {
      this.selectedData = this.category[index];
    }
  }

  getEquipmentList() {
    const type = encodeURIComponent(this.equipment_type || '');
    const dept = encodeURIComponent(this.department);
    this.service
      .get(
        'equipments.php?type=getDeptAllEquipments&department=' +
          dept +
          '&equipment_type=' +
          type +
          '&status=Active'
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(index: number) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download() {
    const type = encodeURIComponent(this.equipment_type || '');
    const dept = encodeURIComponent(this.department);
    this.service.open(
      'equipments.php?type=getDeptAllEquipments&department=' +
        dept +
        '&equipment_type=' +
        type +
        '&status=Active'
    );
  }
}
