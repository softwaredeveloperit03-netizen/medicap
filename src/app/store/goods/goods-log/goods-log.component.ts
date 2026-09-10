  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;

@Component({
  selector: 'app-goods-log',
  templateUrl: './goods-log.component.html',
  styleUrls: ['./goods-log.component.css'],
  providers: [DatePipe] 
})
export class GoodsLogComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getGoodsLog();
  }

  getGoodsLog() {
    this.service.get('store/goods.php?type=getInvestigationLog').subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('store/goods.php?type=downloadCalculator')
  }

}