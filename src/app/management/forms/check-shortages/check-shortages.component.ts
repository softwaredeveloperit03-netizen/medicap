import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-check-shortages',
  templateUrl: './check-shortages.component.html',
  styleUrls: ['./check-shortages.component.css']
})
export class CheckShortagesComponent implements OnInit {
  isView = false;
  results = [];
  selectedResult = [];

  material_name = '';
  material_type = '';
  constructor(private service: DataAccessService) {
  }
  ngOnInit() {
    this.getShortages();
  }

  getShortages() {
    this.service.get('management/inventory.php?type=getShortages').subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadLog(){
    this.service.open('management/inventory.php?type=ShortagesLog');
  }
}
