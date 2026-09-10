import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css'],
  providers: [DatePipe]
})
export class CleaningComponent implements OnInit {

  from_date = '';
  to_date= '';
  results;
  selectedResult=[];
  isView=false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {

    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  ngOnInit() {    
    this.getTankSanitizationLog();
  }

  getTankSanitizationLog(){
    this.service.get('engineering/watertank.php?type=getTankSanitizationLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  download(){
    this.service.open('engineering/watertank.php?type=downloadTankSanitizationLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  downloadView(){
    this.service.open('engineering/watertank.php?type=downloadViewTankSanitizationLog&id='+this.selectedResult['id']);
  }

}
