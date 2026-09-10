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

  selectedResult = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getChallansLog();
  }

  getChallansLog(){
    this.service.get('store/packing.php?type=getChallansLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}
