import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-intimation-log',
  templateUrl: './intimation-log.component.html',
  styleUrls: ['./intimation-log.component.css'],
  providers:[DatePipe]
})
export class IntimationLogComponent implements OnInit {

  from_date = '';
  to_date = '';
  today = '';
  results;
  isView=false;
  selectedResult=[];
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getGoodsLog();
  }

  getGoodsLog() {
    this.service.get('store/goods.php?type=getPendingIntimation').subscribe(response => {
      this.results = response;
    });
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download() {
    this.service.open('store/goods.php?type=downloadCalculator')
  }

}
