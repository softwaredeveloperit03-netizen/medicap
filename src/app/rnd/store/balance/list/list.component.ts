import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ListComponent implements OnInit {

  isView = false;
  results;
  selectedBalance = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getBalances();
  }

  getBalances() {
    this.service.get('balance.php?type=getBalances').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

  download() {}

}
