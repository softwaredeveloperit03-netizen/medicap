import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  isView = false;
  results;
  selectedReport = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingGRN();
  }

  getPendingGRN() {
    this.service.get('store/raw.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

}
