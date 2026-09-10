import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-hold',
  templateUrl: './hold.component.html',
  styleUrls: ['./hold.component.css']
})
export class HoldComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getHoldRecords();
  }

  getHoldRecords() {
    this.service.get('store/dispensing.php?type=getHoldRecords').subscribe(response => {
      this.results = response;
    });
  }

}
