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
    this.results = [
      {"id":"1","product_code":"PR001","product_name":"Demo Product","grade":"BP","batch_no":"1234","batch_size":"1","packing_qty":"10"}
    ];
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(value) {
    if (value == 'manual') {
      this.service.open('packing/packinglist.php?type=packingListPDF&id=' + this.selectedResult['id']);
    } else if (value == 'digital') {
      this.service.open('packing/packinglist.php?type=sopdigital&id=' + this.selectedResult['id']);
    } else {
      this.service.open('packing/packinglist.php?type=packingListLogPDF');
    }
  }

}
