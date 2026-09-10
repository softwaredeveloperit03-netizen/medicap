import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

  selectedRack = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRacksLog();
  }

  getRacksLog() {
    this.service.get('store/location.php?type=getRacksLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedRack = this.results[index];
    this.isView = true;
  }

  downloadChart(){
    this.service.open('store/location.php?type=downloadLocationChart');
  }

}
